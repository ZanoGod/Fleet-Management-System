<?php

declare(strict_types=1);

final class Database
{
    private const HOST = '127.0.0.1';
    private const PORT = 3306;
    private const NAME = 'fleet-beta';
  //      private const NAME = 'fleet-beta';
    private const USERNAME = 'root';
    private const PASSWORD = '';

    public static function connect(): mysqli
    {
        $mysqli = new mysqli(
            self::HOST,
            self::USERNAME,
            self::PASSWORD,
            self::NAME,
            self::PORT
        );

        if ($mysqli->connect_errno !== 0) {
            throw new RuntimeException(
                'Database connection failed: ' . $mysqli->connect_error
            );
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }
}
