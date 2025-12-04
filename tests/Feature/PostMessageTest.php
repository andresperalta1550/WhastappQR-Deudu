<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Actions\StoreMessageAction;
use Mockery;
use App\Models\Message;

class PostMessageTest extends TestCase
{
    public function test_it_can_upload_file_and_send_message()
    {
        $this->withoutExceptionHandling();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');

        // Mock the StoreMessageAction to avoid actual external calls
        $this->mock(StoreMessageAction::class, function ($mock) {
            $mock->shouldReceive('handle')->once()->andReturn(new Message());
        });

        $response = $this->postJson('/api/v1/messages', [
            'message' => [
                'info' => [
                    'to' => '1234567890',
                    'type' => 'text',
                    'coordination_id' => 1,
                    'debtor_id' => 1,
                ],
                'data' => [
                    'text' => 'Hello World',
                ],
            ],
            'file' => $file,
        ]);

        $response->assertStatus(201);

        // Get all files in the uploads directory
        $files = Storage::disk('public')->files('uploads');
        $this->assertCount(1, $files);
        $this->assertTrue(str_ends_with($files[0], '.jpg'));

        // Since we mocked the action, we can't easily check the URL passed to it without more complex mocking,
        // but the existence of the file confirms the controller logic for storage worked.
        // To be more precise, we could spy on the action.
    }
}
