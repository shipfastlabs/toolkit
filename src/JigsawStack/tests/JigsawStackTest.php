<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStack;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackAiScrape;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackEmbedding;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackHtmlToAny;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackNsfwDetection;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackObjectDetection;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackPrediction;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackProfanityCheck;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSearchSuggestions;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSentiment;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpamCheck;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpeechToText;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSpellCheck;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackSummary;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTextToSql;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTranslateImage;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackTranslateText;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackVocr;
use Shipfastlabs\Toolkit\JigsawStack\JigsawStackWebSearch;

it('creates a collection of all JigsawStack tools', function (): void {
    $tools = JigsawStack::all();

    expect($tools)->toBeInstanceOf(Collection::class)
        ->and($tools)->toHaveCount(18)
        ->and($tools->all())->toContainOnlyInstancesOf(Tool::class);
});

it('includes each JigsawStack tool exactly once', function (): void {
    $classes = JigsawStack::all()->map(fn ($tool): string => $tool::class);

    expect($classes->all())->toEqualCanonicalizing([
        JigsawStackSentiment::class,
        JigsawStackSummary::class,
        JigsawStackEmbedding::class,
        JigsawStackPrediction::class,
        JigsawStackTextToSql::class,
        JigsawStackTranslateText::class,
        JigsawStackTranslateImage::class,
        JigsawStackWebSearch::class,
        JigsawStackAiScrape::class,
        JigsawStackHtmlToAny::class,
        JigsawStackSearchSuggestions::class,
        JigsawStackVocr::class,
        JigsawStackObjectDetection::class,
        JigsawStackSpeechToText::class,
        JigsawStackNsfwDetection::class,
        JigsawStackProfanityCheck::class,
        JigsawStackSpellCheck::class,
        JigsawStackSpamCheck::class,
    ]);
});
