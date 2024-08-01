<?php

namespace JunoPhpClient\Conf;

class JunoProperties
{
  public const RESPONSE_TIMEOUT = 'juno.response.timeout_msec';
  public const CONNECTION_TIMEOUT = 'juno.connection.timeout_msec';
  public const DEFAULT_LIFETIME = 'juno.default_record_lifetime_sec';
  public const CONNECTION_LIFETIME = 'juno.connection.recycle_duration_msec';
  public const CONNECTION_POOL_SIZE = 'juno.connection.pool_size';
  public const RECONNECT_ON_FAIL = 'juno.connection.reconnect_on_fail';
  public const HOST = 'juno.server.host';
  public const PORT = 'juno.server.port';
  public const APP_NAME = 'juno.application_name';
  public const RECORD_NAMESPACE = 'juno.record_namespace';
  public const USE_SSL = 'juno.useSSL';
  public const USE_PAYLOADCOMPRESSION = 'juno.usePayloadCompression';
  public const ENABLE_RETRY = 'juno.operation.retry';
  public const BYPASS_LTM = 'juno.connection.byPassLTM';
  public const CONFIG_PREFIX = 'prefix';

  public const MAX_LIFETIME = 'juno.max_record_lifetime_sec';
  public const MAX_KEY_SIZE = 'juno.max_key_size';
  public const MAX_VALUE_SIZE = 'juno.max_value_size';
  public const MAX_RESPONSE_TIMEOUT = 'juno.response.max_timeout_msec';
  public const MAX_CONNECTION_TIMEOUT = 'juno.connection.max_timeout_msec';
  public const MAX_CONNECTION_LIFETIME = 'juno.connection.max_recycle_duration_msec';
  public const MAX_CONNECTION_POOL_SIZE = 'juno.connection.max_pool_size';
  public const MAX_NAMESPACE_LENGTH = 'juno.max_record_namespace_length';
}
