<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'project_id',
        'created_by',
        'defense_date',
        'defense_time',
        'venue',
         'status',
        'is_confirmed',
        'reschedule_requested',
        'requested_defense_date',
        'requested_defense_time',
  
    ];

    protected $casts = [
        'defense_date' => 'date',
        'is_confirmed' => 'boolean',
        'reschedule_requested' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function panelists()
{
    return $this->belongsToMany(User::class, 'schedule_panelists');
}
}