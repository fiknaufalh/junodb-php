<?php
namespace JunoPhpClient\Net;
use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Transport\Socket\SocketConfigHolder;
use JunoPhpClient\Util\JunoMetrics;
use JunoPhpClient\Util\JunoStatusCode;
use JunoPhpClient\Util\JunoLogLevel;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;
use React\Promise\PromiseInterface;
use React\Promise\Promise;
use function React\Promise\reject;
use function React\Promise\resolve;

class IOProcessor extends BaseProcessor
{
    private static int $counter = 0;
    private static int $INITIAL_BYPASSLTM_RETRY_INTERVAL = 337500;
    private int $bypassLTMRetryInterval;
    private static int $MAX_BYPASSLTM_RETRY_INTERVAL = 86400000;
    private int $id;
    private SocketConfigHolder $config;
    private RequestQueue $requestQueue;
    private Scheduler $scheduler;
    private ?ConnectionInterface $connection = null;
    private int $handshakeFail = 0;
    private string $serverAddr = "";
    private bool $recycleStarted = false;
    private int $sendCount = 0;
    private int $failCount = 0;
    private int $recvCount = 0;
    private int $INIT_WAIT_TIME = 200;
    private int $MAX_WAIT_TIME = 60000;
    private int $reconnectWaitTime;
    private int $nextReconnectDue;
    private int $nextByPassLTMCheckTime;
    private array $opaqueRespQueueMap;
    private ?string $remoteConfigAddr;
    private ?string $remoteIpAddr;
    private int $lockIndex = -1;
    private int $ownerId = -1;
    private bool $reconnectNow = false;
    private LoopInterface $loop;
    private Connector $connector;
    public function __construct(
        SocketConfigHolder $config,
        RequestQueue $queue,
        Scheduler $scheduler,
        array $opaqueRespQueueMap,
        LoopInterface $loop
    ) {
        $this->id = self::$counter++;
        $this->config = $config;
        $this->requestQueue = $queue;
        $this->nextReconnectDue = PHP_INT_MAX;
        $this->scheduler = $scheduler;
        $this->opaqueRespQueueMap = $opaqueRespQueueMap;
        $this->remoteConfigAddr = $this->getHost() . ':' . $this->getPort();
        $this->remoteIpAddr = $this->remoteConfigAddr;
        $this->loop = $loop;
        $this->connector = new Connector($loop, ['timeout' => $this->getConnectTimeout() / 1000]);
        $this->bypassLTMRetryInterval = self::$INITIAL_BYPASSLTM_RETRY_INTERVAL;
        $this->nextByPassLTMCheckTime = time() * 1000;
        $this->reconnectWaitTime = $this->INIT_WAIT_TIME;

        $this->requestQueue->addPropertyChangeListener(function ($evt) {
            if ($evt['propertyName'] === 'recycleNow') {
                $this->reconnectNow = true;
            }
        });
    }

    private function getHost(): string
    {
        return $this->config->getHost();
    }

    private function getPort(): int
    {
        return $this->config->getPort();
    }

    private function getConnectTimeout(): int
    {
        return $this->config->getConnectTimeout();
    }

    public function useLTM(): bool
    {
        if ($this->getHost() === '127.0.0.1') {
            return true;
        }
        return !$this->config->getBypassLTM();
    }

    private function isBypassLTMDisabled(): bool
    {
        $currentTime = time() * 1000;
        if ($currentTime > $this->nextByPassLTMCheckTime && $this->bypassLTMRetryInterval < self::$MAX_BYPASSLTM_RETRY_INTERVAL) {
            return false;
        }
        return true;
    }

