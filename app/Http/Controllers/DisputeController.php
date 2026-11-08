<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisputeController extends Controller
{
    public function show(Dispute $dispute): Response
    {
        $this->authorize('view', $dispute);

        $dispute->load([
            'order.transaction.buyer:id,name',
            'order.transaction.seller:id,name',
            'opener:id,name',
            'resolver:id,name',
            'events.user:id,name',
        ]);

        return Inertia::render('disputes/show', [
            'dispute' => $dispute,
        ]);
    }

    public function evidence(Dispute $dispute): StreamedResponse
    {
        $this->authorize('view', $dispute);

        abort_if($dispute->evidence_path === null, 404);
        abort_unless(Storage::disk('local')->exists($dispute->evidence_path), 404);

        return Storage::disk('local')->download(
            $dispute->evidence_path,
            $dispute->evidence_original_name ?? 'evidence',
        );
    }
}
