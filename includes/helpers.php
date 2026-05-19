<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function old(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) ? trim((string) $source[$key]) : $default;
}

function format_display_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d M Y', $timestamp) : $date;
}

function booking_statuses(): array
{
    return [
        'Pending',
        'Confirm',
        'In Service',
        'Completed',
        'Cancelled',
    ];
}

function status_badge_class(string $status): string
{
    return match (strtolower($status)) {
        'confirm' => 'status-confirm',
        'in service' => 'status-service',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
        default => 'status-pending',
    };
}
