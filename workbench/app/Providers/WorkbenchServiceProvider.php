<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Console\Commands\AgentRun;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (function_exists('Orchestra\Testbench\package_path')
            && is_file($path = \Orchestra\Testbench\package_path('.env'))) {
            Dotenv::create(Env::getRepository(), dirname($path), basename($path))->safeLoad();
        }

        config([
            'services.tavily.key' => env('TAVILY_API_KEY'),
            'services.exa.key' => env('EXA_API_KEY'),
            'ai.providers.openai.key' => env('OPENAI_API_KEY'),
        ]);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AgentRun::class,
            ]);
        }
    }
}
