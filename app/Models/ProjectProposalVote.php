<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectProposalVote extends Model
{
    protected $fillable = [
        'project_id',
        'faculty_id',
        'proposal_index',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
}