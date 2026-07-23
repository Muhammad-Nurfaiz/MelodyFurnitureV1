<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payment\PaymentExpirationService;
use Throwable;

class ExpirePendingPaymentsCommand extends Command
{
    /**
     * Nama command.
     */
    protected $signature = 'payments:expire';

    /**
     * Deskripsi command.
     */
    protected $description =
        'Expire pending payments that passed payment deadline';

    public function __construct(
        protected PaymentExpirationService $paymentExpirationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {

            $processed =
                $this->paymentExpirationService
                    ->processExpiredPayments();

            $this->info(
                "{$processed} payment(s) processed."
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            report($e);

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;

        }
    }
}