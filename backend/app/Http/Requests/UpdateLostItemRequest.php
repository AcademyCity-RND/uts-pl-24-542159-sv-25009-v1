<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLostItemRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berhak melakukan permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk pembaruan data barang.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'in:lost,found'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'current_storage' => ['sometimes', 'required', 'string', 'max:255'],
            'contact_info' => ['sometimes', 'required', 'string', 'max:255'],
            'reporter_contact_info' => ['nullable', 'string', 'max:255'],
            'chronology' => ['sometimes', 'required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Pesan kustom untuk error validasi.
     */
    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama barang wajib diisi.',
            'category.required' => 'Kategori barang (hilang/ditemukan) wajib dipilih.',
            'category.in' => 'Kategori barang harus bernilai lost atau found.',
            'location.required' => 'Lokasi kejadian wajib diisi.',
            'current_storage.required' => 'Posisi penyimpanan barang saat ini wajib diisi.',
            'contact_info.required' => 'Informasi kontak penanggung jawab wajib diisi.',
            'chronology.required' => 'Kronologi kejadian wajib diisi.',
            'image.image' => 'Berkas unggahan harus berupa gambar.',
            'image.mimes' => 'Format gambar yang diperbolehkan adalah jpg, jpeg, atau png.',
            'image.max' => 'Ukuran berkas gambar maksimal 2048 KB (2 MB).',
        ];
    }
}
