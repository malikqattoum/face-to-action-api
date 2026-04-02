<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token', ['*'], now()->addDays(30))->plainTextToken;
    }

    public function test_user_can_list_their_logs(): void
    {
        Log::factory()->count(3)->create(['user_id' => $this->user->id]);
        Log::factory()->count(2)->create(); // other user's logs

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/logs');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_logs_are_ordered_by_recorded_at_desc(): void
    {
        Log::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'First',
            'recorded_at' => now()->subDays(2),
        ]);
        Log::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Second',
            'recorded_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/logs');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.customer_name', 'Second')
            ->assertJsonPath('data.1.customer_name', 'First');
    }

    public function test_logs_are_paginated(): void
    {
        Log::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/logs');

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25);
    }

    public function test_user_can_create_log_with_audio_mocked(): void
    {
        // Mock the OpenAIService
        $mock = Mockery::mock(AIService::class);
        $mock->shouldReceive('transcribe')
            ->once()
            ->andReturn('Just finished fixing the leak at Mr. Smith\'s house, charged $150, need to return Tuesday for the valve');
        $mock->shouldReceive('extractStructuredData')
            ->once()
            ->andReturn([
                'customer_name' => 'Mr. Smith',
                'action_taken' => 'Fixed leak',
                'amount' => 150,
                'next_steps' => 'Return Tuesday for valve',
            ]);

        $this->app->instance(AIService::class, $mock);

        // Create a fake audio file with proper mime
        $audioFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('test.webm', str_repeat('fake audio content ', 100));

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/logs', [
                'audio' => $audioFile,
                'recorded_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_name', 'Mr. Smith')
            ->assertJsonPath('data.action_taken', 'Fixed leak')
            ->assertJsonPath('data.amount', 150)
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.next_steps', 'Return Tuesday for valve')
            ->assertJsonStructure(['data' => ['id', 'user_id', 'customer_name', 'action_taken', 'amount', 'currency', 'next_steps', 'recorded_at', 'transcribed_text', 'audio_url']]);

        $this->assertDatabaseHas('logs', [
            'user_id' => $this->user->id,
            'customer_name' => 'Mr. Smith',
            'amount' => 150,
        ]);
    }

    public function test_store_log_requires_audio_file(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/logs', [
                'recorded_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_store_log_accepts_only_webm_and_m4a(): void
    {
        $mp3File = \Illuminate\Http\UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/logs', [
                'audio' => $mp3File,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['audio']);
    }

    public function test_user_can_view_single_log(): void
    {
        $log = Log::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Test Customer',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/logs/' . $log->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.customer_name', 'Test Customer');
    }

    public function test_user_cannot_view_other_users_log(): void
    {
        $otherUser = User::factory()->create();
        $log = Log::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/logs/' . $log->id);

        $response->assertStatus(404);
    }

    public function test_user_can_update_log(): void
    {
        $log = Log::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Old Name',
            'amount' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/logs/' . $log->id, [
                'customer_name' => 'New Name',
                'amount' => 200,
                'action_taken' => 'Updated action',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_name', 'New Name')
            ->assertJsonPath('data.amount', 200)
            ->assertJsonPath('data.action_taken', 'Updated action');

        $this->assertDatabaseHas('logs', [
            'id' => $log->id,
            'customer_name' => 'New Name',
            'amount' => 200,
        ]);
    }

    public function test_user_can_update_log_partial(): void
    {
        $log = Log::factory()->create([
            'user_id' => $this->user->id,
            'customer_name' => 'Original Name',
            'amount' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/logs/' . $log->id, [
                'amount' => 250,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_name', 'Original Name')
            ->assertJsonPath('data.amount', 250);
    }

    public function test_user_cannot_update_other_users_log(): void
    {
        $otherUser = User::factory()->create();
        $log = Log::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/logs/' . $log->id, [
                'customer_name' => 'Hacked Name',
            ]);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_log(): void
    {
        $log = Log::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/logs/' . $log->id);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Log deleted successfully');

        $this->assertDatabaseMissing('logs', ['id' => $log->id]);
    }

    public function test_user_cannot_delete_other_users_log(): void
    {
        $otherUser = User::factory()->create();
        $log = Log::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/logs/' . $log->id);

        $response->assertStatus(404);

        $this->assertDatabaseHas('logs', ['id' => $log->id]);
    }

    public function test_unauthenticated_user_cannot_access_logs(): void
    {
        $response = $this->getJson('/api/logs');
        $response->assertStatus(401);

        $response = $this->postJson('/api/logs', []);
        $response->assertStatus(401);
    }
}
