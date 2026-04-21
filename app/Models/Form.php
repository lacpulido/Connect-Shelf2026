<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
   protected $fillable = [
    'title',
    'file_name',   
    'department_id',
    'section'
];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}