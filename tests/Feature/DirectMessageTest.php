<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DirectMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_direct_conversation_and_send_message(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        $other = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user, 'api');

        $conversationResponse = $this->postJson('/api/dm/conversations', [
            'type' => 'direct',
            'participant_ids' => [$other->id],
        ])->assertCreated();

        $conversationId = $conversationResponse->json('id');
        $this->assertIsInt($conversationId);

        $this->postJson("/api/dm/conversations/{$conversationId}/messages", [
            'body' => 'こんにちは',
        ])->assertCreated()->assertJsonFragment([
            'body' => 'こんにちは',
        ]);

        $this->getJson('/api/dm/conversations')
            ->assertOk()
            ->assertJsonStructure([
                'items',
            ]);

        $this->getJson("/api/dm/conversations/{$conversationId}/messages")
            ->assertOk()
            ->assertJsonFragment([
                'body' => 'こんにちは',
            ]);
    }
}
