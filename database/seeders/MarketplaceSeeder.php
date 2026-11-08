<?php

namespace Database\Seeders;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Web development', 'slug' => 'web-development', 'description' => 'Websites, applications, APIs, and technical builds.', 'sort_order' => 10],
            ['name' => 'Design', 'slug' => 'design', 'description' => 'Brand, product, and interface design.', 'sort_order' => 20],
            ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Launch, content, and growth support.', 'sort_order' => 30],
            ['name' => 'Writing', 'slug' => 'writing', 'description' => 'Copy, documentation, and editorial work.', 'sort_order' => 40],
            ['name' => 'Video and animation', 'slug' => 'video-animation', 'description' => 'Editing, motion, and product video.', 'sort_order' => 50],
            ['name' => 'AI and automation', 'slug' => 'ai-automation', 'description' => 'AI workflows, agents, and operational automation.', 'sort_order' => 60],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ],
            );
        }

        $seller = User::query()->where('email', 'seller@dealflow.test')->firstOrFail();

        $services = [
            [
                'category_slug' => 'web-development',
                'title' => 'Laravel API and dashboard build',
                'description' => 'A production-ready Laravel API, authentication, and a focused React dashboard for your internal workflow.',
                'deliverables' => ['Laravel API with tests', 'React dashboard', 'Deployment notes', 'One handover call'],
                'price' => 1200,
                'delivery_days' => 14,
                'revisions' => 2,
                'includes' => 'API design, implementation, automated tests, and deployment notes.',
                'requirements' => 'A short brief, access to a staging environment, and a decision-maker for scope questions.',
                'supporting_info' => 'Built with Laravel, Inertia, React, and PostgreSQL-compatible migrations.',
            ],
            [
                'category_slug' => 'design',
                'title' => 'Landing page design and build',
                'description' => 'A focused landing page for a product launch, including copy structure, responsive design, and a production build.',
                'deliverables' => ['Responsive landing page', 'Copy structure', 'Production build', 'Two revision rounds'],
                'price' => 850,
                'delivery_days' => 7,
                'revisions' => 2,
                'includes' => 'One page, responsive implementation, and two revision rounds.',
                'requirements' => 'Brand assets, product details, and one approval contact.',
                'supporting_info' => 'Designed for clarity and conversion, not a template swap.',
            ],
            [
                'category_slug' => 'ai-automation',
                'title' => 'AI workflow automation audit',
                'description' => 'A practical audit of one workflow, with a prioritised automation map and a working proof of concept.',
                'deliverables' => ['Workflow map', 'Automation opportunities', 'Working proof of concept', 'Handover document'],
                'price' => 600,
                'delivery_days' => 5,
                'revisions' => 1,
                'includes' => 'One workflow, one proof of concept, and a handover document.',
                'requirements' => 'A described workflow, sample data, and access to the tools involved.',
                'supporting_info' => 'Useful before committing to a larger agent build.',
            ],
        ];

        foreach ($services as $service) {
            $category = Category::query()->where('slug', $service['category_slug'])->firstOrFail();

            Service::query()->updateOrCreate(
                ['slug' => Str::slug($service['title'])],
                [
                    'seller_id' => $seller->id,
                    'category_id' => $category->id,
                    'title' => $service['title'],
                    'description' => $service['description'],
                    'deliverables' => $service['deliverables'],
                    'pricing_type' => PricingType::Fixed,
                    'price_minor' => $service['price'] * 100,
                    'currency' => 'USD',
                    'includes' => $service['includes'],
                    'delivery_days' => $service['delivery_days'],
                    'revisions' => $service['revisions'],
                    'exclusions' => null,
                    'requirements' => $service['requirements'],
                    'images' => null,
                    'supporting_info' => $service['supporting_info'],
                    'negotiation_enabled' => true,
                    'status' => ServiceStatus::Published,
                    'published_at' => now(),
                ],
            );
        }
    }
}
