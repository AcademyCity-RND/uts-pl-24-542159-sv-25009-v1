<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LostItemDetailResource extends JsonResource
{
    /**
     * Transformasi resource data barang ke representasi array terproteksi.
     * Privasi Bertingkat: contact_info dan reporter_contact_info hanya disertakan
     * kepada pemilik barang (owner) atau Admin.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwnerOrAdmin = $user && ($user->isAdmin() || $user->id === $this->user_id);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'role' => $this->user->role,
                ];
            }),
            'item_name' => $this->item_name,
            'category' => $this->category,
            'location' => $this->location,
            'current_storage' => $this->current_storage,
            'chronology' => $this->chronology,
            'status' => $this->status,
            'image_path' => $this->image_path,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'expires_at' => $this->expires_at,
            'is_expired' => $this->is_expired,
            'remaining_seconds' => $this->remaining_seconds,
            // Privasi Kontak Bertingkat (Omitted jika bukan owner dan bukan admin)
            'contact_info' => $this->when($isOwnerOrAdmin, $this->contact_info),
            'reporter_contact_info' => $this->when($isOwnerOrAdmin, $this->reporter_contact_info),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
