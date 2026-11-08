<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Inertia\Inertia;
use Inertia\Response;

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
}
