<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FundTransferMail extends Mailable
{
    use Queueable, SerializesModels;

    public $amount;
    public $name;
    public $recipientEmail;

    /**
     * Create a new message instance.
     */
    public function __construct($amount, $name, $recipientEmail)
    {
        $this->amount = $amount;
        $this->name = $name;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Get the message envelope.
     */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nutrylyfe - Fund Transfer Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.fund_transfer',
            // data: [
            //     'amount' => $this->amount,
            //     'name' => $this->name,
            //     'recipientEmail' => $this->recipientEmail,
            // ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
