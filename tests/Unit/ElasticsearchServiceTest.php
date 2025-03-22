<?php

namespace Tests\Unit;

use App\Services\ElasticsearchService;
use Elastic\Elasticsearch\Client;
use Mockery;
use stdClass;
use Tests\TestCase;

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

class ElasticsearchServiceTest extends TestCase
{
    protected $elasticsearchClientMock;
    protected $elasticsearchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->elasticsearchClientMock = Mockery::mock(ElasticsearchClientWrapper::class);
        $this->app->instance(ElasticsearchClientWrapper::class, $this->elasticsearchClientMock);
        $this->elasticsearchService = new ElasticsearchService($this->elasticsearchClientMock);
    }

    public function testSearchWithBasicQuery()
    {
        $query = 'test';
        $photographers = [];
        $page = 1;
        $perPage = 10;
        $startDate = null;
        $endDate = null;

        $expectedParams = [
            'index' => 'imago',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'fields' => [
                                                    'suchtext.english^1',
                                                    'suchtext.german^1'
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'title' => [
                                                    'query' => $query,
                                                    'boost' => 2
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'description' => [
                                                    'query' => $query,
                                                    'boost' => 1.5
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'fotografen' => [
                        'terms' => [
                            'field' => 'fotografen',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ];

        $mockResponseData = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0]
            ],
            'aggregations' => []
        ];

        $mockResponse = new class($mockResponseData) extends stdClass {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function asArray() {
                return $this->data;
            }
        };

        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->with(\Mockery::on(function ($params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                return true;
            }))
            ->once()
            ->andReturn($mockResponse);

        $result = $this->elasticsearchService->search($query, $photographers, $page, $perPage, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('aggregations', $result);
    }

    public function testSearchWithPhotographersFilter()
    {
        $query = 'test';
        $photographers = ['photographer1', 'photographer2'];
        $page = 1;
        $perPage = 10;
        $startDate = null;
        $endDate = null;

        $expectedParams = [
            'index' => 'imago',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'fields' => [
                                                    'suchtext.english^1',
                                                    'suchtext.german^1'
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'title' => [
                                                    'query' => $query,
                                                    'boost' => 2
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'description' => [
                                                    'query' => $query,
                                                    'boost' => 1.5
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'terms' => [
                                    'fotografen' => $photographers
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'fotografen' => [
                        'terms' => [
                            'field' => 'fotografen',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ];

        $mockResponseData = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0]
            ],
            'aggregations' => []
        ];

        $mockResponse = new class($mockResponseData) extends stdClass {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function asArray() {
                return $this->data;
            }
        };

        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->with(\Mockery::on(function ($params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                return true;
            }))
            ->once()
            ->andReturn($mockResponse);

        $result = $this->elasticsearchService->search($query, $photographers, $page, $perPage, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('aggregations', $result);
    }

    public function testSearchWithDateRange()
    {
        $query = 'test';
        $photographers = [];
        $page = 1;
        $perPage = 10;
        $startDate = '2024-01-01';
        $endDate = '2024-12-31';

        $expectedParams = [
            'index' => 'imago',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $query,
                                                'fields' => [
                                                    'suchtext.english^1',
                                                    'suchtext.german^1'
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'title' => [
                                                    'query' => $query,
                                                    'boost' => 2
                                                ]
                                            ]
                                        ],
                                        [
                                            'match' => [
                                                'description' => [
                                                    'query' => $query,
                                                    'boost' => 1.5
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'range' => [
                                    'datum' => [
                                        'gte' => $startDate,
                                        'lte' => $endDate
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'aggs' => [
                    'fotografen' => [
                        'terms' => [
                            'field' => 'fotografen',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ];

        $mockResponseData = [
            'hits' => [
                'hits' => [],
                'total' => ['value' => 0]
            ],
            'aggregations' => []
        ];

        $mockResponse = new class($mockResponseData) extends stdClass {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function asArray() {
                return $this->data;
            }
        };

        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->with(\Mockery::on(function ($params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                return true;
            }))
            ->once()
            ->andReturn($mockResponse);

        $result = $this->elasticsearchService->search($query, $photographers, $page, $perPage, $startDate, $endDate);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('aggregations', $result);
    }
}