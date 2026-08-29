<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotification implements ShouldQueue
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
        public Product $product,
        public int $currentStock,
        public int $threshold,
        public ?int $warehouseId = null
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get all managers and admins who should be notified
            $recipients = User::whereIn('role', ['admin', 'manager'])
                ->where('status', 'active')
                ->get();

            if ($recipients->isEmpty()) {
                Log::warning("No recipients found for low stock notification", [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                ]);
                return;
            }

            // Load product relationships
            if (!$this->product->relationLoaded('brand')) {
                $this->product->load(['brand', 'category', 'baseUnit']);
            }

            foreach ($recipients as $recipient) {
                Mail::send('emails.low-stock-alert', [
                    'product' => $this->product,
                    'currentStock' => $this->currentStock,
                    'threshold' => $this->threshold,
                    'warehouseId' => $this->warehouseId,
                    'recipientName' => $recipient->name,
                ], function ($message) use ($recipient) {
                    $message->to($recipient->email, $recipient->name)
                        ->subject("Low Stock Alert: {$this->product->name}");
                });
            }

            Log::info("Low stock notification sent", [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'sku' => $this->product->sku,
                'current_stock' => $this->currentStock,
                'threshold' => $this->threshold,
                'recipients_count' => $recipients->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send low stock notification", [
                'product_id' => $this->product->id,
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
        Log::error("Low stock notification job failed permanently", [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'error' => $exception->getMessage(),
        ]);
    }
}
