<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Application\Mail;
use Application\Page;
use PDO;
use PDOException;

header('Content-Type: application/json');

// Extract the ID from the URL: /api/mail/{id}
$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($uri, '/'));
$id = (int) end($parts);

// Validate ID
$page = new Page();
if ($id <= 0) {
    $page->badRequest();
    exit;
}

// Connect to PRODUCTION DB
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

// GET /api/mail/{id} -> fetch one
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $row = $mail->getMail($id);

    if (!$row) {
        $page->notFound();
        exit;
    }

    $page->item($row);
    exit;
}

// PUT /api/mail/{id} -> update
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Must exist first (required for proper 404)
    if (!$mail->getMail($id)) {
        $page->notFound();
        exit;
    }

    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Validate input
    if (!is_array($data) || empty($data['subject']) || empty($data['body'])) {
        $page->badRequest();
        exit;
    }

    $ok = $mail->updateMail($id, $data['subject'], $data['body']);

    // Should be true if row exists; but keep correct semantics
    if (!$ok) {
        $page->notFound();
        exit;
    }

    $page->item($mail->getMail($id));
    exit;
}

// DELETE /api/mail/{id} -> delete
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Must exist first for proper 404
    if (!$mail->getMail($id)) {
        $page->notFound();
        exit;
    }

    $ok = $mail->deleteMail($id);

    if (!$ok) {
        $page->notFound();
        exit;
    }

    // Return a confirmation message
    http_response_code(200);
    echo json_encode(["deleted" => true]);
    exit;
}

// If method not supported
$page->badRequest();
