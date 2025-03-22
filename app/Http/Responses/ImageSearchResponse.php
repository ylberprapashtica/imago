<?php

namespace App\Http\Responses;

use App\DTOs\TransformedImageDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class ImageSearchResponse extends JsonResponse
{
    public function __construct(
        Collection $transformedItems,
        int $currentPage,
        int $perPage,
        int $total,
        array $photographers = []
    ) {
        $data = [
            'data' => $transformedItems->map(fn (TransformedImageDTO $item) => $item->toArray())->values(),
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'aggregations' => [
                'photographers' => $photographers,
            ],
        ];

        parent::__construct($data);
    }
} 