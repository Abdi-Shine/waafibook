<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $companyName;
    public $userName;
    public $daysLeft;
    public $expiryDate;
    public $isTrial;
    public $isExpired;

    /**
     * @param  string  $companyName
     * @param  string  $userName
     * @param  int  $daysLeft  Positive = days remaining. Negative/zero-or-less is treated as already expired.
     * @param  \Illuminate\Support\Carbon  $expiryDate
     * @param  bool  $isTrial
     */
    public function __construct($companyName, $userName, $daysLeft, $expiryDate, $isTrial)
    {
        $this->companyName = $companyName;
        $this->userName    = $userName;
        $this->daysLeft    = $daysLeft;
        $this->expiryDate  = $expiryDate;
        $this->isTrial     = $isTrial;
        $this->isExpired   = $daysLeft < 0;
    }

    public function envelope(): Envelope
    {
        $plan = $this->isTrial ? 'trial' : 'subscription';

        $subject = $this->isExpired
            ? "Your WaafiBook {$plan} has expired"
            : ($this->daysLeft === 1
                ? "1 day left in your WaafiBook {$plan}"
                : "{$this->daysLeft} days left in your WaafiBook {$plan}");

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription_expiry_reminder',
        );
    }
}
