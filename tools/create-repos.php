<?php

declare(strict_types=1);

$org = getenv('GITHUB_ORG') ?: 'shipfastlabs';
$token = getenv('GITHUB_PUSH_TOKEN');

if (! $token) {
    fwrite(STDERR, "GITHUB_PUSH_TOKEN is required.\n");

    exit(1);
}

$overrides = file_exists(__DIR__.'/../split-overrides.json')
    ? (array) json_decode((string) file_get_contents(__DIR__.'/../split-overrides.json'), true)
    : [];

/**
 * @param  array<string, string>  $headers
 * @param  array<string, mixed>|null  $body
 * @return array{0: int, 1: mixed}
 */
function request(string $method, string $url, array $headers, ?array $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_map(
            fn (string $k, string $v): string => "{$k}: {$v}",
            array_keys($headers),
            array_values($headers),
        ),
        CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body),
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$status, json_decode((string) $response, true)];
}

/**
 * @return array<string, string>
 */
function githubHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'shipfastlabs-toolkit',
        'Content-Type' => 'application/json',
    ];
}

function repoName(string $folder, array $overrides): string
{
    return $overrides[$folder] ?? 'toolkit-'.strtolower($folder);
}

$folders = array_filter(
    glob(__DIR__.'/../src/*', GLOB_ONLYDIR) ?: [],
    fn (string $path): bool => basename($path) !== 'stub',
);

foreach ($folders as $path) {
    $folder = basename($path);
    $repo = repoName($folder, $overrides);

    [$status] = request('GET', "https://api.github.com/repos/{$org}/{$repo}", githubHeaders($token));

    if ($status === 200) {
        echo "✓ {$org}/{$repo} already exists\n";

        continue;
    }

    echo "+ Creating {$org}/{$repo}\n";

    [$createStatus, $created] = request(
        'POST',
        "https://api.github.com/orgs/{$org}/repos",
        githubHeaders($token),
        [
            'name' => $repo,
            'description' => "[READ ONLY] Subtree split of the {$folder} tool — see github.com/{$org}/toolkit",
            'homepage' => "https://github.com/{$org}/toolkit",
            'has_issues' => false,
            'has_wiki' => false,
            'has_projects' => false,
        ],
    );

    if ($createStatus >= 300) {
        fwrite(STDERR, "Failed to create {$repo}: ".json_encode($created)."\n");

        exit(1);
    }

    request('PUT', "https://api.github.com/repos/{$org}/{$repo}/topics", githubHeaders($token) + [
        'Accept' => 'application/vnd.github.mercy-preview+json',
    ], ['names' => ['laravel', 'ai', 'tool', 'laravel-ai']]);

    registerOnPackagist("https://github.com/{$org}/{$repo}");
}

function registerOnPackagist(string $repoUrl): void
{
    $username = getenv('PACKAGIST_USERNAME');
    $packagistToken = getenv('PACKAGIST_TOKEN');

    if (! $username || ! $packagistToken) {
        echo "  (skipping Packagist registration — credentials not set)\n";

        return;
    }

    [$status] = request(
        'POST',
        "https://packagist.org/api/create-package?username={$username}&apiToken={$packagistToken}",
        ['Content-Type' => 'application/json', 'User-Agent' => 'shipfastlabs-toolkit'],
        ['repository' => ['url' => $repoUrl]],
    );

    echo $status < 300
        ? "  ✓ Registered on Packagist\n"
        : "  ! Packagist registration returned HTTP {$status}\n";
}
