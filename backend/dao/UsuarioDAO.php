<?php

require_once __DIR__ . '/../config/Database.php';

class UsuarioDAO {

    public function buscarPorEmail($email) {
        $conn = Database::conectar();

        $sql = "SELECT id, nome, email, senha_hash, tipo, ativo 
                FROM usuarios 
                WHERE email = :email 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}