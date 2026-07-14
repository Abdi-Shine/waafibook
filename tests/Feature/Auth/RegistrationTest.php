<?php

use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('submitting the registration form does not create an account yet and sends an otp', function () {
    Mail::fake();

    $response = $this->post('/register', [
        'company_name' => 'Test Company',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    $response->assertRedirect(route('register.verify'));

    Mail::assertSent(\App\Mail\RegistrationOtpMail::class);
});

test('the account is only created after the correct otp is submitted', function () {
    Mail::fake();

    $this->post('/register', [
        'company_name' => 'Test Company',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $otp = session('registration_pending')['otp_code'];

    $response = $this->post(route('register.verify.store'), ['otp' => $otp]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    $response->assertRedirect(route('login'));
});

test('an incorrect otp does not create the account', function () {
    Mail::fake();

    $this->post('/register', [
        'company_name' => 'Test Company',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $response = $this->post(route('register.verify.store'), ['otp' => '000000']);

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    $response->assertSessionHasErrors('otp');
});
