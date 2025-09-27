<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\DbDumper\Databases\PostgreSql;
use Throwable;
use ZipArchive;

class DatabaseBackupController extends Controller
{
    /**
     * Display the database backup and restore tools page.
     */
    public function index()
    {
        return view('settings.database-backups');
    }

    /**
     * Display the database restore page.
     */
    public function restore()
    {
        return view('settings.database-restore');
    }

    /**
     * Trigger a database-only backup and return it as a download response.
     */
    public function export()
    {
        $connectionName = config('database.default');
        $connectionConfig = config("database.connections.{$connectionName}");

        if (! is_array($connectionConfig)) {
            Log::error('Database backup export failed: missing connection configuration.', [
                'connection' => $connectionName,
            ]);

            return redirect()
                ->back()
                ->with('error', __('setting.Database Export Error'));
        }

        $driver = $connectionConfig['driver'] ?? $connectionName;
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "database-backup-{$timestamp}.sql";
        $exportDirectory = storage_path('app/database-exports');
        $filePath = $exportDirectory.DIRECTORY_SEPARATOR.$fileName;

        File::ensureDirectoryExists($exportDirectory);

        $connection = DB::connection($connectionName);

        try {
            $this->dumpDatabaseToFile($driver, $connectionConfig, $filePath, $connection);

            return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            Log::error('Database backup export failed.', [
                'exception' => $e,
                'connection' => $connectionName,
            ]);

            return redirect()
                ->back()
                ->with('error', __('setting.Database Export Error'));
        }
    }

