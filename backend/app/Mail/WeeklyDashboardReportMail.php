<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDashboardReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $kpis  Pre-built KPI payload from the command.
     */
    public function __construct(
        public readonly array $kpis,
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
    ) {}

    public function envelope(): Envelope
    {
        $start = $this->periodStart->format('d/m/Y');
        $end = $this->periodEnd->format('d/m/Y');

        return new Envelope(
            subject: "Reporte semanal del dashboard — {$start} al {$end}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-dashboard-report',
            with: [
                'kpis' => $this->kpis,
                'periodStart' => $this->periodStart,
                'periodEnd' => $this->periodEnd,
                'dashboardUrl' => url('/dashboard'),
            ],
        );
    }
}
