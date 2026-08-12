<?php

namespace App\Services\Whatsapp;

use App\Models\Order;

class WhatsappMessageTemplateService
{
    public function orderStatusChanged(
        Order $order,
        string $status
    ): ?string {
        return match ($status) {
            'paid' => $this->paid($order),

            'processing' => $this->processing($order),

            'picked_up' => $this->pickedUp($order),

            'shipped' => $this->shipped($order),

            'completed' => $this->completed($order),

            'cancelled' => $this->cancelled($order),

            default => null,
        };
    }

    protected function paid(Order $order): string
    {
        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pembayaran untuk pesanan *{$order->order_number}* telah berhasil kami terima.",
            "",
            "Terima kasih telah melakukan pembayaran.",
            "",
            "Melody Furniture",
        ]);
    }

    protected function processing(Order $order): string
    {
        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pesanan *{$order->order_number}* sedang kami proses.",
            "",
            "Kami akan memberikan informasi berikutnya setelah pesanan masuk ke tahap pengiriman.",
            "",
            "Melody Furniture",
        ]);
    }

    protected function pickedUp(Order $order): string
    {
        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pesanan *{$order->order_number}* telah diambil oleh pihak pengiriman.",
            "",
            "Pesanan Anda sedang dalam proses menuju tahap pengiriman.",
            "",
            "Melody Furniture",
        ]);
    }

    protected function shipped(Order $order): string
    {
        $trackingNumber = $order->tracking_number;

        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pesanan *{$order->order_number}* telah dikirim.",
            "",
            $trackingNumber
                ? "Nomor resi: *{$trackingNumber}*"
                : null,
            "",
            "Silakan gunakan nomor resi untuk melacak pengiriman Anda.",
            "",
            "Melody Furniture",
        ]);
    }

    protected function completed(Order $order): string
    {
        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pesanan *{$order->order_number}* telah selesai.",
            "",
            "Terima kasih telah berbelanja di Melody Furniture.",
            "",
            "Semoga produk kami memberikan kepuasan untuk Anda.",
        ]);
    }

    protected function cancelled(Order $order): string
    {
        return implode("\n", [
            "Halo {$order->customer_name},",
            "",
            "Pesanan *{$order->order_number}* dengan nomor *{$order->order_number}* telah dibatalkan.",
            "",
            "Jika pembatalan ini tidak sesuai dengan permintaan Anda, silakan hubungi admin Melody Furniture.",
            "",
            "Melody Furniture",
        ]);
    }
}