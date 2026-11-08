<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NegotiationDirectiveController extends Controller
{
    public function store(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $session = $transaction->negotiationSession;

        abort_if($session === null, 422, 'Start a negotiation before messaging your agent.');

        $directive = $session->directives()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        return response()->json([
            'directive' => [
                'id' => $directive->id,
                'body' => $directive->body,
                'created_at' => $directive->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
