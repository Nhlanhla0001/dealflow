<?php

namespace App\Http\Controllers;

use App\Actions\Agreements\ApproveAgreement;
use App\Actions\Agreements\RejectAgreement;
use App\Models\Agreement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

class AgreementApprovalController extends Controller
{
    public function store(
        Request $request,
        Agreement $agreement,
        ApproveAgreement $approveAgreement,
    ): RedirectResponse {
        $this->authorize('approve', $agreement);

        try {
            $approveAgreement->handle($agreement, $request->user());
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Agreement approval recorded.'),
        ]);

        return to_route('transactions.show', $agreement->transaction_id);
    }

    public function reject(
        Request $request,
        Agreement $agreement,
        RejectAgreement $rejectAgreement,
    ): RedirectResponse {
        $this->authorize('reject', $agreement);

        try {
            $rejectAgreement->handle($agreement, $request->user());
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('Terms rejected. The transaction was cancelled.'),
        ]);

        return to_route('transactions.show', $agreement->transaction_id);
    }
}
