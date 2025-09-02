<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Filesystem\Filesystem;

class CheckTranslations extends Command
{
    protected $signature = 'translations:check {--locales=* : Locales to check (defaults to all found)} {--fix-json : Add missing simple keys to locale JSON files} {--convert-groups : Convert per-locale *.json files to PHP group files if missing}';

    protected $description = 'Scan Blade files for translation keys and verify they exist per locale';

    public function handle(): int
    {
        $fs = new Filesystem();

        $viewPath = resource_path('views');
        if (! $fs->exists($viewPath)) {
            $this->error('Views path not found: ' . $viewPath);
            return self::FAILURE;
        }

        // Optional: convert per-locale JSON files into group PHP files
        if ($this->option('convert-groups')) {
            $this->convertPerLocaleJsonToGroups($fs);
        }

        // 1) Collect keys from Blade files
        $bladeFiles = collect($fs->allFiles($viewPath))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.blade.php'))
            ->values();

        $pattern = '/(?:__|@lang|trans_choice|trans|Lang::get|@choice)\(\s*([\'\"])' .
                   '([^\'\"\)]+)\1/';

        $keyUsage = []; // key => [files]

        foreach ($bladeFiles as $file) {
            $content = $fs->get($file->getRealPath());
            if (preg_match_all($pattern, $content, $m)) {
                foreach ($m[2] as $k) {
                    $key = trim($k);
                    // Skip empty
                    if ($key === '') { continue; }
                    // normalize whitespace
                    $key = preg_replace('/\s+/', ' ', $key);
                    $keyUsage[$key] ??= [];
                    $keyUsage[$key][$file->getRelativePathname()] = true;
                }
            }
        }

        $keys = array_keys($keyUsage);
        sort($keys);

        if (empty($keys)) {
            $this->info('No translation keys found in Blade files.');
            return self::SUCCESS;
        }

        // 2) Determine locales
        $locales = (array) $this->option('locales');
        $locales = array_values(array_filter($locales));
        if (empty($locales)) {
            $langPath = resource_path('lang');
            $locales = [];
            if ($fs->exists($langPath)) {
                // Directories like resources/lang/en
                foreach ($fs->directories($langPath) as $dir) {
                    $locale = basename($dir);
                    if ($locale === 'vendor') { continue; }
                    $locales[] = $locale;
                }
                // Root JSON files like resources/lang/en.json
                foreach ($fs->files($langPath) as $file) {
                    if ($file->getExtension() === 'json') {
                        $locales[] = $file->getFilenameWithoutExtension();
                    }
                }
            }
            $locales = array_values(array_unique($locales));
        }

        if (empty($locales)) {
            $this->error('No locales found under resources/lang.');
            return self::FAILURE;
        }

        $this->info('Locales: ' . implode(', ', $locales));

        // 3) Check keys per locale using the Translator (no fallback)
        $missing = []; // locale => [key => files]

        foreach ($locales as $locale) {
            foreach ($keys as $key) {
                try {
                    $exists = Lang::has($key, $locale, false);
                } catch (\Throwable $e) {
                    // Very exotic keys can cause translator errors; treat as missing
                    $exists = false;
                }
                if (! $exists) {
                    $missing[$locale][$key] = array_keys($keyUsage[$key]);
                }
            }
        }

        if (empty($missing)) {
            $this->info('All keys are present for all locales. ✔');
            return self::SUCCESS;
        }

        // 4) Report
        $totalMissing = 0;
        foreach ($missing as $locale => $items) {
            $count = count($items);
            $totalMissing += $count;
            $this->newLine();
            $this->warn("Missing keys in locale '{$locale}' ({$count}):");
            foreach ($items as $key => $files) {
                $this->line("  - {$key}");
                // Show up to 3 files per key for brevity
                $fileList = array_slice($files, 0, 3);
                $more = max(count($files) - 3, 0);
                $suffix = $more ? ", +{$more} more" : '';
                $this->line('    used in: ' . implode(', ', $fileList) . $suffix);
            }
        }

        // 5) Optional auto-fix for simple JSON keys (no dot/namespace)
        if ($this->option('fix-json')) {
            $this->newLine();
            $this->info('Attempting to add missing simple keys to locale JSON files...');
            foreach ($locales as $locale) {
                $jsonPath = resource_path("lang/{$locale}.json");
                $data = [];
                if ($fs->exists($jsonPath)) {
                    $content = $fs->get($jsonPath);
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) { $data = $decoded; }
                }

                $added = 0;
                $groupDir = resource_path("lang/{$locale}");
                foreach (array_keys($missing[$locale] ?? []) as $key) {
                    // Skip namespaced keys
                    if (str_contains($key, '::')) { continue; }

                    // Determine if this looks like a real group key (e.g. group.item)
                    $firstDot = strpos($key, '.');
                    $looksGrouped = false;
                    if ($firstDot !== false) {
                        $group = substr($key, 0, $firstDot);
                        // If we have a matching group php file for this locale, treat as grouped
                        if (is_file($groupDir . DIRECTORY_SEPARATOR . $group . '.php')) {
                            $looksGrouped = true;
                        }
                    }

                    // For true group keys, don't add to JSON here
                    if ($looksGrouped) { continue; }

                    if (! array_key_exists($key, $data)) {
                        $data[$key] = $key; // placeholder equals key
                        $added++;
                    }
                }

                if ($added > 0) {
                    // Sort keys for readability
                    ksort($data, SORT_NATURAL | SORT_FLAG_CASE);
                    $fs->put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
                    $this->info("  Updated {$jsonPath} (+{$added} keys)");
                } else {
                    $this->line("  No simple keys to add for {$locale}");
                }
            }
        } else {
            $this->newLine();
            $this->line('Tip: Run with --fix-json to auto-add missing simple keys to each {locale}.json file.');
        }

        $this->newLine();
        $this->error("Total missing keys: {$totalMissing}");
        return self::FAILURE;
    }

    private function convertPerLocaleJsonToGroups(Filesystem $fs): void
    {
        $langPath = resource_path('lang');
        if (! $fs->exists($langPath)) { return; }

        $this->info('Converting per-locale JSON files to PHP group files (if missing)...');
        foreach ($fs->directories($langPath) as $dir) {
            $locale = basename($dir);
            if ($locale === 'vendor') { continue; }
            foreach ($fs->files($dir) as $file) {
                if ($file->getExtension() !== 'json') { continue; }
                $group = $file->getFilenameWithoutExtension();
                $targetPhp = $file->getPath() . DIRECTORY_SEPARATOR . $group . '.php';
                if ($fs->exists($targetPhp)) {
                    continue; // already present
                }
                $decoded = json_decode($fs->get($file->getRealPath()), true);
                if (! is_array($decoded)) { continue; }
                // Create PHP array file
                $export = var_export($decoded, true);
                $php = "<?php\n\nreturn " . $export . ";\n";
                $fs->put($targetPhp, $php);
                $this->line(sprintf('  %s -> %s', $file->getRelativePathname(), $locale . DIRECTORY_SEPARATOR . $group . '.php'));
            }
        }
    }
}
