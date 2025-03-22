<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TransformedImageDTO;
use App\Http\Controllers\Controller;
use App\Http\Responses\ImageSearchResponse;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    protected $imageService;
    protected $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch)
    {
        $this->middleware('auth:sanctum');
        $this->elasticsearch = $elasticsearch;
    }

    public function index(Request $request)
    {
        try {
            $results = $this->elasticsearch->search('', [], 1, 20);
            return $this->transformResults($results);
        } catch (\Exception $e) {
            Log::error('Error fetching images:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error fetching images'], 500);
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $perPage = $request->input('per_page', 12);
        $page = $request->input('page', 1);
        $photographers = $request->input('photographers') ? explode(',', $request->input('photographers')) : [];
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        Log::debug('Search request:', [
            'query' => $query,
            'per_page' => $perPage,
            'page' => $page,
            'photographers' => $photographers,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        try {
            $results = $this->elasticsearch->search($query, $photographers, $page, $perPage, $startDate, $endDate);
            return new ImageSearchResponse(
                transformedItems: $this->transformResults($results),
                currentPage: $page,
                perPage: $perPage,
                total: $results['hits']['total']['value'] ?? 0,
                photographers: $results['aggregations'][ElasticsearchService::FIELD_PHOTOGRAPHERS]['buckets'] ?? [],
            );
        } catch (\Exception $e) {
            Log::error('Error searching images:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search_params' => $searchParams ?? null
            ]);
            return response()->json(['error' => 'Error searching images'], 500);
        }
    }

    public function getPhotographers()
    {
        try {
            Log::info('Fetching photographers from Elasticsearch');
            $photographers = $this->elasticsearch->getPhotographers();
            Log::debug('Extracted photographers:', ['photographers' => $photographers]);
            return response()->json($photographers);
        } catch (\Exception $e) {
            Log::error('Error fetching photographers:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function transformResults($results): Collection
    {
        return $transformedItems = collect($results['hits']['hits'])->map(function ($hit) {
            $source = $hit['_source'] ?? [];
            $mediaId = str_pad($source[ElasticsearchService::FIELD_IMAGE_NUMBER] ?? '', 10, '0', STR_PAD_LEFT);
            $db = $source[ElasticsearchService::FIELD_DATABASE] ?? 'st';
            
            return TransformedImageDTO::fromArray([
                'id' => $source[ElasticsearchService::FIELD_IMAGE_NUMBER] ?? null,
                'title' => $source[ElasticsearchService::FIELD_TITLE] ?? '',
                'description' => $source[ElasticsearchService::FIELD_DESCRIPTION] ?? '',
                'search_text' => $source[ElasticsearchService::FIELD_SEARCH_TEXT] ?? '',
                'edited_image' => "https://www.imago-images.de/bild/{$db}/{$mediaId}/s.jpg",
                'photographers' => is_array($source[ElasticsearchService::FIELD_PHOTOGRAPHERS]) 
                    ? $source[ElasticsearchService::FIELD_PHOTOGRAPHERS] 
                    : [$source[ElasticsearchService::FIELD_PHOTOGRAPHERS] ?? ''],
                'database' => $db,
                'date' => $source[ElasticsearchService::FIELD_DATE] ?? null,
                'dimensions' => [
                    'width' => $source[ElasticsearchService::FIELD_WIDTH] ?? 0,
                    'height' => $source[ElasticsearchService::FIELD_HEIGHT] ?? 0
                ]
            ]);
        })->filter(function ($item) {
            return !empty($item->id);
        });
    }
} 