<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ImageController;
use App\Services\ElasticsearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use App\Models\User;

class ImageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $elasticsearchMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->elasticsearchMock = Mockery::mock(ElasticsearchService::class);
        $this->app->instance(ElasticsearchService::class, $this->elasticsearchMock);

        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function testIndexReturnsImages()
    {
        $this->elasticsearchMock
            ->shouldReceive('search')
            ->once()
            ->andReturn(['hits' => ['hits' => []]]);

        $response = $this->getJson('/api/images');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function testSearchReturnsResults()
    {
        $this->elasticsearchMock
            ->shouldReceive('search')
            ->with('query', [], 1, 12, null, null)
            ->once()
            ->andReturn(['hits' => ['hits' => [], 'total' => ['value' => 0]], 'aggregations' => []]);

        $response = $this->getJson('/api/images/search?q=query');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'per_page',
                'total'
            ],
            'aggregations' => [
                'photographers'
            ]
        ]);
    }

    public function testGetPhotographersReturnsList()
    {
        $this->elasticsearchMock
            ->shouldReceive('getPhotographers')
            ->once()
            ->andReturn([]);

        $response = $this->getJson('/api/images/photographers');

        $response->assertStatus(200);
        $response->assertJson([]);
    }
} 