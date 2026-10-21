<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemReport extends Model
{
    use HasFactory;

    /**
     * Atribut yang dijaga dari mass assignment (kolom status alur resolusi dilindungi).
     */
    protected $guarded = [
        'id',
        'status',
    ];

    /**
     * Relasi ke barang hilang/ditemukan yang menjadi target laporan penemuan.
     */
    public function lostItem()
    {
        return $this->belongsTo(LostItem::class, 'lost_item_id');
    }

    /**
     * Relasi ke akun user pelapor pihak ketiga.
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /**
     * Helper status laporan penemuan.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
