<?php

namespace Tests\Feature\Attachments;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_upload_a_service_image_and_get_a_public_url(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)
            ->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->image('work.jpg', 1200, 800),
                'purpose' => 'service_image',
            ])
            ->assertCreated()
            ->assertJsonPath('attachment.collection', 'service_image');

        $attachment = Attachment::query()->findOrFail($response->json('attachment.id'));

        $this->assertSame('public', $attachment->disk);
        $this->assertSame($seller->id, $attachment->user_id);
        Storage::disk('public')->assertExists($attachment->path);
        $this->assertStringStartsWith('/storage/', $response->json('attachment.url'));
    }

    public function test_public_attachments_redirect_to_a_host_relative_path(): void
    {
        Storage::fake('public');

        $attachment = Attachment::factory()->publicImage()->create();
        Storage::disk('public')->put($attachment->path, 'image-bytes');

        $this->get(route('attachments.show', $attachment))
            ->assertRedirect('/storage/'.$attachment->path);
    }

    public function test_uploads_reject_disallowed_types_and_oversized_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
                'purpose' => 'message',
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($user)
            ->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->create('huge.pdf', 20480, 'application/pdf'),
                'purpose' => 'message',
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_message_attachments_are_private_to_the_thread(): void
    {
        Storage::fake('local');

        $transaction = Transaction::factory()->create();
        $outsider = User::factory()->create();

        $upload = $this->actingAs($transaction->buyer)
            ->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->image('brief.png'),
                'purpose' => 'message',
            ])
            ->assertCreated();

        $attachmentId = $upload->json('attachment.id');

        $message = $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.messages.store', $transaction), [
                'body' => 'Brief attached.',
                'attachment_ids' => [$attachmentId],
            ])
            ->assertCreated()
            ->json('message');

        $this->assertSame($attachmentId, $message['attachments'][0]['id']);
        $this->assertSame(1, Message::query()->count());

        // The counterparty can download it.
        $this->actingAs($transaction->seller)
            ->get(route('attachments.show', $attachmentId))
            ->assertOk();

        // An outsider cannot.
        $this->actingAs($outsider)
            ->get(route('attachments.show', $attachmentId))
            ->assertForbidden();
    }

    public function test_avatars_attach_to_the_user_and_are_public(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('attachments.store'), [
                'file' => UploadedFile::fake()->image('me.jpg', 400, 400),
                'purpose' => 'avatar',
            ])
            ->assertCreated();

        $attachment = Attachment::query()->findOrFail($response->json('attachment.id'));

        $this->assertSame('avatar', $attachment->collection);
        $this->assertSame(User::class, $attachment->attachable_type);
        $this->assertSame($user->id, $attachment->attachable_id);
        $this->assertStringContainsString('/storage/', $response->json('attachment.url'));
    }
}
