<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class JigsawStack
{
    /**
     * @return Collection<int, Tool>
     */
    public static function all(): Collection
    {
        return new Collection([
            new JigsawStackSentiment,
            new JigsawStackSummary,
            new JigsawStackEmbedding,
            new JigsawStackPrediction,
            new JigsawStackTextToSql,
            new JigsawStackTranslateText,
            new JigsawStackTranslateImage,
            new JigsawStackWebSearch,
            new JigsawStackAiScrape,
            new JigsawStackHtmlToAny,
            new JigsawStackSearchSuggestions,
            new JigsawStackVocr,
            new JigsawStackObjectDetection,
            new JigsawStackSpeechToText,
            new JigsawStackNsfwDetection,
            new JigsawStackProfanityCheck,
            new JigsawStackSpellCheck,
            new JigsawStackSpamCheck,
        ]);
    }
}
