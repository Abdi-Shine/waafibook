<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $userName;
    public $ttlMinutes;

    public function __construct($otpCode, $userName, $ttlMinutes)
    {
        $this->otpCode    = $otpCode;
        $this->userName   = $userName;
        $this->ttlMinutes = $ttlMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your WaafiBook verification code: ' . $this->otpCode,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration_otp',
        );
    }
}
