<?php

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\Tavily\TavilyCrawl;
use Shipfastlabs\Toolkit\Tavily\TavilyExtract;
use Shipfastlabs\Toolkit\Tavily\TavilyMap;
use Shipfastlabs\Toolkit\Tavily\TavilySearch;

Route::get('/', function () {
    $tools = [
        'TavilySearch' => new TavilySearch,
        'TavilyExtract' => new TavilyExtract,
        'TavilyCrawl' => new TavilyCrawl,
        'TavilyMap' => new TavilyMap,
    ];

    $results = [];

    foreach ($tools as $name => $tool) {
        $results[$name] = [
            'description' => $tool->description(),
            'schema' => array_keys($tool->schema(app(JsonSchema::class))),
        ];
    }

    return response()->json([
        'message' => 'Toolkit Tavily tools are loaded.',
        'tools' => $results,
        'endpoints' => [
            'GET /tavily/search?query=...' => 'TavilySearch',
            'GET /tavily/extract?urls=...' => 'TavilyExtract',
            'GET /tavily/crawl?url=...' => 'TavilyCrawl',
            'GET /tavily/map?url=...' => 'TavilyMap',
        ],
        'note' => 'Set TAVILY_API_KEY in .env to test live API calls.',
    ]);
});

Route::get('/tavily', function () {
    return view('tavily');
});

Route::get('/tavily/search', function () {
    $query = request('query', 'Laravel AI SDK');
    $tool = new TavilySearch;
    $result = $tool->handle(new Request([
        'query' => $query,
        'max_results' => request('max_results'),
        'search_depth' => request('search_depth'),
        'include_answer' => request('include_answer'),
    ]));

    return response()->json([
        'query' => $query,
        'result' => json_decode($result, true) ?? $result,
    ]);
});

Route::get('/tavily/extract', function () {
    $urls = request('urls', 'https://laravel.com');
    $tool = new TavilyExtract;
    $result = $tool->handle(new Request([
        'urls' => $urls,
        'query' => request('query'),
        'extract_depth' => request('extract_depth'),
        'format' => request('format'),
        'include_images' => request('include_images'),
    ]));

    return response()->json([
        'urls' => $urls,
        'result' => json_decode($result, true) ?? $result,
    ]);
});

Route::get('/tavily/crawl', function () {
    $url = request('url', 'https://laravel.com');
    $tool = new TavilyCrawl;
    $result = $tool->handle(new Request([
        'url' => $url,
        'instructions' => request('instructions'),
        'max_depth' => request('max_depth'),
        'max_breadth' => request('max_breadth'),
        'limit' => request('limit'),
        'extract_depth' => request('extract_depth'),
        'allow_external' => request('allow_external'),
    ]));

    return response()->json([
        'url' => $url,
        'result' => json_decode($result, true) ?? $result,
    ]);
});

Route::get('/tavily/map', function () {
    $url = request('url', 'https://laravel.com');
    $tool = new TavilyMap;
    $result = $tool->handle(new Request([
        'url' => $url,
        'instructions' => request('instructions'),
        'max_depth' => request('max_depth'),
        'max_breadth' => request('max_breadth'),
        'limit' => request('limit'),
        'allow_external' => request('allow_external'),
    ]));

    return response()->json([
        'url' => $url,
        'result' => json_decode($result, true) ?? $result,
    ]);
});
