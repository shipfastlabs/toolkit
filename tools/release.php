<?php

declare(strict_types=1);

$org = getenv('GITHUB_ORG') ?: 'shipfastlabs';
$token = getenv('GITHUB_PUSH_TOKEN') ?: '';
$sha = getenv('GITHUB_SHA') ?: trim((string) shell_exec('git rev-parse HEAD'));
$monorepo = getenv('GITHUB_REPOSITORY') ?: "{$org}/toolkit";

if ($token === '') {
    fwrite(STDERR, "GITHUB_PUSH_TOKEN is required.\n");

    exit(1);
}

$overrides = file_exists(__DIR__.'/../split-overrides.json')
    ? (array) json_decode((string) file_get_contents(__DIR__.'/../split-overrides.json'), true)
    : [];

/**
 * @param  array<string, mixed>|null  $body
 * @return array{0: int, 1: mixed}
 */
function api(string $method, string $url, string $token, ?array $body = null): array
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

function repoName(string $folder, array $overrides): string
{
    return $overrides[$folder] ?? 'toolkit-'.strtolower($folder);
}

/**
 * @return array{bump: string, isNew: bool}
 */
function labelsForCommit(string $monorepo, string $sha, string $token): array
{
    [, $pulls] = api('GET', "https://api.github.com/repos/{$monorepo}/commits/{$sha}/pulls", $token);

    $labels = [];

    foreach (((array) $pulls)[0]['labels'] ?? [] as $label) {
        $labels[] = $label['name'];
    }

    $bump = 'patch';

    foreach (['major', 'minor', 'patch'] as $candidate) {
        if (in_array("release:{$candidate}", $labels, true)) {
            $bump = $candidate;

            break;
        }
    }

    return ['bump' => $bump, 'isNew' => in_array('new-tool', $labels, true)];
}

/**
 * @return list<string>
 */
function changedFolders(string $sha): array
{
    $output = (string) shell_exec('git diff-tree --no-commit-id --name-only -r '.escapeshellarg($sha));
    $folders = [];

    foreach (explode("\n", trim($output)) as $line) {
        if (preg_match('#^src/([^/]+)/#', $line, $matches) && $matches[1] !== 'stub') {
            $folders[$matches[1]] = true;
        }
    }

    return array_keys($folders);
}

function latestTag(string $org, string $repo, string $token): ?string
{
    [$status, $tags] = api('GET', "https://api.github.com/repos/{$org}/{$repo}/tags?per_page=1", $token);

    if ($status !== 200 || $tags === []) {
        return null;
    }

    return $tags[0]['name'] ?? null;
}

function bump(string $version, string $type): string
{
    [$major, $minor, $patch] = array_map('intval', explode('.', ltrim($version, 'v')) + [0, 0, 0]);

    return match ($type) {
        'major' => ($major + 1).'.0.0',
        'minor' => "{$major}.".($minor + 1).'.0',
        default => "{$major}.{$minor}.".($patch + 1),
    };
}

$context = labelsForCommit($monorepo, $sha, $token);
$folders = changedFolders($sha);

if ($folders === []) {
    echo "No tool folders changed in {$sha}; nothing to release.\n";

    exit(0);
}

foreach ($folders as $folder) {
    $repo = repoName($folder, $overrides);
    $latest = latestTag($org, $repo, $token);

    $next = $context['isNew'] || $latest === null
        ? '1.0.0'
        : bump($latest, $context['bump']);

    echo "==> Releasing {$org}/{$repo} {$latest} -> {$next} ({$context['bump']})\n";

    [$status, $body] = api(
        'POST',
        "https://api.github.com/repos/{$org}/{$repo}/releases",
        $token,
        [
            'tag_name' => $next,
            'name' => $next,
            'generate_release_notes' => true,
            'target_commitish' => 'master',
        ],
    );

    if ($status >= 300) {
        fwrite(STDERR, "Failed to release {$repo}: ".json_encode($body)."\n");

        exit(1);
    }
}
