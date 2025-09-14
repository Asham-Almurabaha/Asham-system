<?php

function flatten(array $array, string $prefix = ''): array {
    $result = [];
    foreach ($array as $key => $value) {
        $newKey = $prefix === '' ? $key : "$prefix.$key";
        if (is_array($value)) {
            $result += flatten($value, $newKey);
        } else {
            $result[$newKey] = $value;
        }
    }
    return $result;
}

$baseLang = 'en';
$langDir = __DIR__ . '/../resources/lang';
$languages = array_filter(glob($langDir.'/*'), function($path) {
    return is_dir($path) && basename($path) !== 'vendor';
});
$files = array_map('basename', glob("$langDir/$baseLang/*.php"));
$errors = 0;

foreach ($files as $file) {
    $baseKeys = flatten(require "$langDir/$baseLang/$file");
    foreach ($languages as $langPath) {
        $lang = basename($langPath);
        if ($lang === $baseLang) {
            continue;
        }
        $path = "$langDir/$lang/$file";
        if (!file_exists($path)) {
            echo "Missing file $path\n";
            $errors++;
            continue;
        }
        $keys = flatten(require $path);
        $missing = array_diff_key($baseKeys, $keys);
        $extra = array_diff_key($keys, $baseKeys);
        if ($missing) {
            echo "Missing keys in $lang/$file: " . implode(', ', array_keys($missing)) . "\n";
            $errors++;
        }
        if ($extra) {
            echo "Extra keys in $lang/$file: " . implode(', ', array_keys($extra)) . "\n";
            $errors++;
        }
    }
}

if ($errors) {
    exit(1);
}

