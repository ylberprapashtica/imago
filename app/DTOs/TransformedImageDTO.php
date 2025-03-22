<?php

namespace App\DTOs;

class TransformedImageDTO
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $search_text,
        public readonly string $edited_image,
        public readonly array $photographers,
        public readonly string $database,
        public readonly ?string $date,
        public readonly array $dimensions,
    ) {
        if (!isset($this->dimensions['width']) || !isset($this->dimensions['height'])) {
            throw new \InvalidArgumentException('Dimensions must contain both width and height');
        }
    }

    public static function fromArray(array $data): self
    {
        $dimensions = $data['dimensions'] ?? [];
        if (!isset($dimensions['width']) || !isset($dimensions['height'])) {
            $dimensions = [
                'width' => $dimensions['width'] ?? 0,
                'height' => $dimensions['height'] ?? 0,
            ];
        }

        return new self(
            id: $data['id'] ?? null,
            title: $data['title'] ?? '',
            description: $data['description'] ?? '',
            search_text: $data['search_text'] ?? '',
            edited_image: $data['edited_image'] ?? '',
            photographers: $data['photographers'] ?? [],
            database: $data['database'] ?? 'st',
            date: $data['date'] ?? null,
            dimensions: $dimensions,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'search_text' => $this->search_text,
            'edited_image' => $this->edited_image,
            'photographers' => $this->photographers,
            'database' => $this->database,
            'date' => $this->date,
            'dimensions' => $this->dimensions,
        ];
    }
} 