<?php

namespace App\Policies;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ItemReportPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar seluruh laporan pihak ketiga.
     * Hanya administrator yang diizinkan.
     */
    public function viewAny(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Akses ditolak. Hanya administrator yang berhak mengakses data laporan penemuan.');
    }

    /**
     * Menentukan apakah pengguna dapat membuat laporan penemuan pihak ketiga.
     * Pemilik barang dilarang melaporkan barangnya sendiri (Anti Self-Reporting).
     */
    public function create(User $user, LostItem $item): Response
    {
        return ($user->id !== $item->user_id)
            ? Response::allow()
            : Response::deny('Anda tidak dapat membuat laporan penemuan untuk barang milik sendiri.');
    }

    /**
     * Menentukan apakah pengguna dapat memproses status laporan penemuan.
     * Hanya administrator yang diizinkan.
     */
    public function updateStatus(User $user, ?ItemReport $report = null): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Hanya administrator yang berhak mengubah status laporan penemuan.');
    }
}
