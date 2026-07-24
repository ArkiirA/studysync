<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Summarize text into a bullet-point summary. Returns an array of
     * plain-text bullet strings (already stripped of markdown "- " prefixes).
     */
    public function summarize(string $text): array
    {
        $prompt = <<<PROMPT
            Summarize the following lecture notes or reading into a concise,
            student-friendly bullet-point summary. Aim for 5-10 bullets that
            capture the key concepts, not a paraphrase of every sentence.

            TEXT:
            {$text}
            PROMPT;

        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'bullets' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING'],
                        ],
                    ],
                    'required' => ['bullets'],
                ],
            ],
        ];

        $data = $this->call($body);

        return $data['bullets'] ?? [];
    }

    /**
     * Generate flashcard Q&A pairs from notes.
     * Returns an array of ['question' => string, 'answer' => string].
     */
    public function generateFlashcards(string $text): array
    {
        $prompt = <<<PROMPT
            Turn the following study notes into flashcards: clear question-and-answer
            pairs that test understanding of the key concepts, definitions, and facts.
            Generate between 6 and 12 cards depending on how much material there is.
            Keep answers concise (1-2 sentences).

            NOTES:
            {$text}
            PROMPT;

        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'question' => ['type' => 'STRING'],
                            'answer' => ['type' => 'STRING'],
                        ],
                        'required' => ['question', 'answer'],
                    ],
                ],
            ],
        ];

        $data = $this->call($body);

        // Top-level response is the array itself here (no wrapper object),
        // since the schema's root type is ARRAY rather than OBJECT.
        return is_array($data) ? $data : [];
    }

    /**
     * @return array Decoded JSON matching the requested responseSchema.
     */
    protected function call(array $body): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not set — add it to .env.');
        }

        $response = Http::timeout(30)
            ->withHeader('x-goog-api-key', $this->apiKey)
            ->withHeader('Content-Type', 'application/json')
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", $body);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        $rawText = $response->json('candidates.0.content.parts.0.text');

        if (! $rawText) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $decoded = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Could not parse Gemini\'s response as JSON.');
        }

        return $decoded;
    }
}
