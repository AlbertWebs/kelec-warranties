<?php

namespace Tests\Feature\Auth;

use App\Mail\AdminLoginOtpMail;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        app(SettingsService::class)->set('sms_enabled', false, 'sms', 'boolean');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_are_challenged_with_sms_otp_after_password_login(): void
    {
        $user = User::factory()->create([
            'mobile_number' => '0723014032',
            'mobile_normalized' => '254723014032',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login.otp.show'));
        $this->assertNotNull(session('login_otp.user_id'));
        $this->assertDatabaseHas('sms_logs', [
            'mobile' => '254723014032',
            'context' => 'admin_login_otp',
            'status' => 'mock',
        ]);
        Mail::assertSent(AdminLoginOtpMail::class, function (AdminLoginOtpMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_users_can_complete_login_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'mobile_number' => '0723014032',
            'mobile_normalized' => '254723014032',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login.otp.show'));

        $code = $this->latestOtpCode();

        $this->post('/login/otp', ['otp' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_requires_mobile_number_for_otp(): void
    {
        $user = User::factory()->withoutMobile()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    protected function latestOtpCode(): string
    {
        $message = SmsLog::query()->latest('id')->value('message') ?? '';
        preg_match('/code is (\d{6})/', $message, $matches);

        $this->assertNotEmpty($matches[1] ?? null, 'OTP code not found in SMS log');

        return $matches[1];
    }
}
