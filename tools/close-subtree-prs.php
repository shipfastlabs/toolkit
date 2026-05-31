<?php

declare(strict_types=1);

require __DIR__.'/mirrors.php';

$org = getenv('GITHUB_ORG') ?: 'shipfastlabs';
$token = getenv('GITHUB_PUSH_TOKEN') ?: '';

if ($token === '') {
    fwrite(STDERR, "GITHUB_PUSH_TOKEN is required.\n");

    exit(1);
}

$redirect = 'Thanks for the contribution! These packages are **read-only mirrors** '
    .'— code and releases are driven from the monorepo. Please re-open this against '
    ."https://github.com/{$org}/toolkit. Closing automatically.";

foreach (mirrorRepos($org, $token) as $repo) {
    [, $pulls] = githubApi('GET', "https://api.github.com/repos/{$org}/{$repo}/pulls?state=open", $token);

    foreach ((array) $pulls as $pull) {
        $number = $pull['number'];
        echo "Closing {$org}/{$repo}#{$number}\n";

        githubApi(
            'POST',
            "https://api.github.com/repos/{$org}/{$repo}/issues/{$number}/comments",
            $token,
            ['body' => $redirect],
        );

        githubApi(
            'PATCH',
            "https://api.github.com/repos/{$org}/{$repo}/pulls/{$number}",
            $token,
            ['state' => 'closed'],
        );
    }
}
