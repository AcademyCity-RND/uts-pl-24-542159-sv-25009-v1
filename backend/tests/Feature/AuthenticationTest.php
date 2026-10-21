<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memverifikasi pendaftaran akun baru berhasil dan menerbitkan Sanctum Token.
     */
    public function test_user_can_register_successfully_and_gets_sanctum_token(): void
    {
        $payload = [
            'name' => 'Hilarius Avin',
            'email' => 'avin@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '081234567890',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'phone_number',
                             'created_at',
                             'updated_at',
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'avin@gmail.com',
            'role' => 'user',
        ]);
    }

    /**
     * Memverifikasi role pendaftar baru tetap di-hardcode 'user' meskipun payload mencoba menyisipkan 'admin'.
     */
    public function test_user_cannot_register_with_admin_role_via_payload(): void
    {
        $payload = [
            'name' => 'Peretas Akun',
            'email' => 'hacker@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin', // Percobaan eskalasi hak akses
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);
        $this->assertEquals('user', $response->json('data.user.role'));

        $this->assertDatabaseHas('users', [
            'email' => 'hacker@gmail.com',
            'role' => 'user',
        ]);
    }

    /**
     * Memverifikasi registrasi gagal dengan 422 saat konfirmasi password tidak cocok.
     */
    public function test_user_cannot_register_with_mismatched_password_confirmation(): void
    {
        $payload = [
            'name' => 'Hilarius Avin',
            'email' => 'mismatch@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'berbeda456',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Memverifikasi login berhasil dengan kredensial valid dan mengembalikan HTTP 200 + token.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@jejakbarang.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'phone_number' => '081298765432',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mahasiswa@jejakbarang.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'data' => [
                         'token',
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'phone_number',
                         ],
                     ],
                 ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Memverifikasi login gagal dengan HTTP 401 saat kata sandi salah.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@jejakbarang.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'mahasiswa@jejakbarang.test',
            'password' => 'salah_password',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
                 ]);
    }

    /**
     * Memverifikasi pengguna terautentikasi dapat logout dan token dicabut dari sistem.
     */
    public function test_authenticated_user_can_logout_and_revokes_token(): void
    {
        $user = User::create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@jejakbarang.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logout berhasil. Token telah dicabut.',
                 ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Memverifikasi permintaan logout tanpa token ditolak dengan HTTP 401 Unauthorized.
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}
