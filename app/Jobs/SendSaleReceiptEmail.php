<?php

namespace App\Jobs;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSaleReceiptEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Sale $sale,
        public string $recipientEmail,
        public ?string $recipientName = null
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load relationships if not already loaded
            if (!$this->sale->relationLoaded('items')) {
                $this->sale->load(['items.product', 'items.unit', 'customer', 'warehouse', 'user']);
            }

            // Send receipt email
            Mail::send('emails.sale-receipt', [
                'sale' => $this->sale,
                'recipientName' => $this->recipientName ?? $this->sale->customer?->name ?? 'Valued Customer',
            ], function ($message) {
                $message->to($this->recipientEmail, $this->recipientName)
                    ->subject("Receipt for Invoice #{$this->sale->invoice_number}");
            });

            Log::info("Sale receipt email sent successfully", [
                'sale_id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
                'recipient' => $this->recipientEmail,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send sale receipt email", [
                'sale_id' => $this->sale->id,
                'invoice_number' => $this->sale->invoice_number,
                'recipient' => $this->recipientEmail,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sale receipt email job failed permanently", [
            'sale_id' => $this->sale->id,
            'invoice_number' => $this->sale->invoice_number,
            'recipient' => $this->recipientEmail,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admins about the failure
        // You could dispatch another notification job here
    }
}
