<?php

namespace JunoPhpClient\Client;

use JunoPhpClient\Client\Impl\JunoClientImpl;
use JunoPhpClient\Client\Impl\JunoAsyncClientImpl;
use JunoPhpClient\Conf\JunoPropertiesProvider;
use JunoPhpClient\Util\JunoClientUtil;

class JunoClientFactory
{
    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    /**
     * Instantiates a JunoClient implementation using the Juno config 
     * properties from the given URL.
     *
     * @param string $url URL corresponding to the Juno config properties file. 
     * Cannot be null.
     * 
     * @return JunoClient instance initialized with the properties from the 
     * given URL.
     */
    public static function newJunoClient(string $url): JunoClient
    {
        $props = new JunoPropertiesProvider($url);
        return self::newJunoClientFromProps($props);
    }

    /**
     * Instantiates a JunoClient implementation using the given Juno
     * property provider.
     * 
     * @param JunoPropertiesProvider $junoProps Juno configuration properties.
     * Cannot be null.
     * 
     * @return JunoClient instance initialized with the properties from the given
     * Juno configuration properties.
     */
    public static function newJunoClientFromProps(JunoPropertiesProvider $junoProps): JunoClient
    {
        JunoClientUtil::throwIfNull($junoProps, "Juno Properties");
        $cfgHldr = new JunoClientConfigHolder($junoProps);
        return new JunoClientImpl($cfgHldr, null);
    }

    /**
     * Instantiates a JunoClient implementation using the given Juno
     * property provider and client supplied SSLContext.
     * 
     * @param JunoPropertiesProvider $junoProps Juno configuration properties. Cannot be null.
     * @param resource $sslCtx Client supplied SSL context
     * 
     * @return JunoClient instance initialized with the properties from the given
     * Juno configuration properties.
     */
    public static function newJunoClientWithSSL(JunoPropertiesProvider $junoProps, $sslCtx): JunoClient
    {
        JunoClientUtil::throwIfNull($junoProps, "Juno Properties");
        $cfgHldr = new JunoClientConfigHolder($junoProps);
        return new JunoClientImpl($cfgHldr, $sslCtx);
    }

    /**
     * Instantiates a JunoAsyncClient implementation using the Juno config 
     * properties from the given URL.
     *
     * @param string $url URL corresponding to the Juno config properties file. 
     * Cannot be null.
     * 
     * @return JunoAsyncClient instance initialized with the properties from the
     * given URL. This is not threadsafe.
     */
    public static function newJunoAsyncClient(string $url): JunoAsyncClient
    {
        $props = new JunoPropertiesProvider($url);
        return self::newJunoAsyncClientFromProps($props);
    }

    /**
     * Instantiates a JunoAsyncClient implementation using the given Juno 
     * property provider.
     * 
     * @param JunoPropertiesProvider $junoProps Juno configuration properties.
     * Cannot be null.
     * 
     * @return JunoAsyncClient instance initialized with the properties from the given
     * Juno configuration properties.
     */
    public static function newJunoAsyncClientFromProps(JunoPropertiesProvider $junoProps): JunoAsyncClient
    {
        JunoClientUtil::throwIfNull($junoProps, "Juno Properties");
        $cfgHldr = new JunoClientConfigHolder($junoProps);
        return new JunoAsyncClientImpl($cfgHldr, null);
    }

    /**
     * Instantiates a JunoAsyncClient implementation using the given Juno 
     * property provider and client supplied SSLContext.
     * 
     * @param JunoPropertiesProvider $junoProps Juno configuration properties. Cannot be null.
     * @param resource $sslCtx Client supplied SSL context
     * 
     * @return JunoAsyncClient instance initialized with the properties from the given
     * Juno configuration properties.
     */
    public static function newJunoAsyncClientWithSSL(JunoPropertiesProvider $junoProps, $sslCtx): JunoAsyncClient
    {
        JunoClientUtil::throwIfNull($junoProps, "Juno Properties");
        $cfgHldr = new JunoClientConfigHolder($junoProps);
        return new JunoAsyncClientImpl($cfgHldr, $sslCtx);
    }
}