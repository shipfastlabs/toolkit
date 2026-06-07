<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Exa\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Throwable;

trait InteractsWithExa
{
    private function exaApiKey(): ?string
    {
        $apiKey = config('services.exa.key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    private function exaNotConfiguredMessage(): string
    {
        return 'The Exa tool is not configured. Set services.exa.key in your config/services.php file.';
    }

    private function exaClient(string $apiKey, int $timeout): PendingRequest
    {
        return Http::baseUrl('https://api.exa.ai')
            ->timeout($timeout)
            ->withHeader('x-api-key', $apiKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function exaRequest(string $apiKey, int $timeout, string $path, array $payload, string $label): string
    {
        try {
            $response = $this->exaClient($apiKey, $timeout)->post($path, $payload);
        } catch (Throwable $throwable) {
            return sprintf('The Exa %s request failed: %s', $label, $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The Exa %s request failed with status %d: %s',
                $label,
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return sprintf('The Exa %s response was invalid.', $label);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function exaNumResults(Request $request, string $configKey): int
    {
        if ($request->has('num_results') && $request['num_results'] !== null) {
            return max(1, min(25, $request->integer('num_results')));
        }

        $num = config($configKey, 10);

        return is_numeric($num) ? max(1, min(25, (int) $num)) : 10;
    }

    private function exaIncludeText(Request $request, string $configKey, bool $default): bool
    {
        if ($request->has('include_text') && $request['include_text'] !== null) {
            return $request->boolean('include_text');
        }

        return (bool) config($configKey, $default);
    }

    /**
     * @return list<string>
     */
    private function parseDomains(Request $request, string $key): array
    {
        if (! $request->has($key) || $request[$key] === null) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', (string) $request->string($key))),
            static fn (string $domain): bool => $domain !== '',
        ));
    }
}
