<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role ?? null,
            'student_id' => $this->student_id ?? null,
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}

