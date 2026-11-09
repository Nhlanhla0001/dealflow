<?php

namespace Tests\Feature\Wizards;

use App\Support\Wizard\SellerListingWizard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SellerListingMediaTest extends TestCase
{
    public function test_the_media_step_accepts_uploaded_storage_paths(): void
    {
        $wizard = app(SellerListingWizard::class);

        $validated = $wizard->validateStep('media', [
            'image_urls' => ['/storage/service-images/1/work.jpg'],
        ]);

        $this->assertSame(
            ['/storage/service-images/1/work.jpg'],
            $validated['image_urls'],
        );
    }

    public function test_the_media_step_accepts_six_images(): void
    {
        $wizard = app(SellerListingWizard::class);

        $images = array_map(
            fn (int $index): string => "/storage/service-images/1/work-{$index}.jpg",
            range(1, 6),
        );

        $validated = $wizard->validateStep('media', ['image_urls' => $images]);

        $this->assertCount(6, $validated['image_urls']);
    }

    public function test_the_media_step_rejects_a_seventh_image(): void
    {
        $wizard = app(SellerListingWizard::class);

        $images = array_map(
            fn (int $index): string => "/storage/service-images/1/work-{$index}.jpg",
            range(1, 7),
        );

        $this->expectException(ValidationException::class);

        $wizard->validateStep('media', ['image_urls' => $images]);
    }

    public function test_the_media_step_rejects_non_http_urls(): void
    {
        $wizard = app(SellerListingWizard::class);

        $this->expectException(ValidationException::class);

        $wizard->validateStep('media', [
            'image_urls' => ['javascript:alert(1)'],
        ]);
    }
}
