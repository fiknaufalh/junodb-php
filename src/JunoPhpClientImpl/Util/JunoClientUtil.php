<?php

namespace JunoPhpClient\Util;

use JunoPhpClient\Client\JunoClientConfigHolder;
use JunoPhpClient\Client\IO\JunoRequest;
use JunoPhpClient\Client\IO\OperationStatus;
use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Exception\JunoInputException;
use JunoPhpClient\IO\Protocol\JunoMessage;
use JunoPhpClient\IO\Protocol\OperationType;
use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Client\ServerOperationStatus;
use JunoPhpClient\IO\Protocol\MetaMessageTagAndType;
use JunoPhpClient\IO\Protocol\FieldType;
use JunoPhpClient\IO\Protocol\MessageHeader;
use JunoPhpClient\IO\Protocol\MessageOpcode;
use JunoPhpClient\IO\Protocol\MessageRQ;
use JunoPhpClient\IO\Protocol\MessageType;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;


class JunoClientUtil
{
    private const NULL_OR_EMPTY_KEY = "null_or_empty_key";
    private const MAX_KEY_SIZE_EXCEEDED = "key_size_exceeded";
    private const PAYLOAD_EXCEEDS_MAX_LIMIT = "payload_size_exceeded";
    private const ZERO_OR_NEGATIVE_TTL = "invalid_ttl";
    private const TTL_EXCEEDS_MAX = "ttl_exceeded_max";
    private const ZERO_OR_NEGATIVE_VERSION = "invalid_version";
    private $logger = new Logger("JunoClientUtil");

    public static function throwIfNull($value, string $name): void
    {
        if ($value === null) {
            throw new \InvalidArgumentException($name . " must not be null");
        }
    }

    public static function checkForRetry(OperationStatus $status): bool
    {
        return in_array($status, [
            OperationStatus::RecordLocked,
            OperationStatus::TTLExtendFailure,
            OperationStatus::InternalError,
            OperationStatus::NoStorage
        ]);
    }

    public static function validateInput(JunoRequest $req, OperationType $opType, JunoClientConfigHolder $configHolder): JunoMessage
    {
        if (empty($req->key())) {
            throw new \InvalidArgumentException("The Document key must not be null or empty", new JunoInputException(self::NULL_OR_EMPTY_KEY));
        }

        $recordTtl = $req->getTimeToLiveSec() ?? $configHolder->getDefaultLifetimeSecs();
        $junoMsg = new JunoMessage($req->key(), $req->getValue(), $req->getVersion(), 0, $recordTtl, $opType);

        if (strlen($req->key()) > $configHolder->getMaxKeySize()) {
            throw new \InvalidArgumentException("The Document key must not be larger than " . $configHolder->getMaxKeySize() . " bytes",
                new JunoInputException(self::MAX_KEY_SIZE_EXCEEDED));
        }

        if ($opType !== OperationType::Get && $opType !== OperationType::Destroy) {
            $payload = $req->getValue() ?? '';
            if (strlen($payload) > $configHolder->getMaxValueSize()) {
                $error = "The Document Value must not be larger than " . $configHolder->getMaxValueSize() . " bytes. Current value size=" . strlen($payload);
                throw new \InvalidArgumentException($error, new JunoInputException(self::PAYLOAD_EXCEEDS_MAX_LIMIT));
            }
            $junoMsg->setValue($payload);
        }

        if ($recordTtl < 0) {
            $error = "The Document's TTL cannot be negative. Current lifetime=" . $recordTtl;
            throw new \InvalidArgumentException($error, new JunoInputException(self::ZERO_OR_NEGATIVE_TTL));
        } elseif ($recordTtl > $configHolder->getMaxLifetimeSecs()) {
            $error = "Invalid lifetime. current lifetime=" . $recordTtl . ", max configured lifetime=" . $configHolder->getMaxLifetimeSecs();
            throw new \InvalidArgumentException($error, new JunoInputException(self::TTL_EXCEEDS_MAX));
        }

        switch ($opType) {
            case OperationType::Create:
                if ($recordTtl == 0) {
                    $error = "The Document's TTL cannot be 0 for Create operation.";
                    throw new \InvalidArgumentException($error, new JunoInputException(self::ZERO_OR_NEGATIVE_TTL));
                }
                break;
            case OperationType::CompareAndSet:
                if ($req->getVersion() < 1) {
                    $error = "The Document version cannot be less than 1. Current version=" . $req->getVersion();
                    throw new \InvalidArgumentException($error, new JunoInputException(self::ZERO_OR_NEGATIVE_VERSION));
                }
                break;
        }

        $junoMsg->setNameSpace($configHolder->getRecordNamespace());
        $junoMsg->setApplicationName($configHolder->getApplicationName());
        $junoMsg->setReqId(Uuid::uuid4());

        return $junoMsg;
    }

    public static function createOperationMessage(JunoMessage $junoMsg, int $opaque): OperationMessage
    {
        $opMsg = new OperationMessage();
        $header = new MessageHeader($logger);
        $code = self::getMessageOpcode($junoMsg->getOpType());

        $header->setMsgType(MessageType::OperationalMessage->value);
        $header->setFlags(0);
        $header->setMessageRQ(MessageRQ::TwoWayRequest->value);
        $header->setOpcode($code->value);
        $header->setOpaque($opaque);
        $header->setStatus(ServerOperationStatus::BadMsg->getCode());
        $opMsg->setHeader($header);

        // Implement the rest of the message creation logic here
        // This will involve creating MetaOperationMessage and PayloadOperationMessage
        // and setting them in the OperationMessage

        return $opMsg;
    }

    private static function getMessageOpcode(OperationType $opType): MessageOpcode
    {
        return match($opType) {
            OperationType::Create => MessageOpcode::Create,
            OperationType::Destroy => MessageOpcode::Destroy,
            OperationType::Get => MessageOpcode::Get,
            OperationType::Set => MessageOpcode::Set,
            OperationType::Update, OperationType::CompareAndSet => MessageOpcode::Update,
            default => throw new JunoException("Internal Error, invalid type: " . $opType->name),
        };
    }

    public static function decodeOperationMessage(OperationMessage $opMsg, string $key, JunoClientConfigHolder $configHolder): JunoMessage
    {
        $message = new JunoMessage();

        // Decode the Meta component
        if ($opMsg->getMetaComponent() !== null) {
            foreach ($opMsg->getMetaComponent()->getFieldList() as $field) {
                switch ($field->getFieldType()) {
                    case FieldType::CreationTime:
                        $message->setCreationTime($field->getContent());
                        break;
                    case FieldType::TimeToLive:
                        $message->setTimeToLiveSec($field->getContent());
                        break;
                    case FieldType::Version:
                        $message->setVersion($field->getContent());
                        break;
                    case FieldType::RequestHandlingTime:
                        $message->setReqHandlingTime($field->getContent());
                        break;
                }
            }
        }

        // Decode the Header
        $status = $opMsg->getHeader()->getStatus();
        $message->setStatus(ServerOperationStatus::get($status));

        // Decode the Payload Component
        $pp = $opMsg->getPayloadComponent();
        $message->setValue($pp !== null && $pp->getValueLength() !== 0 ? $pp->getValue() : '');
        $message->setNameSpace($pp !== null ? $pp->getNamespace() : '');
        $message->setKey($key);

        // Populate the total message size for this operation
        $message->setMessageSize($opMsg->getHeader()->getMessageSize());

        return $message;
    }
}