    public function putResponse(OperationMessage $opMsg): void
    {
        $opaq = $opMsg->getHeader()->getOpaque();
        $respQueue = $this->opaqueRespQueueMap[$opaq] ?? null;

        if ($respQueue === null) {
            error_log("The response queue for opaque=$opaq no longer exists. Probably response timed out.");
            $trans = [
                'name' => 'JUNO_LATE_RESPONSE',
                'server' => $this->serverAddr,
                'req_id' => $opMsg->getMetaComponent()->getRequestIdString(),
                'opaque' => $opaq,
                'ns' => $this->config->getRecordNamespace(),
                'w' => $this->config->getConnectionPoolSize(),
                'rht' => $opMsg->getMetaComponent()->getRequestHandlingTime(),
                'status' => JunoStatusCode::ERROR->value,
            ];
            error_log("Error " . json_encode($trans));
            JunoMetrics::recordErrorCount("JUNO_LATE_RESPONSE", $this->remoteIpAddr, JunoStatusCode::ERROR->value);
            return;
        }

        try {
            $this->scheduler->onException(TestEvent::EXCEPTION_3);
            $ok = $respQueue->push($opMsg);
            if (!$ok) {
                error_log("Response queue is full.");
                $trans = [
                    'name' => 'JUNO_RESPONSE_QUEUE_FULL',
                    'server' => $this->serverAddr,
                    'req_id' => $opMsg->getMetaComponent()->getRequestIdString(),
                    'status' => JunoStatusCode::ERROR->value,
                ];
                error_log("Error : " . json_encode($trans));
                JunoMetrics::recordErrorCount("JUNO_RESPONSE_QUEUE_FULL", $this->remoteIpAddr, JunoStatusCode::ERROR->value);
            }
        } catch (\Exception $e) {
            error_log("Adding response to response queue: " . $e->getMessage());
            $trans = [
                'name' => 'JUNO_RETURN_RESPONSE',
                'server' => $this->serverAddr,
                'req_id' => $opMsg->getMetaComponent()->getRequestIdString(),
                'error' => $e->getMessage(),
                'status' => JunoStatusCode::ERROR->value,
            ];
            error_log("Error : " . json_encode($trans));
            JunoMetrics::recordErrorCount("JUNO_RETURN_RESPONSE", $this->remoteIpAddr, get_class($e));
        }
    }

    public function onEvent(TestEvent $event): bool
    {
        return $this->scheduler->onEvent($event);
    }

    public function incrementRecvCount(): void
    {
        $this->recvCount++;
    }

    public function getServerAddr(): string
    {
        return $this->serverAddr;
    }

    public function getRemoteIpAddr(): string
    {
        return $this->remoteIpAddr;
    }

    private function getRaddr(ConnectionInterface $connection): string
    {
        $remote = $connection->getRemoteAddress();
        $off = strpos($remote, '/') + 1;
        return substr($remote, $off);
    }

    public function validateMsgCount(): void
    {
        if ($this->sendCount <= $this->recvCount && !$this->scheduler->onEvent(TestEvent::MISSING_RESPONSE)) {
            $this->recycleStarted = false;
            return;
        }

        $text = "send_count={$this->sendCount} fail_count={$this->failCount} recv_count={$this->recvCount} connection_lost=" . (!$this->recycleStarted ? 'true' : 'false');

        error_log("Missing response: " . $text);
        $trans = [
            'name' => 'JUNO_MISSING_RESPONSE',
            'server' => $this->serverAddr,
            'send_count' => $this->sendCount,
            'fail_count' => $this->failCount,
            'recv_count' => $this->recvCount,
            'connection_lost' => (!$this->recycleStarted ? 'true' : 'false'),
            'status' => JunoStatusCode::ERROR->value,
        ];
        error_log("Error : " . json_encode($trans));
        JunoMetrics::recordErrorCount("JUNO_MISSING_RESPONSE", $this->remoteIpAddr, (!$this->recycleStarted ? "connection_recycle" : "connection_lost"));

        $this->recycleStarted = false;
    }

    private function disconnect(?ConnectionInterface $other = null): void
    {
        if (!$this->isOpened()) {
            return;
        }

        $this->recycleStarted = true;
        $connection = $other ?? $this->connection;

        if ($connection !== null) {
            $raddr = $this->getRaddr($connection);
            $connection->close();
            $level = $this->scheduler->getDisconnectLogLevel($this->ownerId);
            if ($level !== JunoLogLevel::OFF) {
                error_log($level->value . " Closed connection to " . $raddr);
            }
            $trans = [
                'name' => $this->remoteIpAddr,
                'framework' => 'juno',
                'usePingIP' => $this->remoteIpAddr !== $raddr ? 'true' : 'false',
                'raddr' => $raddr,
                'id' => $this->getConnectID(),
                'status' => JunoStatusCode::SUCCESS->value,
            ];
            error_log(JunoStatusCode::SUCCESS->value . " " . json_encode($trans));
        }

        if (WorkerPool::isQuit()) {
            throw new \Exception("Interrupted");
        }

        $this->reconnectNow = false;
        $this->requestQueue->resetValues();
    }

    private function isOpened(): bool
    {
        return $this->connection !== null && $this->connection->isWritable();
    }

    private function setConnectOwner(): void
    {
        $this->ownerId = $this->scheduler->setConnectOwner($this->lockIndex);
    }

