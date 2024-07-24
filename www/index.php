<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP JunoDB Example</title>
</head>
<body>
    <h1>PHP JunoDB Example</h1>

    <?php

        // $config = new JunoConfig([
        //     'application_name' => 'JunoTest',
        //     'record_namespace' => 'JunoNS',
        //     'server.host' => 'juno-server.example.com',
        //     'server.port' => 8080,
        //     // Konfigurasi lainnya
        // ]);
        
        // $junoClient = JunoClientFactory::newJunoClient($config);
        
        // // Penggunaan
        // $junoClient->set('key', 'value');
        // $value = $junoClient->get('key');

    ?>

    <form method="post">
        <label for="key">Key:</label>
        <input type="text" id="key" name="key" required>
        <br>
        <label for="value">Value:</label>
        <input type="text" id="value" name="value">
        <br>
        <button type="submit" name="create">Create</button>
        <button type="submit" name="read">Read</button>
        <button type="submit" name="update">Update</button>
        <button type="submit" name="set">Set</button>
        <button type="submit" name="delete">Delete</button>
    </form>
</body>
</html>
