<?php

namespace Tests\Unit\Setting;

use App\Http\Controllers\Setting\DatabaseBackupController;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseBackupControllerTest extends TestCase
{
    private array $temporaryFiles = [];

    /**
     * @dataProvider basicSplitProvider
     */
    public function testSplitSqlStatements(string $sql, array $expected): void
    {
        $this->assertSame($expected, $this->splitStatements($sql));
    }

    public function testSqlExtensionIsAcceptedRegardlessOfCase(): void
    {
        $file = $this->fakeUploadedFile('example.SQL');

        $this->assertTrue($this->isValidBackupExtension($file));
    }

    public function testZipExtensionIsAccepted(): void
    {
        $file = $this->fakeUploadedFile('archive.zip', 'application/zip');

        $this->assertTrue($this->isValidBackupExtension($file));
    }

    public function testUnexpectedExtensionIsRejected(): void
    {
        $file = $this->fakeUploadedFile('notes.txt');

        $this->assertFalse($this->isValidBackupExtension($file));
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                @unlink($path);
            }
        }

        $this->temporaryFiles = [];

        parent::tearDown();
    }

    public function basicSplitProvider(): iterable
    {
        yield 'custom delimiter with reset' => [
            <<<'SQL'
DELIMITER $$
CREATE PROCEDURE test()
BEGIN
    SELECT 'DELIMITER $$ inside string';
END$$
DELIMITER ;
SELECT 2;
SQL,
            [
                "CREATE PROCEDURE test()\nBEGIN\n    SELECT 'DELIMITER $$ inside string';\nEND",
                'SELECT 2',
            ],
        ];

        yield 'versioned delimiter comment' => [
            <<<'SQL'
DELIMITER //
CREATE TRIGGER sample BEFORE INSERT ON `demo`
FOR EACH ROW
BEGIN
    SET NEW.`created_at` = NOW();
END//
/*!50003 DELIMITER ; */
INSERT INTO `demo` (`name`) VALUES ('example');
SQL,
            [
                "CREATE TRIGGER sample BEFORE INSERT ON `demo`\nFOR EACH ROW\nBEGIN\n    SET NEW.`created_at` = NOW();\nEND",
                "INSERT INTO `demo` (`name`) VALUES ('example')",
            ],
        ];

        yield 'byte order mark is ignored' => [
            "\xEF\xBB\xBFSELECT 1;",
            ['SELECT 1'],
        ];
    }

    private function splitStatements(string $sql): array
    {
        $controller = new DatabaseBackupController();
        $reflection = new ReflectionClass(DatabaseBackupController::class);
        $method = $reflection->getMethod('splitSqlStatements');
        $method->setAccessible(true);

        return $method->invoke($controller, $sql);
    }

    private function isValidBackupExtension(UploadedFile $file): bool
    {
        $controller = new DatabaseBackupController();
        $reflection = new ReflectionClass(DatabaseBackupController::class);
        $method = $reflection->getMethod('isValidBackupExtension');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller, $file);
    }

    private function fakeUploadedFile(string $name, ?string $mimeType = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'db-backup-test-');
        file_put_contents($path, 'dummy');

        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, $mimeType, null, true);
    }
}
