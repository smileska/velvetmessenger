<?php
const BASE_PATH = __DIR__ .'/';

function urlIs($value)
{
    return $_SERVER["REQUEST_URI"] === $value;
}
function base_path($path) {
    return BASE_PATH . $path;
}

function view($path, $data = []) {
    extract($data);
    ob_start();
    $fullPath = base_path('src/Views/' . $path); // Corrected path
    if (!file_exists($fullPath)) {
        echo "View file not found: " . $fullPath; // Debug output
        return ''; // Return empty string or handle error gracefully
    }
    include $fullPath;
    return ob_get_clean();
}
function viewIMG($path, $data = []) {
    extract($data);
    ob_start();
    $fullPath = base_path('ui/icons/' . $path); // Corrected path
    if (!file_exists($fullPath)) {
        echo "View file not found: " . $fullPath; // Debug output
        return ''; // Return empty string or handle error gracefully
    }
    include $fullPath;
    return ob_get_clean();
}

function dd($val) {
    echo"<pre>";
    var_dump($val);
    echo"</pre>";
    die();
}


function login($user) {
    $_SESSION['username'] = $user['username'];
    session_regenerate_id(true);
}


function logout() {
    $_SESSION=[];
    session_destroy();
    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}


