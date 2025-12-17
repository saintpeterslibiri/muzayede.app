<?php
// =====================================================
// API CLIENT - PHP to Node.js API Bridge
// =====================================================

define('API_BASE', 'http://localhost:3000/api');

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