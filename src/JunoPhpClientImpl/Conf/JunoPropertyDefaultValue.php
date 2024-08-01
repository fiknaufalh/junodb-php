<?php

namespace JunoPhpClient\Conf;

class JunoPropertyDefaultValue
{
    public const responseTimeoutMS = 200;
    public const connectionTimeoutMS = 200;
    public const connectionPoolSize = 1;
    public const connectionLifetimeMS = 30000;
    public const defaultLifetimeS = 259200;
    
    public const maxResponseTimeoutMS = 5000;
    public const maxConnectionLifetimeMS = 30000;
    public const maxconnectionTimeoutMS = 5000;
    public const maxKeySizeB = 128;
    public const maxValueSizeB = 204800;
    public const maxNamespaceLength = 64;
    public const maxConnectionPoolSize = 3;
    public const maxLifetimeS = 259200;

    public const host = '';
    public const port = 0;
    public const appName = ''; 
    public const recordNamespace = '';
    
    public const useSSL = true;
    public const reconnectOnFail = false;
    public const usePayloadCompression = false;
    public const operationRetry = false;
    public const byPassLTM = true;
}