<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP JunoDB Example</title>
</head>
<body>
    <h1>PHP JunoDB Example</h1>

    <?php
    require 'junodb.php';

    $namespace = 'test_ns';
    $key = 'test_key';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['create'])) {
            $value = $_POST['value'];
            $response = create_data($namespace, $key, $value);
            echo "<p>Create Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['read'])) {
            $response = read_data($namespace, $key);
            echo "<p>Read Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['update'])) {
            $value = $_POST['value'];
            $response = update_data($namespace, $key, $value);
            echo "<p>Update Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['delete'])) {
            $response = delete_data($namespace, $key);
            echo "<p>Delete Response: " . json_encode($response) . "</p>";
        }
    }
    ?>

    <form method="post">
        <label for="value">Value:</label>
        <input type="text" id="value" name="value" required>
        <button type="submit" name="create">Create</button>
        <button type="submit" name="read">Read</button>
        <button type="submit" name="update">Update</button>
        <button type="submit" name="delete">Delete</button>
    </form>
</body>
</html>
