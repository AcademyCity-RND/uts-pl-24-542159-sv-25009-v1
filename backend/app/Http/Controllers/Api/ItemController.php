<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLostItemRequest;
use App\Http\Requests\UpdateLostItemRequest;
use App\Http\Resources\LostItemDetailResource;
use App\Models\ItemReport;
use App\Models\LostItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Menampilkan daftar barang untuk pengguna terautentikasi.
     * Mendukung filter ?status=...&page=1&per_page=10.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);
        $query = LostItem::with('user')->latest('id');

        if ($user->isAdmin()) {
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        } else {
            if ($request->filled('status')) {
                $status = $request->input('status');
                if ($status === 'approved' && !$request->boolean('mine')) {
                    $query->where('status', 'approved');
                } else {
                    $query->where('user_id', $user->id)->where('status', $status);
                }
            } else {
                // Default tanpa status: user biasa melihat barang miliknya sendiri
                $query->where('user_id', $user->id);
            }
        }

        $items = $query->paginate($perPage);

        return LostItemDetailResource::collection($items)->additional([
            'message' => 'Daftar barang berhasil diambil.',
        ]);
    }

    /**
     * Menampilkan detail 1 barang secara lengkap.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $item = LostItem::with('user')->findOrFail($id);

        return (new LostItemDetailResource($item))->additional([
            'message' => 'Detail barang berhasil diambil.',
        ])->response();
    }

    /**
     * Membuat laporan barang baru (otomatis berstatus 'pending').
     */
    public function store(StoreLostItemRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('lost_items', 'public');
        }

        $item = new LostItem([
            'item_name' => $validated['item_name'],
            'category' => $validated['category'],
            'location' => $validated['location'],
            'current_storage' => $validated['current_storage'],
            'contact_info' => $validated['contact_info'],
            'reporter_contact_info' => $validated['reporter_contact_info'] ?? null,
            'chronology' => $validated['chronology'],
            'image_path' => $imagePath,
        ]);
        $item->user_id = $user->id;
        $item->status = 'pending';
        $item->save();

        $item->load('user');

        return (new LostItemDetailResource($item))->additional([
            'message' => 'Laporan barang berhasil dibuat dan menunggu persetujuan admin.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Memperbarui data laporan barang (mendukung _method: 'PUT').
     * Jika status sebelumnya adalah 'rejected', sistem otomatis mereset status menjadi 'pending'.
     */
    public function update(UpdateLostItemRequest $request, int $id): JsonResponse
    {
        $item = LostItem::with('user')->findOrFail($id);

        // Otorisasi melalui LostItemPolicy (hanya pemilik atau admin)
        Gate::authorize('update', $item);

        // Aturan State: Hanya barang pending atau rejected yang dapat diperbarui
        if (!in_array($item->status, ['pending', 'rejected'])) {
            return response()->json([
                'message' => 'Barang dengan status ' . $item->status . ' tidak dapat diperbarui.',
            ], 409);
        }

        $validated = $request->validated();

        // Unggah gambar baru jika disertakan
        if ($request->hasFile('image')) {
            if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                Storage::disk('public')->delete($item->image_path);
            }
            $item->image_path = $request->file('image')->store('lost_items', 'public');
        }

        if (isset($validated['item_name'])) $item->item_name = $validated['item_name'];
        if (isset($validated['category'])) $item->category = $validated['category'];
        if (isset($validated['location'])) $item->location = $validated['location'];
        if (isset($validated['current_storage'])) $item->current_storage = $validated['current_storage'];
        if (isset($validated['contact_info'])) $item->contact_info = $validated['contact_info'];
        if (array_key_exists('reporter_contact_info', $validated)) $item->reporter_contact_info = $validated['reporter_contact_info'];
        if (isset($validated['chronology'])) $item->chronology = $validated['chronology'];

        // Aturan Otomatis: jika status sebelumnya rejected, reset menjadi pending untuk review admin
        if ($item->status === 'rejected') {
            $item->status = 'pending';
            $item->approved_at = null;
        }

        $item->save();

        return (new LostItemDetailResource($item))->additional([
            'message' => 'Data barang berhasil diperbarui.',
        ])->response();
    }

    /**
     * Menghapus data laporan barang (hanya pemilik atau admin).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = LostItem::findOrFail($id);

        // Otorisasi melalui LostItemPolicy (hanya pemilik atau admin)
        Gate::authorize('delete', $item);

        if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return response()->json([
            'message' => 'Data laporan barang berhasil dihapus.',
        ], 200);
    }

    /**
     * Memperbarui status barang sesuai State Transition Matrix resmi PRD.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $item = LostItem::with('user')->findOrFail($id);
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:approved,rejected,resolved'],
        ], [
            'status.required' => 'Status baru wajib diisi.',
            'status.in' => 'Status baru hanya boleh bernilai approved, rejected, atau resolved.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi status gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStatus = $request->input('status');
        $currentStatus = $item->status;

        // 1. Ditolak jika transisi ke status yang identik
        if ($newStatus === $currentStatus) {
            return response()->json([
                'message' => "Barang sudah berada pada status {$currentStatus}.",
            ], 409);
        }

        // 2. Evaluasi Matriks Transisi Resmi (PRD Bagian C):
        // Matrix:
        // pending -> approved (Aktor: Admin)
        // pending -> rejected (Aktor: Admin)
        // approved -> resolved (Aktor: Owner)
        // resolve_requested -> resolved (Aktor: Owner)
        // resolve_requested -> approved (Aktor: Owner)
        $isPendingToApproveOrReject = ($currentStatus === 'pending' && in_array($newStatus, ['approved', 'rejected']));
        $isApprovedToResolved = ($currentStatus === 'approved' && $newStatus === 'resolved');
        $isResolveRequestedDecision = ($currentStatus === 'resolve_requested' && in_array($newStatus, ['approved', 'resolved']));

        if (!$isPendingToApproveOrReject && !$isApprovedToResolved && !$isResolveRequestedDecision) {
            return response()->json([
                'message' => "Transisi status dari {$currentStatus} menjadi {$newStatus} dilarang oleh State Transition Matrix.",
            ], 409);
        }

        // 3. Validasi Hak Akses Aktor per Transisi Spesifik:
        if ($isPendingToApproveOrReject) {
            if (!$user->isAdmin()) {
                return response()->json([
                    'message' => "Hanya admin yang berhak mengubah status barang dari pending menjadi {$newStatus}.",
                ], 403);
            }
        } elseif ($isApprovedToResolved || $isResolveRequestedDecision) {
            if ($user->id !== $item->user_id) {
                return response()->json([
                    'message' => "Hanya pemilik barang (owner) yang berhak mengubah status dari {$currentStatus} menjadi {$newStatus}.",
                ], 403);
            }
        }

        // 4. Terapkan Transisi Status dan Dampak Timestamp Resmi:
        $item->status = $newStatus;
        if ($currentStatus === 'pending' && $newStatus === 'approved') {
            $item->approved_at = now();
            $item->resolved_at = null;
        } elseif ($currentStatus === 'pending' && $newStatus === 'rejected') {
            $item->approved_at = null;
            $item->resolved_at = null;
        } elseif ($isApprovedToResolved || ($currentStatus === 'resolve_requested' && $newStatus === 'resolved')) {
            $item->resolved_at = now();
            // approved_at tetap waktu asli
        } elseif ($currentStatus === 'resolve_requested' && $newStatus === 'approved') {
            // resolve_requested -> approved: approved_at TETAP Waktu Asli, resolved_at = null
            $item->resolved_at = null;
            // Otomatis ubah laporan berstatus accepted menjadi closed (PRD Skenario 10)
            ItemReport::where('lost_item_id', $item->id)
                ->where('status', 'accepted')
                ->update(['status' => 'closed']);
        }

        $item->save();

        return (new LostItemDetailResource($item))->additional([
            'message' => "Status barang berhasil diperbarui menjadi {$newStatus}.",
        ])->response();
    }

    /**
     * Memperpanjang masa tayang barang yang kedaluwarsa (> 72 jam) atau berstatus resolved (PRD Skenario 11).
     */
    public function repost(Request $request, int $id): JsonResponse
    {
        $item = LostItem::with('user')->findOrFail($id);

        // 1. Hak Akses via LostItemPolicy: Hanya pemilik barang (owner)
        Gate::authorize('repost', $item);

        // 2. Ketentuan Repost (PRD Bagian C Baris 272-273 & Baris 324):
        // Repost hanya boleh jika berstatus resolved ATAU berstatus approved tapi sudah kedaluwarsa (> 72 jam)
        $isResolved = ($item->status === 'resolved');
        $isExpiredApproved = ($item->status === 'approved' && $item->is_expired);

        if (!$isResolved && !$isExpiredApproved) {
            return response()->json([
                'message' => 'Batas waktu perpanjangan (repost) belum terpenuhi. Repost hanya diizinkan untuk barang yang telah kedaluwarsa (> 72 jam) atau berstatus resolved.',
            ], 409);
        }

        if ($item->last_reposted_at && $item->last_reposted_at->greaterThan(now()->subHours(72))) {
            return response()->json([
                'message' => 'Repost hanya dapat dilakukan maksimal satu kali dalam 72 jam.',
            ], 409);
        }

        // 3. Eksekusi Repost: approved_at = now(), resolved_at = null, status = approved
        $item->status = 'approved';
        $item->approved_at = now();
        $item->resolved_at = null;
        $item->last_reposted_at = now();
        $item->save();

        return (new LostItemDetailResource($item))->additional([
            'message' => 'Masa tayang barang berhasil diperpanjang (repost).',
        ])->response();
    }
}
