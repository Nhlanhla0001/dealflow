<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $services = Service::query()
            ->published()
            ->latest('published_at')
            ->get(['slug', 'updated_at']);

        $categories = Category::query()
            ->active()
            ->ordered()
            ->get(['slug', 'updated_at']);

        return response()->view('sitemap', [
            'services' => $services,
            'categories' => $categories,
        ])->header('Content-Type', 'application/xml');
    }
}
