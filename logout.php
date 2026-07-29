<?php

declare(strict_types=1);

session_start();

session_start();

$_SESSION = [];

$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'You have been logged out successfully.'
];

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

}

session_destroy();

header('Location: login.php');
exit;