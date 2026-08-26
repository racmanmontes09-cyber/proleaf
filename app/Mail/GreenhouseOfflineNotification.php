<?php

namespace App\Mail;

use App\Models\Device;
use App\Models\Greenhouse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GreenhouseOfflineNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Greenhouse $greenhouse,
        public Device $device,
        public ?string $farmerName = null,
        public ?string $lastSeen = null
    ) {
        $this->farmerName ??= $greenhouse->farmer?->name ?? 'Unassigned';
        $this->lastSeen ??= $device->last_seen_at ? $device->last_seen_at->toDateTimeString() : 'Never';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[CRITICAL ALERT] Greenhouse '{$this->greenhouse->name}' is OFFLINE",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.greenhouse-offline',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
