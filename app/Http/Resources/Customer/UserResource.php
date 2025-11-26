<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('profile', 'roles');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'status' => $this->status?->value ?? null,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'phone' => $this->profile->phone,
                    'cnic' => $this->profile->cnic,
                    'gender' => $this->profile->gender?->value ?? $this->profile->gender,
                    'date_of_birth' => $this->profile->date_of_birth?->format('Y-m-d'),
                    'address' => $this->profile->address,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
