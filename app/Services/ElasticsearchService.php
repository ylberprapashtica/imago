<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class ElasticsearchClientWrapper
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search($params)
    {
        return $this->client->search($params);
    }

    public function indices()
    {
        return $this->client->indices();
    }
}

class ElasticsearchService
{
    private ElasticsearchClientWrapper $client;
    private string $index;

    public const FIELD_EDITED_IMAGE = 'bearbeitet_bild';
    public const FIELD_IMAGE_NUMBER = 'bildnummer';
    public const FIELD_WIDTH = 'breite';
    public const FIELD_DATE = 'datum';
    public const FIELD_DATABASE = 'db';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_PHOTOGRAPHERS = 'fotografen';
    public const FIELD_HEIGHT = 'hoehe';
    public const FIELD_SEARCH_TEXT = 'suchtext';
    public const FIELD_TITLE = 'title';

    public function __construct(ElasticsearchClientWrapper $client)
    {
        $this->client = $client;
        $this->index = env('ELASTICSEARCH_INDEX', 'imago');
    }

    public function createIndex(): void
    {
        $mappings = [
            'mappings' => [
                'properties' => [
                    self::FIELD_EDITED_IMAGE => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    self::FIELD_IMAGE_NUMBER => ['type' => 'integer'],
                    self::FIELD_WIDTH => ['type' => 'integer'],
                    self::FIELD_DATE => ['type' => 'date'],
                    self::FIELD_DATABASE => ['type' => 'keyword'],
                    self::FIELD_DESCRIPTION => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    self::FIELD_PHOTOGRAPHERS => ['type' => 'keyword'],
                    self::FIELD_HEIGHT => ['type' => 'integer'],
                    self::FIELD_SEARCH_TEXT => [
                        'type' => 'text',
                        'fields' => [
                            'english' => [
                                'type' => 'text',
                                'analyzer' => 'english'
                            ],
                            'german' => [
                                'type' => 'text',
                                'analyzer' => 'german'
                            ]
                        ]
                    ],
                    self::FIELD_TITLE => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            if (!$this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->client->indices()->create([
                    'index' => $this->index,
                    'body' => $mappings
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating index:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    function search(
        string | null $query, 
        array $photographers, 
        int $page, 
        int $perPage, 
        ?string $startDate = null, 
        ?string $endDate = null
    ): array {        
        try {
            $params = [
                'index' => $this->index,
                'body' => [
                    'from' => ($page - 1) * $perPage,
                    'size' => $perPage,
                    'query' => [
                        'bool' => [
                            'must' => []
                        ]
                    ],
                    'aggs' => [
                        self::FIELD_PHOTOGRAPHERS => [
                            'terms' => [
                                'field' => self::FIELD_PHOTOGRAPHERS,
                                'size' => 10
                            ]
                        ]
                    ]
                ]
            ];

            if (!empty($query)) {
                $params['body']['query']['bool']['must'][] = [
                    'bool' => [
                        'should' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => [
                                        self::FIELD_SEARCH_TEXT . '.english^1',
                                        self::FIELD_SEARCH_TEXT . '.german^1'
                                    ]
                                ]
                            ],
                            [
                                'match' => [
                                    self::FIELD_TITLE => [
                                        'query' => $query,
                                        'boost' => 2
                                    ]
                                ]
                            ],
                            [
                                'match' => [
                                    self::FIELD_DESCRIPTION => [
                                        'query' => $query,
                                        'boost' => 1.5
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }
 
            if (!empty($photographers)) {
                $params['body']['query']['bool']['must'][] = [
                    'terms' => [self::FIELD_PHOTOGRAPHERS => $photographers]
                ];
            }

            if ($startDate || $endDate) {
                $dateRange = ['range' => [self::FIELD_DATE => []]];
                
                if ($startDate) {
                    $dateRange['range'][self::FIELD_DATE]['gte'] = $startDate;
                }
                
                if ($endDate) {
                    $dateRange['range'][self::FIELD_DATE]['lte'] = $endDate;
                }
                
                $params['body']['query']['bool']['must'][] = $dateRange;
            }

            Log::debug('Elasticsearch search parameters:', ['params' => $params]);

            $response = $this->client->search($params);
            return $response->asArray();
        } catch (\Exception $e) {
            Log::error('Error searching documents:', [
                'error' => $e->getMessage(),
                'params' => $params ?? []
            ]);
            throw $e;
        }
    }

    public function getPhotographers(): array
    {
        try {
            $response = $this->client->search([
                'index' => $this->index,
                'body' => [
                    'size' => 0,
                    'aggs' => [
                        'unique_photographers' => [
                            'terms' => [
                                'field' => self::FIELD_PHOTOGRAPHERS,
                                'size' => 100,
                                'order' => ['_count' => 'desc']
                            ]
                        ]
                    ]
                ]
            ]);

            if (!isset($response['aggregations']['unique_photographers']['buckets'])) {
                return [];
            }

            return ($response['aggregations']['unique_photographers']['buckets']);
        } catch (\Exception $e) {
            Log::error('Error fetching photographers from Elasticsearch:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 