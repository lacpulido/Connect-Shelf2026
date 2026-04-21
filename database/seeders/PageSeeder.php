<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class PageSeeder extends Seeder
{
    public function run()
    {
        Page::create([
            'title' => 'Introduction to Laravel Scout',
            'content' => 'Laravel Scout allows you to integrate full-text search engines easily.',
            'url' => '/pages/laravel-scout',
            'status' => 'published',
            'category' => 'Tech',
            'site_id' => 1,
        ]);

        Page::create([
            'title' => 'Getting Started with Meilisearch',
            'content' => 'Meilisearch is a lightning-fast, open-source search engine.',
            'url' => '/pages/meilisearch-guide',
            'status' => 'published',
            'category' => 'Tech',
            'site_id' => 1,
        ]);

        Page::create([
            'title' => 'Tips for Full-Text Search',
            'content' => 'Using typo tolerance and semantic search can improve search results.',
            'url' => '/pages/search-tips',
            'status' => 'published',
            'category' => 'Tech',
            'site_id' => 1,
        ]);
    }
}
