<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly Customer $customer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Bestellbestätigung #'.$this->order->id.' — dormed24.de',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }
}
