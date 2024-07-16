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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $key = $_POST['key'];

        if (isset($_POST['create'])) {
            $value = $_POST['value'];
            $response = create_data($key, $value);
            echo "<p>Create Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['read'])) {
            $response = read_data($key);
            echo "<p>Read Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['update'])) {
            $value = $_POST['value'];
            $response = update_data($key, $value);
            echo "<p>Update Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['set'])) {
            $value = $_POST['value'];
            $response = set_data($key, $value);
            echo "<p>Set Response: " . json_encode($response) . "</p>";
        } elseif (isset($_POST['delete'])) {
            $response = delete_data($key);
            echo "<p>Delete Response: " . json_encode($response) . "</p>";
        }
    }
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
