<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack\Concerns;

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Throwable;

trait InteractsWithJigsawStack
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function request(string $method, string $path, array $data): string
    {
        $apiKey = config('services.jigsawstack.key');

        if (! is_string($apiKey) || $apiKey === '') {
            return 'The JigsawStack tool is not configured. Set services.jigsawstack.key in your config/services.php file.';
        }

        $client = Http::timeout(60)->withHeaders(['x-api-key' => $apiKey]);
        $url = 'https://api.jigsawstack.com/'.$path;

        try {
            $response = $method === 'get'
                ? $client->get($url, $data)
                : $client->post($url, $data);
        } catch (Throwable $throwable) {
            return sprintf('The JigsawStack request failed: %s', $throwable->getMessage());
        }

        if ($response->failed()) {
            return sprintf(
                'The JigsawStack request failed with status %d: %s',
                $response->status(),
                $response->body()
            );
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            return 'The JigsawStack response was invalid.';
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withOptional(Request $request, array $payload, string ...$keys): array
    {
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $payload[$key] = $request[$key];
            }
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    private function splitList(string $value): array
    {
        return str($value)
            ->explode(',')
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }
}
