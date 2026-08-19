<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockProductNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
    ) {}

    /**
     * Notification channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database notification payload.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',

            'title' => 'Stok produk menipis',

            'message' => sprintf(
                'Stok produk "%s" tersisa %d unit.',
                $this->product->name,
                $this->product->ready_stock
            ),

            'product_id' => $this->product->id,

            'product_name' => $this->product->name,

            'sku' => $this->product->sku,

            'ready_stock' => (int) $this->product->ready_stock,

            'threshold' => 3,

            'url' => route(
                'admin.products.edit',
                $this->product
            ),
        ];
    }
}