<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectManuscriptSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = trim($request->get('q'));

            if (! $query) {
                return response()->json([
                    'data' => [],
                ]);
            }

            $embedding = app(\App\Services\EmbeddingService::class)->embed($query);

            if (empty($embedding) || count($embedding) !== 768) {
                Log::error('Invalid embedding returned', [
                    'count' => is_array($embedding) ? count($embedding) : 0,
                ]);

                return response()->json([
                    'data' => [],
                ]);
            }

            $cacheKey = 'semantic_search_' . md5($query);

            $data = Cache::remember($cacheKey, 60, function () use ($query, $embedding) {
                $embeddingString = '[' . implode(',', $embedding) . ']';

                $response = Http::withHeaders([
                    'X-TYPESENSE-API-KEY' => env('TYPESENSE_API_KEY'),
                ])->post(
                    env('TYPESENSE_PROTOCOL', 'http') . '://' .
                    env('TYPESENSE_HOST', 'typesense') . ':' .
                    env('TYPESENSE_PORT', '8108') . '/multi_search',
                    [
                        'searches' => [
                            [
                                'collection' => 'project_manuscripts',
                                'q' => $query,
                                'query_by' => 'title,abstract',
                                'query_by_weights' => '4,2',
                                'vector_query' => "embedding:($embeddingString, k:10, alpha:0.3, distance_threshold:0.2)",
                                'prefix' => true,
                                'num_typos' => 2,
                                'per_page' => 10,
                            ],
                        ],
                    ]
                );

                if (! $response->successful()) {
                    Log::error('Typesense Error', [
                        'body' => $response->body(),
                    ]);

                    return [
                        'results' => [
                            [
                                'hits' => [],
                            ],
                        ],
                    ];
                }

                return $response->json();
            });

            $hits = $data['results'][0]['hits'] ?? [];

            $results = collect($hits)
                ->map(function ($hit) {
                    $doc = $hit['document'] ?? [];
                    $manuscriptId = $doc['id'] ?? null;

                    return [
                        'id' => $manuscriptId,
                        'manuscript_id' => $manuscriptId,
                        'title' => $doc['title'] ?? 'No title',
                        'snippet' => Str::limit($doc['abstract'] ?? '', 200),
                        'abstract' => $doc['abstract'] ?? '',
                        'project_type' => $doc['project_type'] ?? 'N/A',
                        'academic_year' => $doc['academic_year'] ?? 'N/A',
                        'department' => $doc['department'] ?? null,
                        'filename' => $doc['filename'] ?? null,
                        'download_url' => $manuscriptId
                            ? route('manuscripts.download', $manuscriptId)
                            : null,
                        'vector_distance' => $hit['_vector_distance'] ?? null,
                        'text_score' => $hit['_text_match'] ?? null,
                    ];
                })
                ->values();

            return response()->json([
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('SEARCH ERROR', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Search failed',
                'data' => [],
            ], 500);
        }
    }
}