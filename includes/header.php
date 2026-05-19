<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Fleet Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark topbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">GSS Fleet Management</a>
            <span class="navbar-text text-white-50 small">Web-based booking and vehicle assignment system</span>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
