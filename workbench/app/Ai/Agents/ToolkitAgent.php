<?php

declare(strict_types=1);

namespace Workbench\App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Shipfastlabs\Toolkit\Calculator\CalculatorTool;
use Shipfastlabs\Toolkit\Database\DatabaseQueryTool;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStack;
use Shipfastlabs\Toolkit\Tavily\Tavily;

/**
 * @phpstan-consistent-constructor
 */
class ToolkitAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'TXT'
            You are a helpful assistant with access to a set of tools.
            Always prefer calling the appropriate tool over answering from memory,
            and explain which tool you used and why.
            TXT;
    }

    /**
     * @return array<int, Tool>
     */
    public function tools(): iterable
    {
        return [
            new CalculatorTool,
            new DatabaseQueryTool,
            ...Tavily::all(),
            ...JigsawStack::all(),
        ];
    }
}
