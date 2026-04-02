<?php

namespace Tests\Feature;

use App\Models\CallSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_user_can_list_call_sessions(): void
    {
        CallSession::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/calls', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_call_sessions_ordered_by_started_at_desc(): void
    {
        CallSession::factory()->create([
            'user_id' => $this->user->id,
            'started_at' => now()->subDay(),
            'contact_name' => 'Old Call',
        ]);
        CallSession::factory()->create([
            'user_id' => $this->user->id,
            'started_at' => now(),
            'contact_name' => 'New Call',
        ]);

        $response = $this->getJson('/api/calls', $this->authHeaders());

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('New Call', $data[0]['contact_name']);
        $this->assertEquals('Old Call', $data[1]['contact_name']);
    }

    public function test_user_can_create_call_session(): void
    {
        $payload = [
            'contact_name' => 'Mr. Smith',
            'phone_number' => '+1234567890',
            'direction' => 'incoming',
            'duration_seconds' => 300,
            'started_at' => now()->toIso8601String(),
            'ended_at' => now()->addMinutes(5)->toIso8601String(),
            'notes' => 'Discussed the repair',
        ];

        $response = $this->postJson('/api/calls', $payload, $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.contact_name', 'Mr. Smith')
            ->assertJsonPath('data.direction', 'incoming')
            ->assertJsonPath('data.duration_seconds', 300);

        $this->assertDatabaseHas('call_sessions', [
            'user_id' => $this->user->id,
            'contact_name' => 'Mr. Smith',
        ]);
    }

    public function test_user_can_create_call_session_with_minimal_data(): void
    {
        $payload = ['direction' => 'missed'];

        $response = $this->postJson('/api/calls', $payload, $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.direction', 'missed');
    }

    public function test_user_can_view_single_call_session(): void
    {
        $call = CallSession::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/calls/{$call->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $call->id);
    }

    public function test_user_cannot_view_other_users_call_session(): void
    {
        $otherUser = User::factory()->create();
        $call = CallSession::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/calls/{$call->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_user_can_update_call_session(): void
    {
        $call = CallSession::factory()->create(['user_id' => $this->user->id, 'notes' => 'Old notes']);

        $response = $this->putJson("/api/calls/{$call->id}", ['notes' => 'New notes'], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.notes', 'New notes');
    }

    public function test_user_can_update_call_direction(): void
    {
        $call = CallSession::factory()->create(['user_id' => $this->user->id, 'direction' => 'missed']);

        $response = $this->putJson("/api/calls/{$call->id}", ['direction' => 'outgoing'], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.direction', 'outgoing');
    }

    public function test_user_can_delete_call_session(): void
    {
        $call = CallSession::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/calls/{$call->id}", [], $this->authHeaders());

        $response->assertOk();
        $this->assertDatabaseMissing('call_sessions', ['id' => $call->id]);
    }

    public function test_user_can_delete_other_users_call_session(): void
    {
        $otherUser = User::factory()->create();
        $call = CallSession::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/calls/{$call->id}", [], $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_user_can_attach_memo_to_call(): void
    {
        $call = CallSession::factory()->create(['user_id' => $this->user->id, 'has_voice_memo' => false]);

        $response = $this->postJson("/api/calls/{$call->id}/memo", [], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.has_voice_memo', true);
    }

    public function test_call_direction_must_be_valid(): void
    {
        $response = $this->postJson('/api/calls', ['direction' => 'invalid'], $this->authHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['direction']);
    }

    public function test_unauthenticated_user_cannot_access_calls(): void
    {
        $this->getJson('/api/calls')->assertUnauthorized();
        $this->postJson('/api/calls', [])->assertUnauthorized();
    }
}
