<?php

declare(strict_types=1);

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('TEAMDIMLER_DB_HOST') ?: '127.0.0.1';
    $database = getenv('TEAMDIMLER_DB_NAME') ?: 'teamdimler';
    $username = getenv('TEAMDIMLER_DB_USER') ?: 'root';
    $password = getenv('TEAMDIMLER_DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

    try {
        $connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        throw new RuntimeException('No se pudo conectar con la base Team DimLer. Iniciá MySQL en XAMPP e importá database/schema.sql.');
    }

    return $connection;
}
