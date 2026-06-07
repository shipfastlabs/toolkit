<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;
use Workbench\App\Ai\Agents\ToolkitAgent;

class AgentRun extends Command
{
    protected $signature = 'agent:run
                            {prompt : The prompt to send to the agent}
                            {--provider=openai : The AI provider to use}
                            {--model=gpt-5.4-mini : The model to use}';

    protected $description = 'Run the toolkit agent (all toolkit tools) against a prompt.';

    public function handle(): int
    {
        try {
            $response = ToolkitAgent::make()->prompt(
                $this->argument('prompt'),
                provider: $this->option('provider'),
                model: $this->option('model') ?: null,
            );
        } catch (Throwable $throwable) {
            $this->components->error('Agent failed: '.$throwable->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Agent response');
        $this->line($response->text);

        $this->newLine();
        $this->components->info('Tool calls');

        foreach ($response->toolCalls as $call) {
            $this->components->twoColumnDetail(
                $call->name,
                (string) json_encode($call->arguments, JSON_UNESCAPED_SLASHES),
            );
        }

        foreach ($response->toolResults as $result) {
            $this->components->twoColumnDetail(
                'result :: '.$result->name,
                (string) str(is_string($result->result) ? $result->result : (string) json_encode($result->result))->limit(120),
            );
        }

        return self::SUCCESS;
    }
}
