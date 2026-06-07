<?php

declare(strict_types=1);

namespace Shipfastlabs\Toolkit\JigsawStack;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Shipfastlabs\Toolkit\JigsawStack\Concerns\InteractsWithJigsawStack;

#[Strict]
class JigsawStackSpeechToText implements Tool
{
    use InteractsWithJigsawStack;

    public function description(): string
    {
        return <<<'TEXT'
            Transcribe spoken audio or video into text using JigsawStack.
            Optionally translate the transcription into English.
            TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema
                ->string()
                ->description('The URL of the audio or video file to transcribe')
                ->required(),
            'language' => $schema
                ->string()
                ->description("The language code of the audio, or 'auto' to detect it (default: 'auto')")
                ->nullable()
                ->required(),
            'translate' => $schema
                ->boolean()
                ->description('Whether to translate the transcription into English (default: false)')
                ->nullable()
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $request->filled('url')) {
            return 'The URL is empty. Provide an audio or video URL to transcribe.';
        }

        $url = trim((string) $request->string('url'));

        $payload = $this->withOptional($request, ['url' => $url], 'language', 'translate');

        return $this->request('post', 'v1/ai/transcribe', $payload);
    }
}
