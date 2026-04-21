<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'college_id',
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function forms()
    {
        return $this->hasMany(Form::class);
    }

    public function chair()
    {
        return $this->hasOne(User::class, 'department_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Department Chairperson');
            });
    }
}