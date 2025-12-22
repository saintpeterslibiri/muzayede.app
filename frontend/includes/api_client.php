<?php
// =====================================================
// API CLIENT - PHP to Node.js API Bridge
// =====================================================

$apiBase = getenv('API_BASE_URL') ?: 'http://localhost:3000/api';
define('API_BASE', $apiBase);

// -----------------------------------------------------
// GET Request
// -----------------------------------------------------
function api_get($endpoint) {
    $url = API_BASE . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = ["Content-Type: application/json"];
    
    // Add token if exists in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['auth_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['auth_token'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $res = curl_exec($ch);

    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $err];
    }

    curl_close($ch);

    $json = json_decode($res, true);
    if ($json === null) {
        return ["success" => false, "error" => "Invalid JSON from API", "raw" => $res];
    }

    return $json;
}

// -----------------------------------------------------
// POST Request
// -----------------------------------------------------
function api_post($endpoint, $payload = []) {
    $url = API_BASE . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $headers = ["Content-Type: application/json"];
    
    // Add token if exists in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['auth_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['auth_token'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $res = curl_exec($ch);

    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $err];
    }

    curl_close($ch);

    $json = json_decode($res, true);
    if ($json === null) {
        return ["success" => false, "error" => "Invalid JSON from API", "raw" => $res];
    }

    return $json;
}

// -----------------------------------------------------
// POST Request with Multipart (File Upload)
// -----------------------------------------------------
function api_post_multipart($endpoint, $fields = [], $files = []) {
    $url = API_BASE . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Prepare headers (Content-Type will be set automatically by curl for multipart)
    $headers = [];
    
    // Add token if exists in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['auth_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['auth_token'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Prepare payload
    $payload = $fields;
    
    // Add files
    foreach ($files as $key => $fileInfo) {
        // fileInfo should be ['path' => '/tmp/php...', 'name' => 'filename.jpg', 'type' => 'image/jpeg']
        if (isset($fileInfo['tmp_name']) && file_exists($fileInfo['tmp_name'])) {
            $payload[$key] = new CURLFile($fileInfo['tmp_name'], $fileInfo['type'], $fileInfo['name']);
        }
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $res = curl_exec($ch);

    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $err];
    }

    curl_close($ch);

    $json = json_decode($res, true);
    if ($json === null) {
        return ["success" => false, "error" => "Invalid JSON from API", "raw" => $res];
    }

    return $json;
}

// -----------------------------------------------------
// PUT Request
// -----------------------------------------------------
function api_put($endpoint, $payload = []) {
    $url = API_BASE . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    
    $headers = ["Content-Type: application/json"];
    
    // Add token if exists in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['auth_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['auth_token'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $res = curl_exec($ch);

    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $err];
    }

    curl_close($ch);

    $json = json_decode($res, true);
    if ($json === null) {
        return ["success" => false, "error" => "Invalid JSON from API", "raw" => $res];
    }

    return $json;
}

// -----------------------------------------------------
// DELETE Request
// -----------------------------------------------------
function api_delete($endpoint) {
    $url = API_BASE . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
    $headers = ["Content-Type: application/json"];
    
    // Add token if exists in session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['auth_token'])) {
        $headers[] = "Authorization: Bearer " . $_SESSION['auth_token'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $res = curl_exec($ch);

    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ["success" => false, "error" => $err];
    }

    curl_close($ch);

    $json = json_decode($res, true);
    if ($json === null) {
        return ["success" => false, "error" => "Invalid JSON from API", "raw" => $res];
    }

    return $json;
}