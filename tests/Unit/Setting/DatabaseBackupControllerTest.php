<?php

namespace {
    if (! function_exists('storage_path')) {
        function storage_path(string $path = ''): string
        {
            $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-storage-tests';

            if (! is_dir($base)) {
                mkdir($base, 0777, true);
            }

            if ($path === '') {
                return $base;
            }

            return $base.DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
        }
    }
}

namespace Tests\Unit\Setting {

use App\Http\Controllers\Setting\DatabaseBackupController;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ZipArchive;

class DatabaseBackupControllerTest extends TestCase
{
    private array $temporaryFiles = [];
    private $previousCryptInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        $container->singleton('files', fn () => new Filesystem());
        Facade::setFacadeApplication($container);

        $key = random_bytes(32);
        $encrypter = new Encrypter($key, 'AES-256-CBC');
        $this->previousCryptInstance = Crypt::swap($encrypter);
    }

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

    public function testEncryptedExtensionIsAccepted(): void
    {
        $file = $this->fakeUploadedFile('database-backup.sql.enc', 'text/plain');

        $this->assertTrue($this->isValidBackupExtension($file));
    }

    public function testEncryptedSqlDumpIsDecryptedDuringExtraction(): void
    {
        $sql = "SELECT 1;\n";
        $encrypted = Crypt::encryptString($sql);

        $path = $this->createTemporaryFile($encrypted);

        $this->assertSame($sql, $this->extractSqlFromUpload($path, 'enc'));
    }

    public function testEncryptedArchiveIsDecryptedBeforeExtraction(): void
    {
        $sql = "SELECT 42;\n";
        $archivePath = tempnam(sys_get_temp_dir(), 'db-backup-zip-');

        $zip = new ZipArchive();
        $opened = $zip->open($archivePath, ZipArchive::OVERWRITE);

        if ($opened !== true) {
            $zip->open($archivePath, ZipArchive::CREATE);
        }

        $zip->addFromString('backup.sql', $sql);
        $zip->close();

        $archiveContents = file_get_contents($archivePath);
        $this->temporaryFiles[] = $archivePath;

        $encryptedArchive = Crypt::encryptString($archiveContents);
        $encryptedPath = $this->createTemporaryFile($encryptedArchive);

        $this->assertSame($sql, $this->extractSqlFromUpload($encryptedPath, 'enc'));
    }

    public function testUnexpectedExtensionIsRejected(): void
    {
        $file = $this->fakeUploadedFile('notes.txt');

        $this->assertFalse($this->isValidBackupExtension($file));
    }

    protected function tearDown(): void
    {
        if ($this->previousCryptInstance) {
            Crypt::swap($this->previousCryptInstance);
        } else {
            Facade::clearResolvedInstance('encrypter');
        }

        Facade::setFacadeApplication(null);

        $storageBase = storage_path();
        if (is_dir($storageBase)) {
            $this->deleteDirectory($storageBase);
        }

        foreach ($this->temporaryFiles as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                @unlink($path);
            }
        }

        $this->temporaryFiles = [];
        $this->previousCryptInstance = null;

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

    private function extractSqlFromUpload(string $path, string $extension): string
    {
        $controller = new DatabaseBackupController();
        $reflection = new ReflectionClass(DatabaseBackupController::class);
        $method = $reflection->getMethod('extractSqlFromUpload');
        $method->setAccessible(true);

        return (string) $method->invoke($controller, $path, $extension);
    }

    private function fakeUploadedFile(string $name, ?string $mimeType = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'db-backup-test-');
        file_put_contents($path, 'dummy');

        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function createTemporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'db-backup-encrypted-');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}

}
