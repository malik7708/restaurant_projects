<?php
// Recursively scan for .php files and unescape common HTML-escaped tags
$root = __DIR__ . '/../';

function scanAndFix($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            scanAndFix($path);
        } elseif (is_file($path) && preg_match('/\.php$/i', $path)) {
            $content = file_get_contents($path);
            $original = $content;
            // Common replacements
            $replacements = [
                '<?php' => '<?php',
                '?>' => '?>',
                '<!--' => '<!--',
                '-->' => '-->',
                '</' => '</',
                '<' => '<',
                '>' => '>',
            ];
            $content = strtr($content, $replacements);

            if ($content !== $original) {
                file_put_contents($path, $content);
                echo "Fixed: $path\n";
            }
        }
    }
}

scanAndFix($root);
echo "Done.\n";
