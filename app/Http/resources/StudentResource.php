<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'age' => $this->age,
            'gender' => $this->gender,
            'course_id' => $this->course_id,
            'year_level' => $this->year_level,
            'status' => $this->status,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'course_name' => $this->course?->course_name,
                'course_code' => $this->course?->course_code,
                'department' => $this->course?->department,
            ]),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

