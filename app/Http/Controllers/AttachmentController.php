<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    private const PURPOSES = ['message', 'service_image', 'avatar'];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,zip'],
            'purpose' => ['required', 'string', 'in:'.implode(',', self::PURPOSES)],
        ]);

        $user = $request->user();
        $purpose = $validated['purpose'];
        $file = $request->file('file');

        if (in_array($purpose, ['service_image', 'avatar'], true)) {
            $mime = $file->getMimeType() ?? '';

            if (! str_starts_with($mime, 'image/')) {
                throw ValidationException::withMessages([
                    'file' => 'Please upload an image (JPG, PNG, or WebP).',
                ]);
            }

            $dimensions = @getimagesize($file->getRealPath());

            if ($dimensions !== false && ($dimensions[0] > 4096 || $dimensions[1] > 4096)) {
                throw ValidationException::withMessages([
                    'file' => 'Images must be 4096 pixels or smaller on each side.',
                ]);
            }
        }

        $disk = $purpose === 'message' ? 'local' : 'public';

        $folder = [
            'message' => 'message-attachments',
            'service_image' => 'service-images',
            'avatar' => 'avatars',
        ][$purpose] ?? 'attachments';

        $path = $file->store($folder.'/'.$user->id, $disk);

        if ($purpose === 'avatar') {
            $this->forgetPreviousAvatars($user);
        }

        $attachment = Attachment::create([
            'user_id' => $user->id,
            'attachable_type' => $purpose === 'avatar' ? User::class : null,
            'attachable_id' => $purpose === 'avatar' ? $user->id : null,
            'collection' => $purpose,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
        ]);

        return response()->json([
            'attachment' => [
                'id' => $attachment->id,
                'url' => $attachment->url(),
                'name' => $attachment->original_name,
                'mime' => $attachment->mime,
                'size' => $attachment->size,
                'collection' => $attachment->collection,
            ],
        ], 201);
    }

    public function show(Request $request, Attachment $attachment): StreamedResponse|RedirectResponse
    {
        if ($attachment->isPublic()) {
            return redirect('/storage/'.ltrim($attachment->path, '/'));
        }

        $user = $request->user();

        abort_if($user === null, 403);

        $allowed = $attachment->user_id === $user->id;

        if (! $allowed && $attachment->attachable instanceof Message) {
            $allowed = $attachment->attachable->transaction->isParty($user);
        }

        abort_unless($allowed, 403);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime],
        );
    }

    private function forgetPreviousAvatars(User $user): void
    {
        $previous = Attachment::query()
            ->where('attachable_type', User::class)
            ->where('attachable_id', $user->id)
            ->where('collection', 'avatar')
            ->get();

        foreach ($previous as $avatar) {
            Storage::disk($avatar->disk)->delete($avatar->path);
            $avatar->delete();
        }
    }
}
