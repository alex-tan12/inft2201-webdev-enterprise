<?php
// Autoload Application\Mail and Application\Page via Composer
require __DIR__ . '/../../vendor/autoload.php';

use Application\Mail;
use Application\Page;
use PDO;
use PDOException;

// Always return JSON
header('Content-Type: application/json');

// Connect to PRODUCTION DB using env vars from docker-compose.yml
$dsn = "pgsql:host=" . getenv('DB_PROD_HOST') . ";dbname=" . getenv('DB_PROD_NAME');

try {
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$mail = new Mail($pdo);
$page = new Page();

// GET /api/mail/  -> list all mail
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page->list($mail->getAllMail());
    exit;
}

// POST /api/mail/ -> create new mail
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON request body
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Validate JSON + required fields
    if (!is_array($data) || empty($data['subject']) || empty($data['body'])) {
        $page->badRequest();
        exit;
    }

    $id = $mail->createMail($data['subject'], $data['body']);

    // 201 Created is ideal for POST
    http_response_code(201);
    echo json_encode(["id" => $id]);
    exit;
}

// If method not supported for this route
$page->badRequest();
