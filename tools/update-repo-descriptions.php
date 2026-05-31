<?php

declare(strict_types=1);

require __DIR__.'/mirrors.php';

$org = getenv('GITHUB_ORG') ?: 'shipfastlabs';
$token = getenv('GITHUB_PUSH_TOKEN') ?: '';

if ($token === '') {
    fwrite(STDERR, "GITHUB_PUSH_TOKEN is required.\n");

    exit(1);
}

$folders = array_filter(
    glob(__DIR__.'/../src/*', GLOB_ONLYDIR) ?: [],
    fn (string $path): bool => basename($path) !== 'stub',
);

foreach ($folders as $path) {
    $folder = basename($path);
    $repo = mirrorRepoName($folder);

    $composer = (array) json_decode((string) file_get_contents($path.'/composer.json'), true);
    $description = $composer['description'] ?? "The {$folder} tool for the Laravel AI SDK";
    $topics = array_values(array_unique(array_merge(
        ['laravel', 'ai', 'laravel-ai', 'tool'],
        (array) ($composer['keywords'] ?? []),
    )));

    echo "Updating {$org}/{$repo}\n";

    githubApi('PATCH', "https://api.github.com/repos/{$org}/{$repo}", $token, [
        'description' => "[READ ONLY] {$description}",
        'homepage' => "https://github.com/{$org}/toolkit",
    ]);

    githubApi('PUT', "https://api.github.com/repos/{$org}/{$repo}/topics", $token, [
        'names' => array_map(fn (string $t): string => strtolower($t), $topics),
    ]);
}
