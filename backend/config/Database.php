<?php

class Database {
    private static $host = "localhost";
    private static $db_name = "db_core";
    private static $username = "root";
    private static $password = "";
    private static $conn = null;

    public static function conectar() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8",
                    self::$username,
                    self::$password
                );

                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die(json_encode([
                    "success" => false,
                    "message" => "Erro ao conectar com o banco."
                ]));
            }
        }

        return self::$conn;
    }
}