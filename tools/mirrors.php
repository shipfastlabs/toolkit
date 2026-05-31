<?php

declare(strict_types=1);

/**
 * @param  array<string, mixed>|null  $body
 * @return array{0: int, 1: mixed}
 */
function githubApi(string $method, string $url, string $token, ?array $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            'Accept: application/vnd.github+json',
            'User-Agent: shipfastlabs-toolkit',
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body),
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return [$status, json_decode((string) $response, true)];
}

function mirrorRepoName(string $folder): string
{
    static $overrides;

    if ($overrides === null) {
        $file = __DIR__.'/../split-overrides.json';
        $overrides = file_exists($file)
            ? (array) json_decode((string) file_get_contents($file), true)
            : [];
    }

    return $overrides[$folder] ?? 'toolkit-'.strtolower($folder);
}

/**
 * @return list<string>
 */
function mirrorRepos(string $org, string $token): array
{
    $folders = array_filter(
        glob(__DIR__.'/../src/*', GLOB_ONLYDIR) ?: [],
        fn (string $path): bool => basename($path) !== 'stub',
    );

    return array_values(array_map(
        fn (string $path): string => mirrorRepoName(basename($path)),
        $folders,
    ));
}
