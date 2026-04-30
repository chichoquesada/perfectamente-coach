<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $nutritionist,
        public string $token,
        public ?string $patientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->nutritionist->name.' le invitó a PerfectaMENTE Coach',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.patient-invitation',
            with: [
                'nutritionist' => $this->nutritionist,
                'acceptUrl' => route('invitation.show', $this->token),
                'patientName' => $this->patientName,
            ],
        );
    }
}
