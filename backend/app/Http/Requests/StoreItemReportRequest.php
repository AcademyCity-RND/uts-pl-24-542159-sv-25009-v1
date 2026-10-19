<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemReportRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna berhak melakukan permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk laporan penemuan pihak ketiga.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Pesan kustom untuk error validasi.
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Pesan laporan penemuan wajib diisi.',
            'message.min' => 'Pesan laporan minimal 10 karakter.',
            'message.max' => 'Pesan laporan maksimal 2000 karakter.',
        ];
    }
}
