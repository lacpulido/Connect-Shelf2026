<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'project_document_id',
        'adviser_id',
        'comment',
        'decision',
    ];

    public function document()
    {
        return $this->belongsTo(ProjectDocument::class,'project_document_id');
    }

    public function adviser()
    {
        return $this->belongsTo(User::class,'adviser_id');
    }
}