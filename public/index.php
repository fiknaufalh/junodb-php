<?php
require __DIR__ . '/../vendor/autoload.php';

use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Util\JunoLogger;
use JunoPhpClient\Util\JunoClientFactory;
use JunoPhpClient\Exception\JunoException;

// Debugging
echo "JUNO_HOST: " . getenv('JUNO_HOST') . "<br/>";
echo "JUNO_PORT: " . getenv('JUNO_PORT') . "<br/>";

// Load configuration
$config = new JunoConfig(require __DIR__ . '/../config/juno_config.php');

// More debugging
echo "Config host: " . $config->get('server.host') . "<br/>";
echo "Config port: " . $config->get('server.port') . "<br/>";

// Setup logger
$logger = new JunoLogger(__DIR__ . '/../logs/juno.log');

// Create client
$client = JunoClientFactory::newJunoClient($config, $logger);

// Use the client
try {
    // Set a value
    $client->set('test_key', 'Hello from Docker!');

    // Get the value
    $value = $client->get('test_key');

    echo "Value from JunoDB: " . $value;
} catch (JunoException $e) {
    echo "Error: " . $e->getMessage();
}