    private function getConnectID(): string
    {
        return $this->lockIndex . "_" . ($this->ownerId & 0x1);
    }

    private function afterConnect(): void
    {
        $this->reconnectWaitTime = $this->INIT_WAIT_TIME;
        $this->setNextReconnectDue();

        $this->sendCount = 0;
        $this->failCount = 0;
        $this->recvCount = 0;
        $this->recycleStarted = false;
    }

    private function connect(): PromiseInterface
    {
        if ($this->isOpened() && $this->handshakeFail <= 0) {
            return resolve(true);
        }

        $this->lockIndex = $this->scheduler->acquireConnectLock($this->lockIndex);
        $level = $this->scheduler->getConnectLogLevel($this->lockIndex);

        $notConnected = !$this->scheduler->isIndexedChannelConnected($this->lockIndex);

        return $this->ipConnect(null, $level)->then(function ($ok) use ($notConnected) {
            if (!$ok) {
                return false;
            }

            $this->handshakeFail = 0;
            if ($notConnected || $this->useLTM() || $this->isBypassLTMDisabled()) {
                $this->setConnectOwner();
                return true;
            }

            // Send a Nop to get server ip.
            $req = new PingMessage(null, 0);
            $out = $req->pack();
            $this->clearPingRespQueue();

            if ($this->connection === null) {
                return reject(new \Exception("Connection is not established"));
            }

            $writeResult = $this->connection->write($out);

            if ($writeResult instanceof PromiseInterface) {
                return $writeResult->then(function () {
                    $ip = $this->getPingIp();
                    if ($ip === null) {
                        return true;
                    }

                    $old = $this->connection;
                    $this->connection = null;

                    return $this->ipConnect($ip, $this->scheduler->getConnectLogLevel($this->lockIndex))->then(function ($ok) use ($old, $ip) {
                        if ($ok) {
                            error_log("connected via ping ip=" . $ip);
                            $this->disconnect($old);
                            $this->bypassLTMRetryInterval = self::$INITIAL_BYPASSLTM_RETRY_INTERVAL;
                            $this->nextByPassLTMCheckTime = time() * 1000;
                        } else {
                            $this->connection = $old;
                            $this->nextByPassLTMCheckTime += $this->bypassLTMRetryInterval;

                            if ($this->bypassLTMRetryInterval < self::$MAX_BYPASSLTM_RETRY_INTERVAL) {
                                $this->bypassLTMRetryInterval *= 2;
                            }
                        }

                        $this->setConnectOwner();
                        return true;
                    });
                });
            } else {
                // If write() doesn't return a Promise, we assume it was successful
                $ip = $this->getPingIp();
                if ($ip === null) {
                    return resolve(true);
                }

                $old = $this->connection;
                $this->connection = null;

                return $this->ipConnect($ip, $this->scheduler->getConnectLogLevel($this->lockIndex))->then(function ($ok) use ($old, $ip) {
                    if ($ok) {
                        error_log("connected via ping ip=" . $ip);
                        $this->disconnect($old);
                        $this->bypassLTMRetryInterval = self::$INITIAL_BYPASSLTM_RETRY_INTERVAL;
                        $this->nextByPassLTMCheckTime = time() * 1000;
                    } else {
                        $this->connection = $old;
                        $this->nextByPassLTMCheckTime += $this->bypassLTMRetryInterval;

                        if ($this->bypassLTMRetryInterval < self::$MAX_BYPASSLTM_RETRY_INTERVAL) {
                            $this->bypassLTMRetryInterval *= 2;
                        }
                    }

                    $this->setConnectOwner();
                    return true;
                });
            }
        });
    }

