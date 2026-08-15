<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notificationText(
                'subject',
                'Secure laboratory notification'
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notification',
            text: 'mail.notification-text',
            with: [
                'title' => $this->notificationText(
                    'title',
                    'Laboratory notification'
                ),
                'body' => $this->notificationText(
                    'body',
                    'A secure update is available.'
                ),
                'actionLabel' => $this->nullableText(
                    'action_label'
                ),
                'actionUrl' => $this->nullableUrl(
                    'action_url'
                ),
                'footer' => $this->notificationText(
                    'footer',
                    'Do not reply with clinical or account information.'
                ),
            ],
        );
    }

    private function notificationText(
        string $key,
        string $fallback
    ): string {
        $value = trim((string) ($this->payload[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    }

    private function nullableText(string $key): ?string
    {
        $value = trim((string) ($this->payload[$key] ?? ''));

        return $value !== '' ? $value : null;
    }

    private function nullableUrl(string $key): ?string
    {
        $value = $this->nullableText($key);

        if ($value === null) {
            return null;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true)
            ? $value
            : null;
    }
}