    /**
     * Restore the database from an uploaded backup archive or SQL dump.
     */
    public function import(Request $request)
    {
        $maxKilobytes = (int) config('backup.import.max_upload_kilobytes', 0);

        $rules = ['required', 'file'];

        if ($maxKilobytes > 0) {
            $rules[] = 'max:'.$maxKilobytes;
        }

        $validator = Validator::make($request->all(), [
            'backup_file' => $rules,
        ]);

        $validator->after(function ($validator) use ($request) {
            $file = $request->file('backup_file');

            if (! $file) {
                return;
            }

            if (! $this->isValidBackupExtension($file)) {
                $validator->errors()->add('backup_file', __('validation.mimes', [
                    'attribute' => __('validation.attributes.backup_file'),
                    'values' => 'zip, sql',
                ]));
            }
        });

        $validator->validate();

        $file = $request->file('backup_file');

        $reenableOnFailure = null;

        try {
            $sql = $this->extractSqlFromUpload($file->getRealPath(), (string) $file->getClientOriginalExtension());

            $driver = DB::getDriverName();
            $disableCommand = null;
            $enableCommand = null;

            if ($driver === 'mysql') {
                $disableCommand = 'SET FOREIGN_KEY_CHECKS=0;';
                $enableCommand = 'SET FOREIGN_KEY_CHECKS=1;';
            } elseif ($driver === 'sqlite') {
                $disableCommand = 'PRAGMA foreign_keys = OFF;';
                $enableCommand = 'PRAGMA foreign_keys = ON;';
            } elseif ($driver === 'pgsql') {
                $disableCommand = 'SET session_replication_role = replica;';
                $enableCommand = 'SET session_replication_role = DEFAULT;';
            }

            $reenableOnFailure = $enableCommand;

            if ($disableCommand) {
                DB::statement($disableCommand);
            }

            $statements = $this->splitSqlStatements($sql);

            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }

            if ($enableCommand) {
                DB::statement($enableCommand);
                $reenableOnFailure = null;
            }

            return redirect()
                ->back()
                ->with('success', __('setting.Database Import Success'));
        } catch (Throwable $e) {
            if (! empty($reenableOnFailure)) {
                try {
                    DB::statement($reenableOnFailure);
                } catch (Throwable $inner) {
                    Log::warning('Failed to re-enable database constraints after import error.', ['exception' => $inner]);
                }
            }

            Log::error('Database backup import failed.', ['exception' => $e]);

            return redirect()
                ->back()
                ->with('error', __('setting.Database Import Error'));
        }
    }

    /**
     * Determine if the uploaded backup file has an allowed extension.
     */
    private function isValidBackupExtension(UploadedFile $file): bool
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if ($extension === '') {
            $extension = Str::lower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        }

        return in_array($extension, ['zip', 'sql'], true);
    }

    /**
     * Extract SQL content from the uploaded backup file.
     */
    private function extractSqlFromUpload(string $path, string $extension): string
    {
        if (strtolower($extension) === 'sql') {
            return File::get($path);
        }

        $temporaryDirectory = storage_path('app/import-' . Str::uuid());
        File::makeDirectory($temporaryDirectory, 0755, true, true);

        try {
            $archive = new ZipArchive();
            if ($archive->open($path) !== true) {
                throw new \RuntimeException('Unable to open the uploaded archive.');
            }

            if ($archive->extractTo($temporaryDirectory) === false) {
                throw new \RuntimeException('Unable to extract the uploaded archive.');
            }

            $archive->close();

            $sqlFile = collect(File::allFiles($temporaryDirectory))
                ->first(function ($file) {
                    return Str::endsWith($file->getFilename(), '.sql');
                });

            if (! $sqlFile) {
                throw new FileNotFoundException('No SQL dump found inside the archive.');
            }

            return File::get($sqlFile->getRealPath());
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /**
     * Dump the configured database connection to the provided file path.
     */
    private function dumpDatabaseToFile(string $driver, array $config, string $destination, ConnectionInterface $connection): void
    {
        if (Str::of($driver)->lower()->contains('sqlite')) {
            $databasePath = $config['database'] ?? null;

            if (! $databasePath || $databasePath === ':memory:') {
                throw new \RuntimeException('SQLite in-memory databases cannot be exported.');
            }

            if (! File::exists($databasePath)) {
                throw new FileNotFoundException("SQLite database file not found at {$databasePath}.");
            }

            if (! File::copy($databasePath, $destination)) {
                throw new \RuntimeException("Failed to copy SQLite database from {$databasePath}.");
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->dumpMySqlDatabase($connection, $config, $destination);

            return;
        }

        $dumper = match ($driver) {
            'pgsql', 'postgresql', 'postgres' => PostgreSql::create(),
            default => null,
        };

        if ($dumper === null) {
            throw new \RuntimeException("Database driver [{$driver}] is not supported for export.");
        }

        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));

        $dumper
            ->setDbName($database)
            ->setUserName($username)
            ->setPassword($password)
            ->setHost($host)
            ->setPort($port);

        if (! empty($config['unix_socket'])) {
            $dumper->setSocket($config['unix_socket']);
        }

        if (! empty($config['dump']['dump_binary_path'])) {
            $dumper->setDumpBinaryPath($config['dump']['dump_binary_path']);
        }

        if (! empty($config['dump']['timeout'])) {
            $dumper->setTimeout((int) $config['dump']['timeout']);
        }

        if (! empty($config['dump']['extra_options']) && is_array($config['dump']['extra_options'])) {
            foreach ($config['dump']['extra_options'] as $option) {
                if (is_string($option) && $option !== '') {
                    $dumper->addExtraOption($option);
                }
            }
        }

        if (method_exists($dumper, 'useSingleTransaction') && ($config['dump']['use_single_transaction'] ?? false)) {
            $dumper->useSingleTransaction();
        }

        if (! empty($config['dump']['exclude_tables']) && is_array($config['dump']['exclude_tables'])) {
            foreach ($config['dump']['exclude_tables'] as $table) {
                if (is_string($table) && $table !== '') {
                    $dumper->excludeTables($table);
                }
            }
        }

        $dumper->dumpToFile($destination);
    }

    /**
     * Dump a MySQL or MariaDB database without relying on external binaries.
     */
    private function dumpMySqlDatabase(ConnectionInterface $connection, array $config, string $destination): void
    {
        $database = $config['database'] ?? $connection->getDatabaseName();

        if ($database === null) {
            throw new RuntimeException('Unable to determine the database name for export.');
        }

        $handle = fopen($destination, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the export destination for writing.');
        }

        $pdo = $connection->getPdo();

        $write = static function (string $sql) use ($handle): void {
            if (fwrite($handle, $sql) === false) {
                throw new RuntimeException('Failed to write to the export file.');
            }
        };

        try {
            $write('-- Database Backup'.PHP_EOL);
            $write('-- Generated at '.now()->toDateTimeString().PHP_EOL.PHP_EOL);
            $write('SET NAMES utf8mb4;'.PHP_EOL);
            $write('SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL);
            $write('USE `'.str_replace('`', '``', $database).'`;'.PHP_EOL.PHP_EOL);

            $tables = $connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

            foreach ($tables as $tableRow) {
                $tableData = (array) $tableRow;
                $table = array_values($tableData)[0] ?? null;

                if ($table === null) {
                    continue;
                }

                $write('-- --------------------------------------------------'.PHP_EOL);
                $write('-- Structure for table `'.$table.'`'.PHP_EOL);
                $write('-- --------------------------------------------------'.PHP_EOL);

                $write('DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL);

                $create = (array) $connection->selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $create['Create Table'] ?? $create['Create View'] ?? (array_values($create)[1] ?? null);

                if ($createSql === null) {
                    throw new RuntimeException("Unable to determine create statement for table {$table}.");
                }

                $write($createSql.';'.PHP_EOL.PHP_EOL);

                $rows = [];
                $columns = null;
                $batchSize = 500;

                $quote = static function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    if (is_int($value) || is_float($value)) {
                        return (string) $value;
                    }

                    return $pdo->quote((string) $value);
                };

                foreach ($connection->table($table)->select('*')->cursor() as $record) {
                    $row = (array) $record;

                    if ($columns === null) {
                        $columns = array_keys($row);
                    }

                    $rows[] = $row;

                    if (count($rows) >= $batchSize) {
                        $this->writeInsertStatements($handle, $table, $columns, $rows, $quote);
                        $rows = [];
                    }
                }

                if (! empty($rows) && $columns !== null) {
                    $this->writeInsertStatements($handle, $table, $columns, $rows, $quote);
                }

                $write(PHP_EOL);
            }

            $write('SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Write INSERT statements for the provided rows.
     */
    private function writeInsertStatements($handle, string $table, array $columns, array $rows, callable $quote): void
    {
        $columnList = implode(', ', array_map(fn ($column) => '`'.str_replace('`', '``', $column).'`', $columns));

        $values = [];

        foreach ($rows as $row) {
            $ordered = [];

            foreach ($columns as $column) {
                $ordered[] = $quote($row[$column] ?? null);
            }

            $values[] = '('.implode(', ', $ordered).')';
        }

        $statement = 'INSERT INTO `'.$table.'` ('.$columnList.') VALUES'.PHP_EOL;
        $statement .= implode(','.PHP_EOL, $values).';'.PHP_EOL;

        if (fwrite($handle, $statement) === false) {
            throw new RuntimeException('Failed to write INSERT statements to the export file.');
        }
    }

    /**
     * Break a SQL dump into executable statements while handling comments and strings.
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = ltrim($sql, "\xEF\xBB\xBF");

        $statements = [];
        $current = '';
        $lineBuffer = '';
        $length = strlen($sql);
        $inString = false;
        $stringDelimiter = '';
        $inLineComment = false;
        $inBlockComment = false;
        $delimiter = ';';
        $delimiterLength = strlen($delimiter);

        $parseDelimiterDirective = static function (string $line) use (&$delimiter, &$delimiterLength, &$current, &$lineBuffer): bool {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                return false;
            }

            if (substr($trimmedLine, 0, 2) === '/*!') {
                $withoutPrefix = preg_replace('/^\/\*!\d+\s*/', '', $trimmedLine);

                if ($withoutPrefix !== null && $withoutPrefix !== '') {
                    $commentEnd = strpos($withoutPrefix, '*/');

                    if ($commentEnd !== false) {
                        $withoutPrefix = substr($withoutPrefix, 0, $commentEnd);
                    }

                    $candidate = trim($withoutPrefix);

                    if ($candidate !== '') {
                        $trimmedLine = $candidate;
                    }
                }
            }

            if (! preg_match('/^DELIMITER\s+(\S+)/i', $trimmedLine, $matches)) {
                return false;
            }

            $newDelimiter = $matches[1] !== '' ? $matches[1] : ';';

            if ($newDelimiter === '') {
                $newDelimiter = ';';
            }

            $delimiter = $newDelimiter;
            $delimiterLength = strlen($delimiter);

            $lineLength = strlen($lineBuffer);

            if ($lineLength > 0 && $lineLength <= strlen($current)) {
                $current = substr($current, 0, -$lineLength);
            }

            $lineBuffer = '';
            $current = rtrim($current, "\r\n");

            return true;
        };

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($inLineComment) {
                if ($char === "\n" || ($char === "\r" && $next !== "\n")) {
                    $inLineComment = false;
                    $lineBuffer = '';
                }

                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }

                if ($char === "\n" || ($char === "\r" && $next !== "\n")) {
                    $lineBuffer = '';
                }

                continue;
            }

            if ($inString) {
                $current .= $char;
                $lineBuffer .= $char;

                if ($char === $stringDelimiter) {
                    $escaped = false;
                    $offset = 1;

                    while ($i - $offset >= 0 && ($sql[$i - $offset] ?? null) === '\\') {
                        $escaped = ! $escaped;
                        $offset++;
                    }

                    if (! $escaped) {
                        $inString = false;
                        $stringDelimiter = '';
                    }
                }

                if ($char === "\n" || ($char === "\r" && $next !== "\n")) {
                    $lineBuffer = '';
                }

                continue;
            }

            if ($char === '-' && $next === '-') {
                $third = $sql[$i + 2] ?? '';

                if ($third === ' ' || $third === "\t" || $third === '-' || $third === "\r" || $third === "\n" || $third === '') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === '#') {
                $inLineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }

            if ($delimiterLength > 0 && substr($sql, $i, $delimiterLength) === $delimiter) {
                $trimmed = trim($current);

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $current = '';
                $lineBuffer = '';

                $i += $delimiterLength - 1;

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringDelimiter = $char;
                $current .= $char;
                $lineBuffer .= $char;

                continue;
            }

            $current .= $char;
            $lineBuffer .= $char;

            if ($char === "\n" || ($char === "\r" && $next !== "\n")) {
                if ($parseDelimiterDirective($lineBuffer)) {
                    continue;
                }

                $lineBuffer = '';
            }
        }

        if ($lineBuffer !== '') {
            $parseDelimiterDirective($lineBuffer);
        }

        $trimmed = trim($current);

        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
