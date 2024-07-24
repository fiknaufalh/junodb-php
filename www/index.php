<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP JunoDB Example</title>
</head>
<body>
    <h1>PHP JunoDB Example</h1>

    <?php
        class JunoDBClient {
            private $host;
            private $port;
            private $socket;

            public function __construct($host, $port) {
                $this->host = $host;
                $this->port = $port;
            }

            private function connect() {
                $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
                if (!$this->socket) {
                    throw new Exception("Could not connect to JunoDB: $errstr ($errno)");
                }
            }

            private function disconnect() {
                fclose($this->socket);
            }

            private function sendRequest($request) {
                fwrite($this->socket, $request);
                $response = '';
                while (!feof($this->socket)) {
                    $response .= fgets($this->socket, 128);
                }
                return $response;
            }

            public function create($key, $value, $ttl) {
                $this->connect();
                $request = "CREATE {$key} {$value} {$ttl}\n";
                $response = $this->sendRequest($request);
                $this->disconnect();
                return $response;
            }

            public function get($key) {
                $this->connect();
                $request = "GET {$key}\n";
                $response = $this->sendRequest($request);
                $this->disconnect();
                return $response;
            }

            public function update($key, $value, $ttl) {
                $this->connect();
                $request = "UPDATE {$key} {$value} {$ttl}\n";
                $response = $this->sendRequest($request);
                $this->disconnect();
                return $response;
            }

            public function delete($key) {
                $this->connect();
                $request = "DELETE {$key}\n";
                $response = $this->sendRequest($request);
                $this->disconnect();
                return $response;
            }
        }

        $juno_server_host = '10.181.133.164';
        $juno_server_port = 5080;
        
        try {
            $client = new JunoDBClient('juno-server-host', 5000);
            $client->create('testKey', 'testValue', 3600);
            echo "Key: testKey, Value [must be testValue]: ", $client->get('testKey');
            $client->update('testKey', 'newValue', 3600);
            echo "Key: testKey, (New) Value [must be newValue]: ", $client->get('testKey');
            $client->delete('testKey');
            echo "Key: testKey, Value [must be null/error]: ", $client->get('testKey');
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>

</body>
</html>
