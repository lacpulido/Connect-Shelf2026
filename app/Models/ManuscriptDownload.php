<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManuscriptDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'manuscript_id',
        'project_id',
        'user_id',
        'file_name',
        'ip_address',
        'user_agent',
    ];

    public function manuscript()
    {
        return $this->belongsTo(ProjectManuscript::class, 'manuscript_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}