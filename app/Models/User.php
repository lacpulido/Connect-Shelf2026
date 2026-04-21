<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'expertise',
        'college_id',
        'department_id',
        'user_type',
        'email',
        'password',
        'status',
        'is_active',
        'deactivated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        );
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function projects()
    {
        return $this->belongsToMany(
            Project::class,
            'project_researchers',
            'user_id',
            'project_id'
        )->withTimestamps();
    }

   public function panelProjects()
{
    return $this->belongsToMany(
        Project::class,
        'project_panelists',
        'panelist_id',
        'project_id'
    )->withTimestamps();
}
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('name', $role)
            ->exists();
    }

    public function isAdministrator(): bool
    {
        return (int) $this->user_type === 1 && $this->hasRole('Administrator');
    }

    public function isDepartmentChair(): bool
    {
        return $this->hasRole('Department Chairperson');
    }

    public function isFocalPerson(): bool
    {
        return $this->hasRole('Focal Person');
    }

   // public function getFullNameAttribute(): string
   // {
       // return trim(implode(' ', array_filter([
           // $this->first_name,
           // $this->middle_name,
            //$this->last_name,
           // $this->extension_name,
      //  ])));
   // }
    public function getFullNameAttribute(): string
{
    return trim("{$this->first_name} {$this->last_name}") ?: 'N/A';
}
}