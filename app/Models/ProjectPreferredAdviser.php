<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPreferredAdviser extends Model
{
    protected $fillable = [
        'project_id',
        'adviser_id',
        'preference_order',
        'status',
    ];

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}