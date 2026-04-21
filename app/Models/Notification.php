<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
   protected $fillable = [
    'user_id',
    'title',
    'message',
    'type',
    'reference_id',
    'reference_type',
    'status',
    'program',
];

    protected $casts = [
        'created_at' => 'datetime',
       
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}