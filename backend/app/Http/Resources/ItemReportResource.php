<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemReportResource extends JsonResource
{
    /**
     * Transformasi resource data laporan penemuan pihak ketiga ke array JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lost_item_id' => $this->lost_item_id,
            'lost_item' => $this->whenLoaded('lostItem', function () {
                return [
                    'id' => $this->lostItem->id,
                    'user_id' => $this->lostItem->user_id,
                    'item_name' => $this->lostItem->item_name,
                    'category' => $this->lostItem->category,
                    'status' => $this->lostItem->status,
                    'location' => $this->lostItem->location,
                    'image_path' => $this->lostItem->image_path,
                    'approved_at' => $this->lostItem->approved_at?->toIso8601String(),
                ];
            }),
            'reporter_user_id' => $this->reporter_user_id,
            'reporter' => $this->whenLoaded('reporter', function () {
                return [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                    'email' => $this->reporter->email,
                ];
            }),
            'message' => $this->message,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
