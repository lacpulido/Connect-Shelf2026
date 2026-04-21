<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ProjectManuscript extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        
        'project_id',
        'title',
        'abstract',
        'filename',
        'status'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id')->withTrashed();
    }

    public function searchableAs(): string
    {
        return 'project_manuscripts';
    }

    public function toSearchableArray()
{
    $this->loadMissing('project.department');

    $embedding = app(\App\Services\EmbeddingService::class)
        ->generate($this->title . ' ' . $this->abstract);

    return [
        'id' => (string) $this->id,
        'title' => $this->title,
        'abstract' => $this->abstract,
        'status' => $this->status,
        'project_type' => $this->project->project_type ?? null,
        'academic_year' => $this->project->academic_year ?? null,
        'department' => $this->project->department->name ?? null,
        'filename' => $this->filename,
        'created_at' => optional($this->created_at)->timestamp,
        'embedding' => $embedding,
    ];
}


    public function shouldBeSearchable()
    {
        return $this->status === 'approved';
    }
}
