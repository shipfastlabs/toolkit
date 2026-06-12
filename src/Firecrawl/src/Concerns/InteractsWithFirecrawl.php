<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\Firecrawl\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Throwable;

trait InteractsWithFirecrawl
{
    private function firecrawlApiKey(): ?string
    {
        $apiKey = config('services.firecrawl.key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    private function firecrawlNotConfiguredMessage(): string
    {
        return 'The Firecrawl tool is not configured. Set services.firecrawl.key in your config/services.php file.';
    }

    private function firecrawlClient(string $apiKey, int $timeout): PendingRequest
    {
        return Http::baseUrl('https://api.firecrawl.dev')
            ->connectTimeout(10)
            ->timeout($timeout)
            ->withToken($apiKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function firecrawlPost(string $apiKey, int $timeout, string $path, array $payload, string $label): string
    {
        try {
            $response = $this->firecrawlClient($apiKey, $timeout)->post($path, $payload);
        } catch (Throwable $throwable) {
            return sprintf('The Firecrawl %s request failed: %s', $label, $throwable->getMessage());
        }

        return $this->firecrawlBody($response, $label);
    }

    private function firecrawlGet(string $apiKey, int $timeout, string $path, string $label): string
    {
        try {
            $response = $this->firecrawlClient($apiKey, $timeout)->get($path);
        } catch (Throwable $throwable) {
            return sprintf('The Firecrawl %s request failed: %s', $label, $throwable->getMessage());
        }

        return $this->firecrawlBody($response, $label);
    }

    private function firecrawlBody(Response $response, string $label): string
    {
        if ($response->failed()) {
            return sprintf(
                'The Firecrawl %s request failed with status %d: %s',
                $label,
                $response->status(),
                $response->body()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return sprintf('The Firecrawl %s response was invalid.', $label);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function firecrawlConfigString(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function firecrawlConfigInt(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function firecrawlConfigBool(string $key, bool $default): bool
    {
        $value = config($key, $default);

        return is_bool($value) ? $value : $default;
    }

    private function firecrawlResolveLimit(Request $request, string $configKey, int $default, int $min, int $max): int
    {
        $limit = $request->filled('limit')
            ? $request->integer('limit')
            : $this->firecrawlConfigInt($configKey, $default);

        return max($min, min($max, $limit));
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function firecrawlCsvAllowList(string $raw, array $allowed, string $fallback): array
    {
        $values = array_values(array_filter(
            array_map(trim(...), explode(',', $raw)),
            static fn (string $value): bool => in_array($value, $allowed, true),
        ));

        return $values === [] ? [$fallback] : $values;
    }
}
