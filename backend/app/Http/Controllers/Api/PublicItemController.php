<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LostItemPublicResource;
use App\Models\LostItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicItemController extends Controller
{
    /**
     * Menampilkan daftar barang aktif yang disetujui (approved) dan belum kedaluwarsa (< 72 jam).
     * Endpoint terbuka untuk publik / tamu dengan privasi kontak terproteksi.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);

        // Filter aktif pasif: status = 'approved' dan approved_at > now() - 72 jam
        $items = LostItem::activePublic()
            ->latest('approved_at')
            ->paginate($perPage);

        return LostItemPublicResource::collection($items)->additional([
            'message' => 'Daftar barang publik berhasil diambil.',
        ]);
    }
}
