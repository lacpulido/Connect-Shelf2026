<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ProjectDocument extends Model
{
    use Searchable;

    protected $fillable = [
        'title',
        'slug',
        'status',
        'project_id',
        'filename',
        'is_current',
        'parent_id',
        'version',
    ];

    protected $casts = [
        'embedding' => 'array', // important for Typesense
    ];

     //Automatically Generate Slug
  

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($document) {

            if (empty($document->filename) || empty($document->title)) {
                return;
            }

            // Get first word of title
            $titleParts = explode(' ', strtolower($document->title));
            $folder = Str::slug($titleParts[0]);

            // Remove file extension
            $fileName = pathinfo($document->filename, PATHINFO_FILENAME);

            // Convert filename to slug
            $fileSlug = Str::slug($fileName);

            // Final slug
            $document->slug = $folder . '/' . $fileSlug;
        });
    }

    //Relationships
   

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function latestComment()
    {
        return $this->hasOne(Comment::class)->latestOfMany();
    }

  //Route Model Binding
    

    public function getRouteKeyName()
    {
        return 'slug';
    }

     //Typesense Schema
  

    public function typesenseCollectionSchema(): array
    {
        return [
            'name' => 'project_documents',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'abstract', 'type' => 'string'],
                ['name' => 'embedding', 'type' => 'float[]', 'num_dim' => 768],
                ['name' => 'created_at', 'type' => 'int64'],
                ['name' => 'is_current', 'type' => 'int32'],
                ['name' => 'project_id', 'type' => 'int32'],
            ],
            'default_sorting_field' => 'created_at',
        ];
    }

   //Data Sent to Typesense
   

    public function toSearchableArray(): array
    {
        return [
            'id'         => (string) $this->id,
            'title'      => $this->title ?? '',
            'abstract'   => $this->description ?? '', // description is your abstract
            'embedding'  => $this->embedding ?? array_fill(0, 768, 0),
            'created_at' => optional($this->created_at)->timestamp ?? time(),
            'is_current' => (int) $this->is_current,
            'project_id' => (int) $this->project_id,
        ];
    }

  
    //Typesense Index Name
   

    public function searchableAs(): string
    {
        return 'project_documents';
    }
}