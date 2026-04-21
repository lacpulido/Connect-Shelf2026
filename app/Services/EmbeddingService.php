<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function embed(string $text): array
    {
        return $this->generate($text);
    }

    public function generate(string $text): array
    {
        try {
           $response = Http::timeout(30)->post(
    config('services.ollama.host') . '/api/embeddings',
    [
        'model' => config('services.ollama.model'),
        'prompt' => $text,
    ]
);

            if (!$response->successful()) {
                Log::error('Ollama embedding failed: ' . $response->body());
                throw new \Exception('Ollama request failed');
            }

            $embedding = $response->json()['embedding'] ?? null;

            if (!$embedding || count($embedding) !== 768) {
                throw new \Exception('Invalid embedding size: ' . count($embedding ?? []));
            }

            return $embedding;

        } catch (\Throwable $e) {
            Log::error('Embedding exception: ' . $e->getMessage());
            throw $e; // ❗ DO NOT silently fail
        }
    }
}