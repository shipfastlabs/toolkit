<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Ai\AnonymousAgent;
use Shipfastlabs\Toolkit\Tavily\TavilyCrawl;
use Shipfastlabs\Toolkit\Tavily\TavilyExtract;
use Shipfastlabs\Toolkit\Tavily\TavilyMap;
use Shipfastlabs\Toolkit\Tavily\TavilySearch;
use Throwable;

class TavilyAgentCommand extends Command
{
    protected $signature = 'tavily:agent
                            {prompt? : The prompt to send to the agent}
                            {--tool=search : The tool to use (search, extract, crawl, map, all)}
                            {--provider=openai : The AI provider to use}';

    protected $description = 'Test Tavily tools via the Laravel AI SDK agent';

    public function handle(): int
    {
        $prompt = $this->argument('prompt') ?? 'What are the latest developments in Laravel?';
        $toolOption = $this->option('tool');
        $provider = $this->option('provider');

        $tools = match ($toolOption) {
            'search' => [new TavilySearch],
            'extract' => [new TavilyExtract],
            'crawl' => [new TavilyCrawl],
            'map' => [new TavilyMap],
            default => [new TavilySearch, new TavilyExtract, new TavilyCrawl, new TavilyMap],
        };

        $this->info('Creating agent with Tavily tools...');
        $this->line('Tools: '.implode(', ', array_map(fn ($t) => class_basename($t), $tools)));
        $this->line('Provider: '.$provider);
        $this->line('Prompt: '.$prompt);
        $this->newLine();

        $agent = new AnonymousAgent(
            instructions: 'You are a helpful research assistant with access to Tavily web tools. Use the tools when you need real-time data.',
            messages: [],
            tools: $tools,
        );

        try {
            $response = $agent->prompt($prompt, provider: $provider);

            $this->info('Agent Response:');
            $this->line($response->text());
            $this->newLine();

            if ($response->toolCalls() !== []) {
                $this->info('Tool Calls Made:');

                foreach ($response->toolCalls() as $call) {
                    $this->line('  - '.$call->name.'('.json_encode($call->arguments).')');
                }
            }

            if ($response->toolResults() !== []) {
                $this->info('Tool Results:');

                foreach ($response->toolResults() as $result) {
                    $this->line('  - '.class_basename($result->tool).': '.str($result->output)->limit(200));
                }
            }
        } catch (Throwable $e) {
            $this->error('Agent failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
