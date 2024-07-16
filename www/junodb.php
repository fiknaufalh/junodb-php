<?php
function junodb_request($method, $url, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['error' => $error_msg];
    }

    curl_close($ch);

    $decoded_response = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => json_last_error_msg(), 'response' => $response];
    }

    return $decoded_response;
}

function create_data($key, $value) {
    $url = 'http://10.181.133.164:8181/samplejuno/recordcreate';
    $data = ['key' => $key, 'value' => $value];
    return junodb_request('POST', $url, $data);
}

function read_data($key) {
    $url = 'http://10.181.133.164:8181/samplejuno/reactget/' . urlencode($key);
    return junodb_request('GET', $url);
}

function update_data($key, $value) {
    $url = 'http://10.181.133.164:8181/samplejuno/reactset/';
    $data = ['key' => $key, 'value' => $value];
    return junodb_request('POST', $url, $data);
}

function set_data($key, $value) {
    $url = 'http://10.181.133.164:8181/samplejuno/reactupdate/';
    $data = ['key' => $key, 'value' => $value];
    return junodb_request('POST', $url, $data);
}

function delete_data($key) {
    $url = 'http://10.181.133.164:8181/samplejuno/reactdelete/' . urlencode($key);
    return junodb_request('DELETE', $url);
}
?>
