<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'abstract',
        'project_type',
        'user_id',
        'department_id',
        'college_id',
        'academic_year',
        'semester',
        'status',
        'adviser_id',
        'slug',
        'description',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($project) {
            if (empty($project->slug) && !empty($project->title)) {
                $project->slug = static::generateUniqueSlug($project->title);
            }
        });

        static::updating(function ($project) {
            if (empty($project->slug) && !empty($project->title)) {
                $project->slug = static::generateUniqueSlug($project->title, $project->id);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if (empty($baseSlug)) {
            $baseSlug = 'project';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            static::withTrashed()
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id')->withTrashed();
    }

    public function researchers()
    {
        return $this->belongsToMany(
            User::class,
            'project_researchers',
            'project_id',
            'user_id'
        )->withTrashed()->withTimestamps();
    }

    public function panelists()
    {
        return $this->belongsToMany(
            User::class,
            'project_panelists',
            'project_id',
            'panelist_id'
        )->withTrashed()->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function schedule()
    {
        return $this->hasOne(\App\Models\Schedule::class);
    }

   public function manuscript()
    {
        return $this->hasOne(ProjectManuscript::class, 'project_id')->withTrashed();
    }


    public function isApproved()
    {
        return $this->adviser_id !== null;
    }

    public function isCompleted(): bool
    {
        return strtolower((string) $this->status) === 'completed' || !is_null($this->completed_at);
    }
}