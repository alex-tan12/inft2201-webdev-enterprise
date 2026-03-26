<?php
require __DIR__ . '/../../../autoload.php';

use Application\Database;
use Application\Page;
use Application\Verifier;
use PDOException;

$database = new Database('prod');
$page = new Page();
$db = $database->getDb();

// Verify JWT from Authorization header
try {
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    $verifier = new Verifier();
    $verifier->decode($_SERVER['HTTP_AUTHORIZATION']);

    $userId = $verifier->userId;
    $role = $verifier->role;

} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (
        !is_array($data) ||
        !array_key_exists('name', $data) ||
        !array_key_exists('message', $data)
    ) {
        $page->badRequest();
        exit;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO mail (name, message, userId)
            VALUES (:name, :message, :userId)
        ");

        $stmt->execute([
            'name' => $data['name'],
            'message' => $data['message'],
            'userId' => $userId
        ]);

        $page->item(["id" => $db->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {
        if ($role === 'admin') {
            $stmt = $db->query("
                SELECT id, name, message, userId
                FROM mail
                ORDER BY id
            ");
        } else {
            $stmt = $db->prepare("
                SELECT id, name, message, userId
                FROM mail
                WHERE userId = :userId
                ORDER BY id
            ");
            $stmt->execute(['userId' => $userId]);
        }

        $page->item($stmt->fetchAll(\PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }

} else {
    $page->badRequest();
}