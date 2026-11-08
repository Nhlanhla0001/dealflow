<?php

namespace App\Http\Controllers;

use App\Services\WizardDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request, WizardDraftService $wizards): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isOnboarded()) {
            return to_route('dashboard');
        }

        $draft = $wizards->startForOnboarding($user);

        return Inertia::render('onboarding/wizard', [
            'draft' => $wizards->payload($draft),
            'dashboardUrl' => route('dashboard'),
        ]);
    }
}
