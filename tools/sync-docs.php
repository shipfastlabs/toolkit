<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$target = $root.'/docs/tools';

if (! is_dir($target)) {
    mkdir($target, 0755, true);
}

$folders = array_filter(
    glob($root.'/src/*', GLOB_ONLYDIR) ?: [],
    fn (string $path): bool => basename($path) !== 'stub',
);

foreach ($folders as $path) {
    $folder = basename($path);
    $readme = $path.'/README.md';

    if (! is_file($readme)) {
        echo "! {$folder} has no README.md, skipping\n";

        continue;
    }

    $slug = strtolower($folder);
    $title = ucfirst($slug);
    $contents = (string) file_get_contents($readme);

    $contents = preg_replace('/^\[!\[.*$\n/m', '', $contents) ?? $contents;
    $contents = preg_replace('/^<!-- AUTO-GENERATED.*-->\s*$\n/m', '', $contents) ?? $contents;

    $contents = preg_replace('/^#\s+.+?\s*$/m', "# {$title}", $contents, 1) ?? $contents;

    $contents = ltrim($contents);
    $contents = preg_replace('/\n{3,}/', "\n\n", $contents) ?? $contents;
    $contents = preg_replace('/^(#[^\n]*)\n(?!\n)/', "$1\n\n", $contents, 1) ?? $contents;

    $destination = sprintf('%s/%s.md', $target, $slug);
    file_put_contents($destination, $contents."\n");
    echo "✓ {$destination}\n";
}
