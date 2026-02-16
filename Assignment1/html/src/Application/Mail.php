<?php

namespace Application;

use PDO;

class Mail
{
    protected PDO $pdo;

    // Store PDO connection for database operations
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Insert a new mail entry and return its ID
    public function createMail($subject, $body): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO mail (subject, body) VALUES (?, ?) RETURNING id"
        );
        $stmt->execute([$subject, $body]);

        return (int)$stmt->fetchColumn();
    }

    // Retrieve a single mail entry by ID
    public function getMail(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, subject, body FROM mail WHERE id = ?"
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: false;
    }

    // Retrieve all mail entries
    public function getAllMail(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, subject, body FROM mail ORDER BY id ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update an existing mail entry
    public function updateMail(int $id, $subject, $body): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE mail SET subject = ?, body = ? WHERE id = ?"
        );
        $stmt->execute([$subject, $body, $id]);

        return $stmt->rowCount() === 1;
    }

    // Delete a mail entry by ID
    public function deleteMail(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM mail WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }
}
