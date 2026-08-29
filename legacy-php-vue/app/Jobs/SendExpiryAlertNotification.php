<?php

namespace App\Jobs;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiryAlertNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $batches,
        public int $daysThreshold = 30
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->batches->isEmpty()) {
                return;
            }

            // Get all warehouse managers and admins
            $recipients = User::whereIn('role', ['admin', 'manager', 'warehouse'])
                ->where('status', 'active')
                ->get();

            if ($recipients->isEmpty()) {
                Log::warning("No recipients found for expiry alert notification");
                return;
            }

            // Ensure batches have product relationships loaded
            $this->batches->load(['product', 'warehouse']);

            // Group batches by urgency
            $urgentBatches = $this->batches->filter(function ($batch) {
                return $batch->expiry_date <= now()->addDays(7);
            });

            $warningBatches = $this->batches->filter(function ($batch) {
                return $batch->expiry_date > now()->addDays(7) && $batch->expiry_date <= now()->addDays(30);
            });

            foreach ($recipients as $recipient) {
                Mail::send('emails.expiry-alert', [
                    'urgentBatches' => $urgentBatches,
                    'warningBatches' => $warningBatches,
                    'daysThreshold' => $this->daysThreshold,
                    'recipientName' => $recipient->name,
                ], function ($message) use ($recipient, $urgentBatches) {
                    $urgentCount = $urgentBatches->count();
                    $subject = $urgentCount > 0
                        ? "URGENT: {$urgentCount} Batch(es) Expiring Within 7 Days"
                        : "Product Expiry Alert: Action Required";
                    
                    $message->to($recipient->email, $recipient->name)
                        ->subject($subject);
                });
            }

            Log::info("Expiry alert notification sent", [
                'total_batches' => $this->batches->count(),
                'urgent_batches' => $urgentBatches->count(),
                'warning_batches' => $warningBatches->count(),
                'recipients_count' => $recipients->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send expiry alert notification", [
                'batches_count' => $this->batches->count(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Expiry alert notification job failed permanently", [
            'batches_count' => $this->batches->count(),
            'error' => $exception->getMessage(),
        ]);
    }
}
