<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Console\Command;

class CleanupInactiveGuestData extends Command
{
    protected $signature = 'cleanup:inactive-guest-data
                            {--delete : Hapus data yang memenuhi syarat}';

    protected $description = 'Membersihkan guest customer dan cart kosong yang tidak aktif selama 3 bulan';

    public function handle(): int
    {
        $cutoff = now()->subMonths(3);

        $inactiveGuests = Customer::query()
            ->whereNotNull('guest_token')
            ->whereNotNull('last_guest_activity_at')
            ->where('last_guest_activity_at', '<', $cutoff)
            ->whereDoesntHave('orders')
            ->get();

        $emptyCarts = Cart::query()
            ->whereDoesntHave('items')
            ->where('updated_at', '<', $cutoff)
            ->get();

        $this->info('=== Cleanup Inactive Guest Data ===');
        $this->newLine();

        $this->line(
            'Batas waktu: ' . $cutoff->format('Y-m-d H:i:s')
        );

        $this->newLine();

        $this->info(
            'Guest customer yang memenuhi syarat: ' .
            $inactiveGuests->count()
        );

        foreach ($inactiveGuests as $customer) {
            $this->line(
                sprintf(
                    '- Customer %s | Last Activity: %s',
                    $customer->id,
                    $customer->last_guest_activity_at->format('Y-m-d H:i:s')
                )
            );
        }

        $this->newLine();

        $this->info(
            'Cart kosong yang memenuhi syarat: ' .
            $emptyCarts->count()
        );

        foreach ($emptyCarts as $cart) {
            $this->line(
                sprintf(
                    '- Cart %s | Updated: %s',
                    $cart->id,
                    $cart->updated_at?->format('Y-m-d H:i:s')
                )
            );
        }

        $this->newLine();

        if (! $this->option('delete')) {
            $this->comment(
                'DRY RUN: tidak ada data yang dihapus.'
            );

            $this->line(
                'Gunakan --delete untuk menghapus data yang memenuhi syarat.'
            );

            return self::SUCCESS;
        }

        $deletedCustomers = 0;
        $deletedCarts = 0;

        foreach ($inactiveGuests as $customer) {
            $customer->delete();
            $deletedCustomers++;
        }

        foreach ($emptyCarts as $cart) {
            $cart->delete();
            $deletedCarts++;
        }

        $this->newLine();

        $this->info(
            "Customer guest dihapus: {$deletedCustomers}"
        );

        $this->info(
            "Cart kosong dihapus: {$deletedCarts}"
        );

        return self::SUCCESS;
    }
}