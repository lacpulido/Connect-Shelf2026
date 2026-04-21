<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Page extends Model
{
    use Searchable;

    /**
     * Only include published pages in the search index
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Data to index in Meilisearch (selective fields)
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'url' => $this->url,
            // Optionally add more fields if needed
            //'category' => $this->category,
            //'site_id' => $this->site_id,
        ];
    }

    /**
     * Advanced Meilisearch settings for highlights and cropping
     */
    public function searchableSettings(): array
    {
        return [
            'attributesToSearch' => ['title', 'content'],
            'attributesToRetrieve' => ['id', 'title', 'url', 'snippet'],
            'attributesToHighlight' => ['title', 'content'],
            'attributesToCrop' => ['content'],
            'cropLength' => 200,
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
        ];
    }
}
