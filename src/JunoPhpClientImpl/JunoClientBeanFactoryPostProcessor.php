<?php

namespace JunoPhpClient;

use JunoPhpClient\Client\JunoClient;
use JunoPhpClient\Client\JunoAsyncClient;
use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Exception\JunoException;

class JunoClientBeanFactoryPostProcessor
{
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function postProcessBeanFactory($container)
    {
        $config = $container->get(JunoConfig::class);
        $qualifiedBeanList = [];
        $nonQualifiedBeanList = [];

        foreach ([JunoClient::class, JunoAsyncClient::class] as $junoClass) {
            $this->registerBean($container, $config, $qualifiedBeanList, $nonQualifiedBeanList, $junoClass);
        }
    }

    private function registerBean($container, $config, &$qualifiedBeanList, &$nonQualifiedBeanList, $junoClass)
    {
        $provider = $this->getJunoPropertiesProvider(null, $config);
        $provider->setConfig($config);
        
        $junoClient = $this->createJunoBean($provider, $junoClass);
        $container->set($junoClass, $junoClient);

        foreach ($qualifiedBeanList as $qualifiedBean) {
            $provider = $this->getJunoPropertiesProvider($qualifiedBean, $config);
            $provider->setConfig($config);
            $junoClient = $this->createJunoBean($provider, $junoClass);
            $container->set($qualifiedBean . $junoClass, $junoClient);
        }

        if (!empty($nonQualifiedBeanList)) {
            $provider = $this->getJunoPropertiesProvider(null, $config);
            $provider->setConfig($config);
            $junoClient = $this->createJunoBean($provider, $junoClass);
            foreach ($nonQualifiedBeanList as $nonQualifiedBean) {
                $container->set($nonQualifiedBean . $junoClass, $junoClient);
            }
        }
    }

    private function createJunoBean($provider, $junoClass)
    {
        if ($junoClass === JunoClient::class) {
            return JunoClientFactory::newJunoClient($provider);
        } elseif ($junoClass === JunoAsyncClient::class) {
            return JunoClientFactory::newJunoAsyncClient($provider);
        }
        return null;
    }

    private function getJunoPropertiesProvider($qualifier, $configuration)
    {
        $properties = [];
        $propertiesProvider = null;

        if ($qualifier === null) {
            $keys = $configuration->getKeys("juno");
            foreach ($keys as $key) {
                $properties[$key] = $configuration->get($key);
            }
        } else {
            $subset = $configuration->getSubset($qualifier);
            $keys = $subset->getKeys("juno");
            foreach ($keys as $key) {
                $properties[$key] = $subset->get($key);
            }
        }

        if (empty($properties)) {
            throw new JunoException("No Juno Client properties could be found for qualifying properties: " . $qualifier);
        }

        $propertiesProvider = new JunoPropertiesProvider($properties);
        return $propertiesProvider;
    }
}