<?php
// csrf.php - CSRF protection functions
// Start session only if not already active
if (function_exists('session_status')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
} else {
    if (session_id() === '') {
        session_start();
    }
}

function generate_csrf_token(){
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token){
    if(empty($_SESSION['csrf_token']) || empty($token)){
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(){
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}