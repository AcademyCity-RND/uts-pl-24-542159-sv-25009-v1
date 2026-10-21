<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LostItemPublicResource extends JsonResource
{
    /**
     * Transformasi resource data barang ke dalam representasi array publik.
     * Atribut kontak sensitif (contact_info & reporter_contact_info) dihilangkan sama sekali (omitted).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // Catatan Keamanan: contact_info dan reporter_contact_info sengaja di-omit untuk publik
        ];
    }
}
