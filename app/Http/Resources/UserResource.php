<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => "{$this->first_name} {$this->last_name}",
            'email' => $this->email,
            'user_type' => $this->user_type,
            'department' => $this->department->name ?? null,
        ];
    }
}