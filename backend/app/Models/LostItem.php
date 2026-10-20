<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostItem extends Model
{
    use HasFactory;

    /**
     * Atribut yang dijaga dari mass assignment (kolom sensitif & siklus hidup).
     */
    protected $guarded = [
        'id',
        'status',
        'approved_at',
        'resolved_at',
        'last_reposted_at',
    ];

    /**
     * Type casting atribut tanggal/waktu.
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_reposted_at' => 'datetime',
        ];
    }

    /**
     * Atribut computed yang otomatis disertakan dalam serialisasi array/JSON.
     */
    protected $appends = [
        'expires_at',
        'is_expired',
        'remaining_seconds',
    ];

    /**
     * Relasi ke pemilik/pelapor postingan barang.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke seluruh laporan penemuan pihak ketiga.
     */
    public function reports()
    {
        return $this->hasMany(ItemReport::class, 'lost_item_id');
    }

    /**
     * Alias relasi reports sesuai rujukan kontrak PRD.
     */
    public function itemReports()
    {
        return $this->reports();
    }

    /**
     * Accessor: Waktu kedaluwarsa pasif barang (approved_at + 72 jam) format ISO 8601.
     */
    public function getExpiresAtAttribute(): ?string
    {
        if (!$this->approved_at) {
            return null;
        }

        return $this->approved_at->copy()->addHours(72)->toIso8601String();
    }

    /**
     * Accessor: Status apakah masa aktif barang sudah kedaluwarsa (> 72 jam).
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->approved_at) {
            return false;
        }

        return now()->greaterThan($this->approved_at->copy()->addHours(72));
    }

    /**
     * Accessor: Sisa detik hitung mundur masa aktif barang.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->approved_at || $this->is_expired) {
            return 0;
        }

        $expiry = $this->approved_at->copy()->addHours(72);

        return max(0, (int) now()->diffInSeconds($expiry, false));
    }

    /**
     * Scope: Menyaring barang yang aktif di papan publik (approved dan usia < 72 jam).
     */
    public function scopeActivePublic($query)
    {
        return $query->where('status', 'approved')
                     ->where('approved_at', '>', now()->subHours(72));
    }
}
