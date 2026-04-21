<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'Administrator';
    public const PANEL_MEMBER = 'Panel Member';
    public const FOCAL_PERSON = 'Focal Person';
    public const DEPARTMENT_CHAIR = 'Department Chairperson';
    public const RESEARCH_COORDINATOR = 'Research Coordinator';
    public const ADVISER = 'Adviser';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(User::class,'role_user','role_id','user_id');
    }
}
