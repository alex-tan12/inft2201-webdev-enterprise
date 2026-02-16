<?php

use PHPUnit\Framework\TestCase;
use Application\Mail;

class MailTest extends TestCase
{
    protected PDO $pdo;

    // Runs before every test to create a fresh test database table
    protected function setUp(): void
    {
        $dsn = "pgsql:host=" . getenv('DB_TEST_HOST') . ";dbname=" . getenv('DB_TEST_NAME');
        $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Reset table to ensure clean state
        $this->pdo->exec("DROP TABLE IF EXISTS mail;");
        $this->pdo->exec("
            CREATE TABLE mail (
                id SERIAL PRIMARY KEY,
                subject TEXT NOT NULL,
                body TEXT NOT NULL
            );
        ");
    }

    // Helper method to insert a row directly into DB for testing
    private function seedMail(string $subject, string $body): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO mail (subject, body) VALUES (?, ?) RETURNING id");
        $stmt->execute([$subject, $body]);
        return (int)$stmt->fetchColumn();
    }

    // Test creating a new mail entry
    public function testCreateMail(): void
    {
        $mail = new Mail($this->pdo);

        $id = $mail->createMail("Alice", "Hello world");

        $this->assertIsInt($id);
        $this->assertEquals(1, $id);
    }

    // Test retrieving an existing mail entry by ID
    public function testGetMailReturnsRow(): void
    {
        $id = $this->seedMail("Subject 1", "Body 1");
        $mail = new Mail($this->pdo);

        $row = $mail->getMail($id);

        $this->assertIsArray($row);
        $this->assertEquals($id, (int)$row["id"]);
        $this->assertEquals("Subject 1", $row["subject"]);
        $this->assertEquals("Body 1", $row["body"]);
    }

    // Test retrieving a non-existent mail entry
    public function testGetMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);

        $row = $mail->getMail(9999);

        $this->assertFalse($row);
    }

    // Test retrieving all mail entries
    public function testGetAllMailReturnsAllRows(): void
    {
        $this->seedMail("S1", "B1");
        $this->seedMail("S2", "B2");
        $mail = new Mail($this->pdo);

        $rows = $mail->getAllMail();

        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
        $this->assertEquals("S1", $rows[0]["subject"]);
        $this->assertEquals("S2", $rows[1]["subject"]);
    }

    // Test updating an existing mail entry
    public function testUpdateMailUpdatesExistingRow(): void
    {
        $id = $this->seedMail("Old", "Old body");
        $mail = new Mail($this->pdo);

        $ok = $mail->updateMail($id, "New", "New body");

        $this->assertTrue($ok);

        $row = $mail->getMail($id);
        $this->assertEquals("New", $row["subject"]);
        $this->assertEquals("New body", $row["body"]);
    }

    // Test updating a non-existent mail entry
    public function testUpdateMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);

        $ok = $mail->updateMail(9999, "New", "New body");

        $this->assertFalse($ok);
    }

    // Test deleting an existing mail entry
    public function testDeleteMailDeletesExistingRow(): void
    {
        $id = $this->seedMail("To delete", "Bye");
        $mail = new Mail($this->pdo);

        $ok = $mail->deleteMail($id);

        $this->assertTrue($ok);
        $this->assertFalse($mail->getMail($id));
    }

    // Test deleting a non-existent mail entry
    public function testDeleteMailReturnsFalseWhenMissing(): void
    {
        $mail = new Mail($this->pdo);

        $ok = $mail->deleteMail(9999);

        $this->assertFalse($ok);
    }
}
