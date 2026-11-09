<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Attachment;
use App\Models\Message;
use App\Models\Transaction;
use App\Notifications\MessageReceived;
use App\Services\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function __construct(private readonly MessageThread $thread) {}

    public function index(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $user = $request->user();

        $unread = $this->thread->unreadCount($transaction, $user);

        $this->thread->markRead($transaction, $user);

        $messages = $transaction->messages()
            ->with(['user:id,name', 'attachments'])
            ->get()
            ->map(fn (Message $message): array => $message->toPayload())
            ->values();

        return response()->json([
            'messages' => $messages,
            'unread' => $unread,
        ]);
    }

    public function store(StoreMessageRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $user = $request->user();

        $message = DB::transaction(function () use ($request, $transaction, $user): Message {
            $message = $transaction->messages()->create([
                'user_id' => $user->id,
                'body' => trim($request->string('body')->toString()),
            ]);

            $attachmentIds = $request->array('attachment_ids');

            if ($attachmentIds !== []) {
                Attachment::query()
                    ->whereIn('id', $attachmentIds)
                    ->where('user_id', $user->id)
                    ->where('collection', 'message')
                    ->where('disk', 'local')
                    ->whereNull('attachable_id')
                    ->update([
                        'attachable_type' => Message::class,
                        'attachable_id' => $message->id,
                        'collection' => 'message',
                    ]);
            }

            return $message;
        });

        $transaction->counterparty($user)?->notify(
            new MessageReceived($transaction, $user, $message),
        );

        return response()->json([
            'message' => $message->load(['user:id,name', 'attachments'])->toPayload(),
        ], 201);
    }
}
