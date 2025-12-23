<?php
/**
 * API Helper Functions
 * Backend API ile iletişim için yardımcı fonksiyonlar
 */

$apiBase = getenv('API_BASE_URL') ?: 'http://localhost:3000/api';
if (!defined('API_BASE_URL')) {
    define('API_BASE_URL', $apiBase);
}

if (!defined('PUBLIC_API_URL')) {
    $publicApiUrl = getenv('PUBLIC_API_URL') ?: 'http://localhost:3000';
    define('PUBLIC_API_URL', $publicApiUrl);
}

/**
 * API'ye istek gönder
 */
function apiRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = API_BASE_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => $result
    ];
}

/**
 * Kullanıcı kaydı
 */
function registerUser($username, $email, $password, $full_name) {
    return apiRequest('/auth/register', 'POST', [
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'full_name' => $full_name
    ]);
}

/**
 * Kullanıcı girişi
 */
function loginUser($username, $password) {
    return apiRequest('/auth/login', 'POST', [
        'username' => $username,
        'password' => $password
    ]);
}

/**
 * Mevcut kullanıcı bilgilerini al
 */
function getCurrentUser($token) {
    return apiRequest('/auth/me', 'GET', null, $token);
}

/**
 * Token'dan kullanıcı bilgilerini decode et (JWT)
 */
function decodeToken($token) {
    if (!$token) return null;
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    return json_decode($payload, true);
}

/**
 * Session'a token kaydet
 */
function saveTokenToSession($token, $userData = null) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION['auth_token'] = $token;
    
    // Eğer userData verilmişse onu kullan, yoksa token'dan decode et
    if (!$userData) {
        $userData = decodeToken($token);
    }
    
    if ($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['avatar_path'] = isset($userData['avatar_path']) ? $userData['avatar_path'] : null;
    }
}

/**
 * Session'dan token'ı al
 */
function getTokenFromSession() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return isset($_SESSION['auth_token']) ? $_SESSION['auth_token'] : null;
}

/**
 * Kullanıcı giriş yapmış mı kontrol et
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return isset($_SESSION['auth_token']) && isset($_SESSION['user_id']);
}

/**
 * Kullanıcı admin mi kontrol et
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Kullanıcıyı çıkış yap
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['auth_token']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['avatar_path']);
    session_destroy();
}

/**
 * Profil bilgilerini getir
 */
function getUserProfile($token) {
    return apiRequest('/users/profile', 'GET', null, $token);
}

/**
 * Profil bilgilerini güncelle
 */
function updateUserProfile($token, $full_name, $email) {
    return apiRequest('/users/profile', 'PUT', [
        'full_name' => $full_name,
        'email' => $email
    ], $token);
}

/**
 * Şifre değiştir
 */
function changePassword($token, $current_password, $new_password) {
    return apiRequest('/users/password', 'PUT', [
        'current_password' => $current_password,
        'new_password' => $new_password
    ], $token);
}

/**
 * Avatar yükle (multipart/form-data)
 */
function uploadAvatar($token, $filePath) {
    if (!file_exists($filePath)) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => ['message' => 'File does not exist']
        ];
    }
    
    $url = API_BASE_URL . '/users/avatar';
    
    // cURL ile multipart/form-data gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = [
        'Authorization: Bearer ' . $token
    ];
    
    // PHP 5.5+ için CURLFile kullan
    if (class_exists('CURLFile')) {
        $mimeType = mime_content_type($filePath);
        if (!$mimeType) {
            $mimeType = 'image/jpeg'; // Default
        }
        $cfile = new CURLFile($filePath, $mimeType, basename($filePath));
        $data = ['avatar' => $cfile];
    } else {
        // Eski PHP versiyonları için
        $data = ['avatar' => '@' . $filePath];
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'http_code' => 0,
            'data' => ['message' => 'cURL Error: ' . $curlError]
        ];
    }
    
    if (!$response) {
        return [
            'success' => false,
            'http_code' => $httpCode,
            'data' => ['message' => 'No response from server']
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'http_code' => $httpCode,
            'data' => ['message' => 'Invalid JSON response: ' . $response]
        ];
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => $result
    ];
}

