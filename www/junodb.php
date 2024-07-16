<?php
function junodb_request($method, $url, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function create_data($namespace, $key, $value) {
    $url = 'http://proxy:8080/create';
    $data = ['namespace' => $namespace, 'key' => $key, 'value' => $value];
    return junodb_request('POST', $url, $data);
}

function read_data($namespace, $key) {
    $url = 'http://proxy:8080/get?namespace=' . urlencode($namespace) . '&key=' . urlencode($key);
    return junodb_request('GET', $url);
}

function update_data($namespace, $key, $value) {
    $url = 'http://proxy:8080/update';
    $data = ['namespace' => $namespace, 'key' => $key, 'value' => $value];
    return junodb_request('POST', $url, $data);
}

function delete_data($namespace, $key) {
    $url = 'http://proxy:8080/destroy';
    $data = ['namespace' => $namespace, 'key' => $key];
    return junodb_request('POST', $url, $data);
}
?>
