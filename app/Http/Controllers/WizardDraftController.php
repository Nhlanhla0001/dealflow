<?php

namespace App\Http\Controllers;

use App\Exceptions\WizardConflictException;
use App\Exceptions\WizardExpiredException;
use App\Http\Requests\PublishWizardRequest;
use App\Http\Requests\SaveWizardDraftRequest;
use App\Http\Requests\SaveWizardStepRequest;
use App\Models\WizardDraft;
use App\Services\WizardDraftService;
use Illuminate\Http\JsonResponse;

class WizardDraftController extends Controller
{
    public function update(
        SaveWizardDraftRequest $request,
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): JsonResponse {
        $this->authorize('update', $draft);

        try {
            $fresh = $wizards->saveDraft(
                $draft,
                $request->validated('data'),
                $request->validated('current_step'),
                $request->validated('version'),
            );
        } catch (WizardConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'draft' => $wizards->payload($exception->latest),
            ], 409);
        } catch (WizardExpiredException $exception) {
            return response()->json(['message' => $exception->getMessage()], 410);
        }

        return response()->json(['draft' => $wizards->payload($fresh)]);
    }

    public function step(
        SaveWizardStepRequest $request,
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): JsonResponse {
        $this->authorize('update', $draft);

        try {
            $fresh = $wizards->saveStep(
                $draft,
                $request->validated('step'),
                $request->validated('data'),
                $request->validated('version'),
            );
        } catch (WizardConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'draft' => $wizards->payload($exception->latest),
            ], 409);
        } catch (WizardExpiredException $exception) {
            return response()->json(['message' => $exception->getMessage()], 410);
        }

        return response()->json(['draft' => $wizards->payload($fresh)]);
    }

    public function publish(
        PublishWizardRequest $request,
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): JsonResponse {
        $this->authorize('publish', $draft);

        try {
            $service = $wizards->publish($draft, $request->validated('version'));
        } catch (WizardConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'draft' => $wizards->payload($exception->latest),
            ], 409);
        } catch (WizardExpiredException $exception) {
            return response()->json(['message' => $exception->getMessage()], 410);
        }

        return response()->json([
            'service' => [
                'slug' => $service->slug,
                'url' => route('services.show', $service),
            ],
        ], 201);
    }

    public function destroy(
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): JsonResponse {
        $this->authorize('delete', $draft);

        try {
            $wizards->abandon($draft, $draft->version);
        } catch (WizardConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'draft' => $wizards->payload($exception->latest),
            ], 409);
        }

        return response()->json(['abandoned' => true]);
    }
}