    private function ipConnect(?string $ip, JunoLogLevel $level): PromiseInterface
    {
        $trans = [
            'framework' => 'juno',
            'cfgAddr' => $this->remoteConfigAddr,
            'qsize' => $this->requestQueue->size(),
        ];

        $deferred = new Deferred();
        $startTimeInMs = time() * 1000;
        $handshakeStarted = false;

        try {
            if (!$this->isOpened()) {
                if ($ip === null) {
                    $start = microtime(true);
                    try {
                        $ip = gethostbyname($this->getHost());
                    } catch (\Exception $e) {
                        $trans['name'] = $this->getHost() . ':' . $this->getPort();
                        throw $e;
                    }
                    $this->remoteIpAddr = $ip . ':' . $this->getPort();

                    $duration = (microtime(true) - $start) * 1000;

                    if ($duration >= 500 || $this->scheduler->onEvent(TestEvent::DNS_DELAY)) {
                        $transTxn = [
                            'name' => 'JUNO_DNS_DELAY',
                            'ip' => $ip,
                            'status' => JunoStatusCode::WARNING->value,
                            'duration' => $duration,
                        ];
                        error_log(JunoStatusCode::WARNING->value . " " . json_encode($transTxn));
                        JunoMetrics::recordTimer("JUNO_DNS_DELAY", $ip, JunoStatusCode::WARNING->value, $duration);
                    }
                } else {
                    $trans['usePingIP'] = 'true';
                }

                $trans['name'] = $this->getHost() . ':' . $this->getPort();

                $this->connector->connect($ip . ':' . $this->getPort())->then(
                    function (ConnectionInterface $connection) use ($trans, $deferred, $level) {
                        $this->connection = $connection;
                        $this->serverAddr = $this->getRaddr($connection);

                        $str = $this->serverAddr . "&w=" . $this->config->getConnectionPoolSize();
                        $trans['raddr'] = $str;
                        $trans['id'] = $this->getConnectID();

                        $numRecv = $this->recvCount;
                        $qsizeStr = $this->requestQueue->size() > 0 ? " qsize=" . $this->requestQueue->size() : "";

                        if ($this->sendCount > 0 || $this->failCount > 0) {
                            $eventLevel = ($this->sendCount > $numRecv || $this->failCount > 0) ? 'info' : 'debug';
                            error_log($eventLevel . " Connected to " . $this->serverAddr . $qsizeStr . " send_count=" . $this->sendCount . " fail_count=" . $this->failCount . " recv_count=" . $numRecv);
                            $trans['send_count'] = $this->sendCount;
                            $trans['fail_count'] = $this->failCount;
                            $trans['recv_count'] = $numRecv;
                        } elseif ($level !== JunoLogLevel::OFF) {
                            error_log("info Connected to " . $this->serverAddr . $qsizeStr);
                        }

                        $this->afterConnect();

                        $trans['status'] = JunoStatusCode::SUCCESS->value;
                        error_log(JunoStatusCode::SUCCESS->value . " " . json_encode($trans));
                        JunoMetrics::recordConnectCount($this->remoteIpAddr, JunoStatusCode::SUCCESS->value, "none");

                        if (!$this->scheduler->isConnected()) {
                            $wait = min($this->getConnectTimeout(), 1000);
                            $this->loop->addTimer($wait / 1000, function () use ($deferred) {
                                if (!$this->isOpened() || $this->scheduler->onEvent(TestEvent::CONNECTION_LOST)) {
                                    $this->scheduler->setDisconnected($this->lockIndex, $this->ownerId);
                                    error_log("Connection closed by server.");
                                    $subTrans = [
                                        'name' => 'JUNO_CONNECTION_LOST',
                                        'server' => $this->serverAddr,
                                        'error' => 'connection_closed_by_server',
                                        'status' => JunoStatusCode::ERROR->value,
                                    ];
                                    error_log("Error : " . json_encode($subTrans));
                                    JunoMetrics::recordErrorCount("JUNO_CONNECTION_LOST", $this->remoteIpAddr, "connection_closed_by_server");
                                    $deferred->resolve(false);
                                } else {
                                    $this->handshakeFail = 0;
                                    $this->afterConnect();
                                    $deferred->resolve(true);
                                }
                            });
                        } else {
                            $deferred->resolve(true);
                        }
                    },
                    function (\Exception $e) use ($trans, $deferred, $startTimeInMs) {
                        $this->scheduler->setDisconnected($this->lockIndex, $this->ownerId);

                        $err = "Connect to " . $this->getHost() . ":" . $this->getPort() .
                            " failed. Config Timeout : " . $this->getConnectTimeout() .
                            ". " . $e->getMessage();

                        error_log($err);
                        $trans['error'] = $err;
                        $trans['status'] = JunoStatusCode::ERROR->value;
                        error_log("Error : " . json_encode($trans));
                        JunoMetrics::recordConnectCount($this->remoteIpAddr, JunoStatusCode::ERROR->value, get_class($e));

                        $deferred->resolve(false);
                    }
                );
            } else {
                $deferred->resolve(true);
            }

            return $deferred->promise();
        } catch (\Exception $e) {
            $trans['error'] = "Error: " . $e->getMessage();
            $trans['status'] = JunoStatusCode::ERROR->value;
            error_log("Error : " . json_encode($trans));
            JunoMetrics::recordConnectCount($this->remoteIpAddr, JunoStatusCode::ERROR->value, get_class($e));

            return reject($e);
        }
    }

