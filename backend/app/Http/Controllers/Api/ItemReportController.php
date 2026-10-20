<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemReportRequest;
use App\Http\Resources\ItemReportResource;
use App\Models\ItemReport;
use App\Models\LostItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemReportController extends Controller
{
    /**
     * Menampilkan daftar seluruh laporan pihak ketiga (khusus Admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Proteksi Akses: Hanya Admin (PRD Skenario 4: 403 Forbidden untuk user biasa)
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya administrator yang berhak mengakses data laporan penemuan.',
            ], 403);
        }

        $query = ItemReport::with(['lostItem', 'reporter']);

        // Filter status opsional: pending, accepted, rejected, closed
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);
        $reports = $query->latest('id')->paginate($perPage);

        return ItemReportResource::collection($reports)->response();
    }

    /**
     * Mengajukan laporan penemuan pihak ketiga untuk barang tertentu.
     * Mengimplementasikan Anti-Self Reporting, Anti-Duplicate Pending, dan validasi status barang.
     */
    public function store(StoreItemReportRequest $request, int $id): JsonResponse
    {
        $item = LostItem::findOrFail($id);
        $user = $request->user();

        // 1. Anti Self-Reporting: Pemilik barang dilarang melapor barangnya sendiri (PRD Skenario 5: 403 Forbidden)
        if ($item->user_id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat membuat laporan penemuan untuk barang milik sendiri.',
            ], 403);
        }

        // 2. Ketentuan State Barang: Harus approved dan belum kedaluwarsa (PRD Skenario 7: 409 Conflict)
        if ($item->status !== 'approved' || $item->is_expired) {
            return response()->json([
                'message' => 'Laporan penemuan hanya dapat diajukan untuk barang aktif berstatus disetujui (approved) dan belum kedaluwarsa.',
            ], 409);
        }

        // 3. Pencegahan duplikasi resolusi aktif pada barang yang sama
        if ($item->status === 'resolve_requested' || $item->itemReports()->where('status', 'accepted')->exists()) {
            return response()->json([
                'message' => 'Barang ini sedang dalam proses resolusi klaim aktif.',
            ], 409);
        }

        // 4. Anti-Duplicate Pending Report: Maksimal 1 laporan pending per user pada barang yang sama (PRD Skenario 6: 409 Conflict)
        $hasPendingReport = ItemReport::where('lost_item_id', $item->id)
            ->where('reporter_user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingReport) {
            return response()->json([
                'message' => 'Anda sudah memiliki laporan aktif yang sedang ditinjau untuk barang ini.',
            ], 409);
        }

        $report = new ItemReport();
        $report->lost_item_id = $item->id;
        $report->reporter_user_id = $user->id;
        $report->message = $request->input('message');
        $report->status = 'pending';
        $report->save();

        return (new ItemReportResource($report->load(['lostItem', 'reporter'])))->additional([
            'message' => 'Laporan penemuan berhasil dikirimkan dan menunggu peninjauan admin.',
        ])->response()->setStatusCode(201);
    }

    /**
     * Memproses status laporan pihak ketiga oleh Admin dengan Dual Pessimistic Locking.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // 1. Proteksi Akses: Hanya Admin
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Hanya administrator yang berhak mengubah status laporan penemuan.',
            ], 403);
        }

        // 2. Status 'closed' dilarang diset secara manual oleh Admin (PRD: 403 Forbidden)
        if ($request->input('status') === 'closed') {
            return response()->json([
                'message' => 'Status closed dilarang diset secara manual oleh Admin.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:accepted,rejected'],
        ], [
            'status.required' => 'Status laporan wajib diisi.',
            'status.in' => 'Status laporan hanya boleh bernilai accepted atau rejected.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi status laporan gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStatus = $request->input('status');

        // 3. Dual Pessimistic Locking di dalam Database Transaction
        return DB::transaction(function () use ($id, $newStatus) {
            $report = ItemReport::lockForUpdate()->find($id);
            if (!$report) {
                return response()->json([
                    'message' => 'Data laporan penemuan tidak ditemukan.',
                ], 404);
            }

            $item = LostItem::lockForUpdate()->find($report->lost_item_id);
            if (!$item) {
                return response()->json([
                    'message' => 'Data barang terkait laporan tidak ditemukan.',
                ], 404);
            }

            // Verifikasi integritas di dalam lock
            if ($report->status !== 'pending') {
                return response()->json([
                    'message' => 'Laporan sudah pernah diproses sebelumnya.',
                ], 409);
            }

            if ($newStatus === 'accepted') {
                // Anti Double Resolution & State Verification (PRD Skenario 8 & 9)
                if ($item->status !== 'approved' || $item->is_expired) {
                    return response()->json([
                        'message' => 'Barang tidak dalam status aktif atau telah kedaluwarsa.',
                    ], 409);
                }

                if ($item->itemReports()->where('status', 'accepted')->exists()) {
                    return response()->json([
                        'message' => 'Barang sudah memiliki proses resolusi aktif.',
                    ], 409);
                }

                $report->status = 'accepted';
                $report->save();

                $item->status = 'resolve_requested';
                $item->save();

                return (new ItemReportResource($report->load(['lostItem', 'reporter'])))->additional([
                    'message' => 'Laporan penemuan berhasil disetujui (accepted) dan status barang menjadi resolve_requested.',
                ])->response();
            }

            // Aksi rejected
            $report->status = 'rejected';
            $report->save();

            return (new ItemReportResource($report->load(['lostItem', 'reporter'])))->additional([
                'message' => 'Laporan penemuan berhasil ditolak (rejected).',
            ])->response();
        });
    }
}
