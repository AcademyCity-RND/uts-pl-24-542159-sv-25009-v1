<?php

namespace App\Policies;

use App\Models\LostItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LostItemPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar barang.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat detail barang.
     */
    public function view(User $user, LostItem $item): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat membuat postingan barang baru.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data barang.
     * Hanya pemilik barang (owner) atau Admin yang diizinkan.
     */
    public function update(User $user, LostItem $item): Response
    {
        return ($user->isAdmin() || $user->id === $item->user_id)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki hak akses untuk mengubah data barang ini.');
    }

    /**
     * Menentukan apakah pengguna dapat menghapus postingan barang.
     * Hanya pemilik barang (owner) atau Admin yang diizinkan.
     */
    public function delete(User $user, LostItem $item): Response
    {
        return ($user->isAdmin() || $user->id === $item->user_id)
            ? Response::allow()
            : Response::deny('Anda tidak memiliki hak akses untuk menghapus data barang ini.');
    }

    /**
     * Menentukan apakah pengguna dapat memperpanjang masa tayang (repost).
     * Hanya pemilik barang (owner) yang diizinkan.
     */
    public function repost(User $user, LostItem $item): Response
    {
        return ($user->id === $item->user_id)
            ? Response::allow()
            : Response::deny('Hanya pemilik barang (owner) yang berhak memperpanjang masa tayang (repost).');
    }
}
