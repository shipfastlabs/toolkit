<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tavily Toolkit Tester</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 1.5rem; }
        h2 { font-size: 1.2rem; margin-top: 2rem; }
        .tool { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin: 16px 0; }
        input, select, button { padding: 8px 12px; font-size: 1rem; }
        input[type="text"] { width: 300px; }
        button { background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: #4338ca; }
        .result { background: #f3f4f6; padding: 12px; border-radius: 6px; margin-top: 12px; white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow-y: auto; }
        .error { color: #dc2626; }
        .note { background: #fef3c7; padding: 12px; border-radius: 6px; margin-bottom: 24px; }
    </style>
</head>
<body>
    <h1>Tavily Toolkit Tester</h1>

    <div class="note">
        <strong>Note:</strong> Set <code>TAVILY_API_KEY</code> in your <code>.env</code> file to test live API calls.
        Without it, tools will return a configuration error.
    </div>

    <div class="tool">
        <h2>TavilySearch</h2>
        <input type="text" id="search-query" value="Laravel AI SDK" placeholder="Search query">
        <button onclick="testTool('search')">Search</button>
        <div class="result" id="search-result"></div>
    </div>

    <div class="tool">
        <h2>TavilyExtract</h2>
        <input type="text" id="extract-urls" value="https://laravel.com" placeholder="Comma-separated URLs">
        <button onclick="testTool('extract')">Extract</button>
        <div class="result" id="extract-result"></div>
    </div>

    <div class="tool">
        <h2>TavilyCrawl</h2>
        <input type="text" id="crawl-url" value="https://laravel.com" placeholder="Root URL">
        <button onclick="testTool('crawl')">Crawl</button>
        <div class="result" id="crawl-result"></div>
    </div>

    <div class="tool">
        <h2>TavilyMap</h2>
        <input type="text" id="map-url" value="https://laravel.com" placeholder="Root URL">
        <button onclick="testTool('map')">Map</button>
        <div class="result" id="map-result"></div>
    </div>

    <script>
        async function testTool(tool) {
            const resultEl = document.getElementById(tool + '-result');
            resultEl.textContent = 'Loading...';

            let url = '/tavily/' + tool;
            const params = new URLSearchParams();

            if (tool === 'search') {
                params.append('query', document.getElementById('search-query').value);
            } else if (tool === 'extract') {
                params.append('urls', document.getElementById('extract-urls').value);
            } else {
                params.append('url', document.getElementById(tool + '-url').value);
            }

            try {
                const res = await fetch(url + '?' + params.toString());
                const data = await res.json();
                resultEl.textContent = JSON.stringify(data, null, 2);
            } catch (e) {
                resultEl.textContent = 'Error: ' + e.message;
                resultEl.classList.add('error');
            }
        }
    </script>
</body>
</html>