    public function send(): PromiseInterface
    {
        return $this->connect()->then(function ($connected) {
            if (!$connected) {
                return Status::CONNECT_FAIL;
            }

            // Fetch a message.
            $entry = $this->requestQueue->dequeue();
            if ($entry === null) {
                return Status::WAIT_FOR_MESSAGE;
            }

            $msg = $entry->msg;
            return $this->connect()->then(function ($reconnected) use ($entry, $msg) {
                if (!$reconnected) {
                    $ok = $this->requestQueue->enqueue($entry);
                    error_log("requeue=" . ($ok ? 'true' : 'false') . " server=" . $this->remoteConfigAddr);
                    return Status::CONNECT_FAIL;
                }

                if ($this->connection === null) {
                    return reject(new \Exception("Connection is not established"));
                }

                $writeResult = $this->connection->write($out);

                // Flush the message.
                return $this->connection->write($msg)->then(
                    function () {
                        $this->sendCount++;
                        error_log("netty send ok.  server=" . $this->serverAddr);
                        return Status::SENT_DONE;
                    },
                    function (\Exception $e) use ($msg) {
                        $this->failCount++;

                        $op = new OperationMessage();
                        $op->readBuf($msg);
                        $mo = $op->getMetaComponent();
                        $requestId = "not_set";
                        $corrId = "not_set";
                        if ($mo !== null) {
                            $requestId = $mo->getRequestIdString();
                            $corrId = $mo->getCorrelationIDString();
                        }

                        error_log("server=" . $this->serverAddr . " req_id=" . $requestId . " corr_id=" . $corrId . " Send failed:" . $e->getMessage());

                        $trans = [
                            'name' => 'JUNO_SEND',
                            'server' => $this->serverAddr,
                            'req_id' => $requestId,
                            'corr_id_' => $corrId,
                            'error' => $e->getMessage(),
                            'status' => JunoStatusCode::ERROR->value,
                        ];
                        error_log("Error : " . json_encode($trans));

                        JunoMetrics::recordErrorCount("JUNO_SEND", $this->remoteIpAddr, get_class($e));

                        return Status::SENT_DONE;
                    }
                );
            });
        });
    }

    private function setNextReconnectDue(): void
    {
        $this->nextReconnectDue = $this->scheduler->selectTimeSlot();
    }

    private function reconnectDue(): bool
    {
        return time() * 1000 > $this->nextReconnectDue;
    }

    private function recycleNow(): PromiseInterface
    {
        if (!$this->reconnectDue()) {
            return resolve(false);
        }

        if (!$this->scheduler->connectOwnerExpired($this->lockIndex, $this->ownerId)) {
            return resolve(false);  // The other worker has not connected yet.
        }

        $DELAY = (int) (2 * $this->config->getResponseTimeout());   // milliseconds
        return new Promise(function ($resolve, $reject) use ($DELAY) {
            $this->loop->addTimer($DELAY / 1000, function () use ($resolve) {
                error_log("Recycle connection ...");
                $resolve(true);
            });
        });
    }

    public function run(): void
    {
        error_log("Start worker_" . $this->id);

        $this->loop->addPeriodicTimer(0.1, function () {
            $this->send()->then(function ($status) {
                switch ($status) {
                    case Status::CONNECT_FAIL:
                        $this->loop->addTimer($this->reconnectWaitTime / 1000, function () {
                            $this->reconnectWaitTime *= 2;
                            if ($this->reconnectWaitTime > $this->MAX_WAIT_TIME) {
                                $this->reconnectWaitTime = $this->MAX_WAIT_TIME;
                            }
                            // Add random adjustment.
                            $this->reconnectWaitTime *= (1 + 0.3 * mt_rand() / mt_getrandmax());
                        });
                        break;

                    case Status::SENT_DONE:
                        $this->recycleNow()->then(function ($shouldRecycle) {
                            if ($shouldRecycle || $this->reconnectNow) {
                                $this->disconnect();
                            }
                        });
                        break;
                }

                if ($this->scheduler->isTestMode()) {
                    $this->scheduler->onException(TestEvent::INTERRUPTED_2);
                    $this->scheduler->onException(TestEvent::EXCEPTION_2);
                }
            });
        });

        $this->loop->run();
    }
}

enum Status
{
    case CONNECT_FAIL;
    case WAIT_FOR_MESSAGE;
    case SENT_DONE;
}