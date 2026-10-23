<?php

namespace Tests\Unit;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\StoreItemReportRequest;
use App\Http\Requests\StoreLostItemRequest;
use App\Http\Requests\UpdateLostItemRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Memverifikasi aturan validasi RegisterRequest.
     */
    public function test_register_request_validation_rules(): void
    {
        $request = new RegisterRequest();
        $rules = $request->rules();
        $messages = $request->messages();

        // 1. Uji kegagalan validasi (nama kosong, email invalid, password terlalu pendek & tidak ada confirmation)
        $validator = Validator::make([
            'name' => '',
            'email' => 'bukan-email',
            'password' => '123',
        ], $rules, $messages);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // 2. Uji kegagalan jika password_confirmation tidak cocok
        $mismatchValidator = Validator::make([
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ], $rules, $messages);

        $this->assertTrue($mismatchValidator->fails());
        $this->assertArrayHasKey('password', $mismatchValidator->errors()->toArray());
        $this->assertEquals('Konfirmasi kata sandi tidak cocok.', $mismatchValidator->errors()->first('password'));

        $domainValidator = Validator::make([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $rules, $messages);

        $this->assertTrue($domainValidator->fails());
        $this->assertArrayHasKey('email', $domainValidator->errors()->toArray());

        // 3. Uji keberhasilan ketika password_confirmation cocok
        $validValidator = Validator::make([
            'name' => 'Budi Santoso',
            'email' => 'budi@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '08123456789',
        ], $rules);

        $this->assertFalse($validValidator->fails());
    }

    /**
     * Memverifikasi aturan validasi LoginRequest.
     */
    public function test_login_request_validation_rules(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'email' => '',
            'password' => '',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Memverifikasi aturan validasi StoreLostItemRequest.
     */
    public function test_store_lost_item_request_validation_rules(): void
    {
        $request = new StoreLostItemRequest();
        $rules = $request->rules();

        // Uji nilai wajib kosong
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('item_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
        $this->assertArrayHasKey('location', $validator->errors()->toArray());
        $this->assertArrayHasKey('current_storage', $validator->errors()->toArray());
        $this->assertArrayHasKey('contact_info', $validator->errors()->toArray());
        $this->assertArrayHasKey('chronology', $validator->errors()->toArray());

        // Uji kategori tidak valid
        $invalidCatValidator = Validator::make([
            'item_name' => 'Tas Hitam',
            'category' => 'invalid_category',
            'location' => 'Kantin',
            'current_storage' => 'Pos Satpam',
            'contact_info' => '08123',
            'chronology' => 'Tertinggal kemarin',
        ], $rules);
        $this->assertTrue($invalidCatValidator->fails());
        $this->assertArrayHasKey('category', $invalidCatValidator->errors()->toArray());
    }

    /**
     * Memverifikasi aturan validasi StoreItemReportRequest (pesan minimal 10 karakter).
     */
    public function test_store_item_report_request_validation_rules(): void
    {
        $request = new StoreItemReportRequest();
        $rules = $request->rules();

        $validatorShort = Validator::make([
            'message' => 'pendek',
        ], $rules);

        $this->assertTrue($validatorShort->fails());
        $this->assertArrayHasKey('message', $validatorShort->errors()->toArray());

        $validatorValid = Validator::make([
            'message' => 'Barang terlihat di pos satpam lantai 1.',
        ], $rules);

        $this->assertFalse($validatorValid->fails());
    }

    /**
     * Memverifikasi pendaftaran RateLimiter auth dan reports.
     */
    public function test_custom_rate_limiters_are_registered(): void
    {
        $request = Request::create('/api/login', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $authLimiter = RateLimiter::limiter('auth');
        $this->assertNotNull($authLimiter, 'RateLimiter auth wajib terdaftar.');

        $reportsLimiter = RateLimiter::limiter('reports');
        $this->assertNotNull($reportsLimiter, 'RateLimiter reports wajib terdaftar.');
    }
}
