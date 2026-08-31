berikut file yang berhubungan dengan shipment :
- migration shipment
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('courier');

            $table->string('service');

            $table->string('booking_code')
                ->nullable();

            $table->string('tracking_number')
                ->nullable();

            $table->string('label_url')
                ->nullable();

            $table->enum(
                'status',
                [
                    'waiting_pickup',
                    'ready_to_print',
                    'picked_up',
                    'in_transit',
                    'delivered',
                    'cancelled',
                ]
            )->default('waiting_pickup');

            $table->json('metadata')
                ->nullable();

            $table->timestamp('picked_up_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamps();

            $table->index('tracking_number');
            $table->index('booking_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Current Database State
        |--------------------------------------------------------------------------
        |
        | Migration sebelumnya sudah berhasil melakukan:
        |
        | shipments -> shipments_old
        |
        | sehingga:
        |
        | shipments_old = 7 existing records
        | shipments     = empty table
        |
        | Jangan melakukan rename lagi.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Ensure Old Data Exists
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('shipments_old')) {
            throw new RuntimeException(
                'Tabel shipments_old tidak ditemukan. '
                . 'Migration recovery tidak dapat dilanjutkan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Correct Shipments Table
        |--------------------------------------------------------------------------
        |
        | orders.id = UUID / VARCHAR
        |
        | Oleh karena itu:
        |
        | shipments.order_id = UUID / VARCHAR
        |
        | UNIQUE memastikan satu order hanya memiliki satu shipment.
        |
        */

        if (! Schema::hasTable('shipments')) {

            Schema::create('shipments', function (Blueprint $table) {

                $table->id();

                $table->foreignUuid('order_id')
                    ->unique()
                    ->constrained('orders')
                    ->cascadeOnDelete();

                $table->string('courier');

                $table->string('service');

                $table->string('booking_code')
                    ->nullable();

                $table->string('tracking_number')
                    ->nullable();

                $table->string('label_url')
                    ->nullable();

                $table->enum(
                    'status',
                    [
                        'waiting_pickup',
                        'ready_to_print',
                        'picked_up',
                        'in_transit',
                        'delivered',
                        'cancelled',
                    ]
                )->default('waiting_pickup');

                $table->json('metadata')
                    ->nullable();

                $table->timestamp('picked_up_at')
                    ->nullable();

                $table->timestamp('delivered_at')
                    ->nullable();

                $table->timestamp('last_tracking_sync_at')
                    ->nullable();

                $table->timestamps();

                $table->index('tracking_number');

                $table->index('booking_code');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Copy Existing Shipment Data
        |--------------------------------------------------------------------------
        */

        DB::statement('
            INSERT INTO shipments (
                id,
                order_id,
                courier,
                service,
                booking_code,
                tracking_number,
                label_url,
                status,
                metadata,
                picked_up_at,
                delivered_at,
                last_tracking_sync_at,
                created_at,
                updated_at
            )
            SELECT
                id,
                order_id,
                courier,
                service,
                booking_code,
                tracking_number,
                label_url,
                status,
                metadata,
                picked_up_at,
                delivered_at,
                last_tracking_sync_at,
                created_at,
                updated_at
            FROM shipments_old
        ');

        /*
        |--------------------------------------------------------------------------
        | Remove Temporary Table
        |--------------------------------------------------------------------------
        */

        Schema::drop('shipments_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Safety
        |--------------------------------------------------------------------------
        |
        | Karena migration ini melakukan recovery terhadap data existing,
        | rollback tidak mencoba mengembalikan order_id menjadi INTEGER.
        |
        | Jika migration perlu dibatalkan, lebih aman menghentikan proses
        | daripada mengubah kembali foreign key UUID menjadi INTEGER.
        |
        */

        throw new RuntimeException(
            'Migration fix_shipments_order_id_type tidak mendukung rollback otomatis.'
        );
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {

            $table->unique(
                'order_id',
                'shipments_order_id_unique'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {

            $table->dropUnique(
                'shipments_order_id_unique'
            );

        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {

            $table->timestamp('last_tracking_sync_at')
                ->nullable()
                ->after('delivered_at');

        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {

            $table->dropColumn(
                'last_tracking_sync_at'
            );

        });
    }
};
- Models Shipment
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [

        'order_id',

        'courier',

        'service',

        'booking_code',

        'tracking_number',

        'label_url',

        'status',

        'metadata',

        'picked_up_at',

        'delivered_at',

    ];

    protected $casts = [
        'metadata' => 'array',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_tracking_sync_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function isWaitingPickup(): bool
    {
        return $this->status === 'waiting_pickup';
    }

    public function isReadyToPrint(): bool
    {
        return $this->status === 'ready_to_print';
    }

    public function isPickedUp(): bool
    {
        return $this->status === 'picked_up';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
- Service Shipment
<?php

namespace App\Services\Shipping;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Order\OrderWorkflowService;
use App\Services\Shipping\Courier\CourierShipmentResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShipmentService
{
    public function __construct(
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Shipment
    |--------------------------------------------------------------------------
    */

    public function create(
        Order $order,
        CourierShipmentResult $result
    ): Shipment {

        return DB::transaction(function () use (
            $order,
            $result
        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Shipment
            |--------------------------------------------------------------------------
            */

            if ($order->hasShipment()) {
                throw new RuntimeException(
                    'Order sudah memiliki shipment.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Order
            |--------------------------------------------------------------------------
            */

            if (! $order->isProcessing()) {
                throw new RuntimeException(
                    'Shipment hanya dapat dibuat ketika order sedang diproses.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Courier Result
            |--------------------------------------------------------------------------
            */

            if (! $result->success) {
                throw new RuntimeException(
                    $result->message
                    ?? 'Gagal membuat shipment pada ekspedisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Local Shipment
            |--------------------------------------------------------------------------
            */

            $shipment = Shipment::create([

                'order_id' => $order->id,

                'courier' => $order->courier,

                'service' => $order->shipping_method,

                'booking_code' => $result->bookingCode,

                'tracking_number' => $result->trackingNumber,

                'label_url' => $result->labelUrl,

                'status' =>
                    $result->status
                    ?? 'waiting_pickup',

                'metadata' => $result->metadata,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Sync Tracking Number To Order
            |--------------------------------------------------------------------------
            */

            if (filled($result->trackingNumber)) {

                $order->update([
                    'tracking_number'
                        => $result->trackingNumber,
                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Shipment
            |--------------------------------------------------------------------------
            */

            return $this->refreshShipment(
                $shipment
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Shipment
    |--------------------------------------------------------------------------
    */

    private function updateShipment(
        Shipment $shipment,
        array $attributes
    ): Shipment {

        $shipment->update(
            $attributes
        );

        return $this->refreshShipment(
            $shipment
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Set Tracking Number
    |--------------------------------------------------------------------------
    */

    public function setTrackingNumber(
        Shipment $shipment,
        string $trackingNumber
    ): Shipment {

        return DB::transaction(
            function () use (
                $shipment,
                $trackingNumber
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'tracking_number'
                            => $trackingNumber,
                    ]
                );

                $shipment->order()->update([
                    'tracking_number'
                        => $trackingNumber,
                ]);

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Picked Up
    |--------------------------------------------------------------------------
    */

    public function markPickedUp(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validatePickup(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'picked_up',

                        'picked_up_at'
                            => now(),
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'picked_up',
                    'Barang telah diambil kurir.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark In Transit
    |--------------------------------------------------------------------------
    */

    public function markInTransit(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateTransit(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'in_transit',
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'shipped',
                    'Barang sedang dikirim.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Delivered
    |--------------------------------------------------------------------------
    */

    public function markDelivered(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateDelivered(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'delivered',

                        'delivered_at'
                            => now(),
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'completed',
                    'Pesanan telah diterima customer.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Shipment
    |--------------------------------------------------------------------------
    */

    public function cancel(
        Shipment $shipment,
        Admin $admin
    ): Shipment {

        $this->validateCancellation(
            $shipment
        );

        return DB::transaction(
            function () use (
                $shipment,
                $admin
            ) {

                $shipment = $this->updateShipment(
                    $shipment,
                    [
                        'status'
                            => 'cancelled',
                    ]
                );

                $this->workflowService->changeStatus(
                    $shipment->order,
                    'processing',
                    'Pengiriman dibatalkan.',
                    (string) $admin->id
                );

                return $this->refreshShipment(
                    $shipment
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Tracking
    |--------------------------------------------------------------------------
    */

    public function findByTracking(
        string $trackingNumber
    ): ?Shipment {

        return Shipment::with([
            'order',
            'order.payment',
        ])
            ->where(
                'tracking_number',
                $trackingNumber
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Booking Code
    |--------------------------------------------------------------------------
    */

    public function findByBookingCode(
        string $bookingCode
    ): ?Shipment {

        return Shipment::where(
            'booking_code',
            $bookingCode
        )->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validatePickup(
        Shipment $shipment
    ): void {

        if (! $shipment->isWaitingPickup()) {

            throw new RuntimeException(
                'Shipment tidak dapat dipick up.'
            );
        }
    }

    private function validateTransit(
        Shipment $shipment
    ): void {

        if (! $shipment->isPickedUp()) {

            throw new RuntimeException(
                'Shipment belum diambil kurir.'
            );
        }
    }

    private function validateDelivered(
        Shipment $shipment
    ): void {

        if (! $shipment->isInTransit()) {

            throw new RuntimeException(
                'Shipment belum dalam perjalanan.'
            );
        }
    }

    private function validateCancellation(
        Shipment $shipment
    ): void {

        if ($shipment->isDelivered()) {

            throw new RuntimeException(
                'Shipment yang sudah delivered tidak dapat dibatalkan.'
            );
        }

        if ($shipment->isCancelled()) {

            throw new RuntimeException(
                'Shipment sudah dibatalkan.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Shipment
    |--------------------------------------------------------------------------
    */

    private function refreshShipment(
        Shipment $shipment
    ): Shipment {

        return $shipment->fresh([
            'order',
            'order.payment',
        ]);
    }
}
<?php

namespace App\Services\Shipping\Courier;

class CourierShipmentResult
{
    public function __construct(
        public bool $success,
        public ?string $bookingCode = null,
        public ?string $trackingNumber = null,
        public ?string $labelUrl = null,
        public ?string $status = null,
        public array $metadata = [],
        public ?string $message = null,
    ) {}

    public static function success(
        ?string $bookingCode = null,
        ?string $trackingNumber = null,
        ?string $labelUrl = null,
        ?string $status = null,
        array $metadata = [],
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            bookingCode: $bookingCode,
            trackingNumber: $trackingNumber,
            labelUrl: $labelUrl,
            status: $status,
            metadata: $metadata,
            message: $message,
        );
    }

    public static function failed(
        string $message,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            metadata: $metadata,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'booking_code' => $this->bookingCode,
            'tracking_number' => $this->trackingNumber,
            'label_url' => $this->labelUrl,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'message' => $this->message,
        ];
    }
}
- Controller Shipment
<?php

namespace App\Http\Controllers\Admin\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderFulfillmentService;
use App\Services\Shipping\DeliveryService;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected OrderFulfillmentService $fulfillmentService
    ) {}

    public function store(
        Order $order
    ): JsonResponse {

        $order = $this->fulfillmentService->start(
            $order,
            auth()->id()
                ? (string) auth()->id()
                : 'admin'
        );

        return response()->json([
            'message' => 'Shipment berhasil dibuat.',
            'data' => $order,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Pickup
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as picked up by courier.
     */
    public function pickup(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->pickup(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment berhasil dipickup.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transit
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as in transit.
     */
    public function transit(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->transit(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment sedang dikirim.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delivered
    |--------------------------------------------------------------------------
    */

    /**
     * Mark order shipment as delivered.
     */
    public function delivered(
        Order $order
    ): JsonResponse {

        $order = $this->deliveryService->delivered(
            $order,
            (string) auth()->id()
        );

        return response()->json([
            'message' => 'Shipment berhasil diterima pelanggan.',
            'data' => $order,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel
    |--------------------------------------------------------------------------
    */

    /**
     * Cancel shipment.
     *
     * Cancellation shipment belum diaktifkan karena
     * aturan pembatalan dari perusahaan belum dikonfirmasi.
     */
    public function cancel(
        Order $order
    ): JsonResponse {

        abort_unless(
            $order->shipment !== null,
            404,
            'Shipment tidak ditemukan.'
        );

        return response()->json([
            'message' =>
                'Pembatalan shipment belum tersedia karena aturan pembatalan dari perusahaan belum dikonfirmasi.',
        ], 409);
    }
}

berikut file order yang mengaitkan order dan shipment :
- service order
<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Shipping\CourierService;
use App\Services\Shipping\ShipmentService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderFulfillmentService
{
    public function __construct(
        protected CourierService $courierService,
        protected ShipmentService $shipmentService,
        protected OrderWorkflowService $workflowService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Start Fulfillment
    |--------------------------------------------------------------------------
    */

    /**
     * Memulai proses fulfillment order.
     *
     * Flow:
     *
     * Order:
     * paid
     *   ↓
     * processing
     *
     * Courier:
     * create shipment
     *
     * Local:
     * create shipment
     *
     * Proteksi:
     * - order dikunci menggunakan lockForUpdate()
     * - shipment dicek ulang setelah lock
     * - status dicek ulang setelah lock
     * - satu order hanya boleh memiliki satu shipment
     */
    public function start(
        Order $order,
        ?string $createdBy = 'admin'
    ): Order {

        return DB::transaction(function () use (
            $order,
            $createdBy
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock Order
            |--------------------------------------------------------------------------
            |
            | Mencegah dua request fulfillment berjalan bersamaan
            | untuk order yang sama.
            |
            */

            $order = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Shipment
            |--------------------------------------------------------------------------
            |
            | Setelah mendapatkan lock, lakukan pengecekan ulang.
            |
            | Ini penting karena request kedua mungkin sudah masuk
            | ketika request pertama masih berjalan.
            |
            */

            if ($order->hasShipment()) {
                throw new RuntimeException(
                    'Order sudah memiliki shipment.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Order Transition
            |--------------------------------------------------------------------------
            */

            $this->workflowService->validate(
                $order,
                'processing'
            );

            /*
            |--------------------------------------------------------------------------
            | Create Shipment At Courier
            |--------------------------------------------------------------------------
            |
            | Order masih berada pada status paid.
            |
            | Jika courier gagal, transaction akan rollback
            | dan order tetap paid.
            |
            */

            $shipmentResult = $this->courierService
                ->createShipment($order);

            /*
            |--------------------------------------------------------------------------
            | Handle Courier Failure
            |--------------------------------------------------------------------------
            */

            if (! $shipmentResult->success) {

                throw new RuntimeException(
                    $shipmentResult->message
                    ?? 'Gagal membuat shipment pada ekspedisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Change Order Status
            |--------------------------------------------------------------------------
            */

            $this->workflowService->changeStatus(
                $order,
                'processing',
                'Pesanan mulai diproses.',
                $createdBy
            );

            /*
            |--------------------------------------------------------------------------
            | Create Local Shipment
            |--------------------------------------------------------------------------
            */

            $this->shipmentService->create(
                $order->fresh(),
                $shipmentResult
            );

            /*
            |--------------------------------------------------------------------------
            | Return Fresh Order
            |--------------------------------------------------------------------------
            */

            return $order->fresh([
                'shipment',
                'payment',
            ]);
        });
    }
}
<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(
        protected OrderQueryService $queries,
        protected OrderWorkflowService $workflow,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Order List
    |--------------------------------------------------------------------------
    */
    public function list(Request $request): LengthAwarePaginator {
        return $this->paginate($request);
    }

    public function paginate(Request $request,int $perPage = 15): LengthAwarePaginator {
        $query = $this->queries->query();
        $query = $this->queries->search($query,$request->input('search'));
        $query = $this->queries->filterStatus($query,$request->input('status'));
        $query = $this->queries->filterPaymentStatus($query,$request->input('payment_status'));
        $query = $this->queries->filterCourier($query,$request->input('courier'));
        $query = $this->queries->filterDate($query,$request->input('date'));
        $query = $this->queries->sort($query,$request->input('sort', 'created_at'),$request->input('direction', 'desc'));
        return $this->queries->paginate($query,$perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    public function detail(string $id): Order {
        return $this->queries->find($id)?? abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Change Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(Order $order,string $status,?string $description = null,?string $adminId = null): Order {
        return $this->workflow->changeStatus($order,$status,$description,$adminId);
    }

    /*
    |--------------------------------------------------------------------------
    | Available Actions
    |--------------------------------------------------------------------------
    */

    public function availableActions(Order $order): array {
        return $this->workflow->availableTransitions($order);
    }

    public function show(string $id): Order
    {
        $order = $this->queries->find($id);

        if (! $order) {
            abort(404);
        }

        $order->loadMissing(['cancellationRequest',]);

        $order->action = $this->action($order);

        return $order;
    }

    public function action(Order $order): array
    {
        return match ($order->status) {

            'pending' => [
                'type' => 'info',
                'button_variant' => null,
                'label' => 'Menunggu Pembayaran',
                'route' => null,
                'method' => null,
            ],

            'paid' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => 'Proses Pesanan',
                'route' => route(
                    'admin.orders.processing',
                    $order
                ),
                'method' => 'PATCH',
            ],

            'processing' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => $order->shipment
                    ? 'Pickup Kurir'
                    : 'Buat Pengiriman',
                'route' => $order->shipment
                    ? route(
                        'admin.shipments.pickup',
                        $order->shipment
                    )
                    : route(
                        'admin.shipments.store',
                        $order
                    ),
                'method' => $order->shipment
                    ? 'PATCH'
                    : 'POST',
            ],

            'picked_up' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => 'Tandai Dikirim',
                'route' => route(
                    'admin.shipments.transit',
                    $order->shipment
                ),
                'method' => 'PATCH',
            ],

            'shipped' => [
                'type' => 'success',
                'button_variant' => 'success',
                'label' => 'Selesaikan Pesanan',
                'route' => route(
                    'admin.shipments.delivered',
                    $order->shipment
                ),
                'method' => 'PATCH',
            ],

            'completed' => [
                'type' => 'success',
                'button_variant' => null,
                'label' => 'Pesanan Selesai',
                'route' => null,
                'method' => null,
            ],

            'cancelled' => [
                'type' => 'danger',
                'button_variant' => null,
                'label' => 'Pesanan Dibatalkan',
                'route' => null,
                'method' => null,
            ],

            'req_cancel' => [
                'type' => 'warning',
                'button_variant' => null,
                'label' => 'Permintaan Cancel',
                'route' => null,
                'method' => null,
            ],

            default => [
                'type' => 'gray',
                'button_variant' => null,
                'label' => '-',
                'route' => null,
                'method' => null,
            ],
        };
    }

    public function statistics(): array
    {
        return $this->queries->statistics();
    }

    public function stats(): array
    {
        return $this->queries->stats();
    }
}
<?php

namespace App\Services\Order;

use App\Events\OrderStatusChanged;
use App\Models\OrderStatusHistory;
use App\Models\Order;
use RuntimeException;

class OrderWorkflowService
{
    public function __construct(
        protected OrderTimelineService $timelineService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Initialize Workflow
    |--------------------------------------------------------------------------
    */

    public function initialize(
        Order $order,
        ?string $description = null,
    ): Order {

        $this->timelineService->record(
            $order,
            $order->status,
            $description,
            'system'
        );

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Transition
    |--------------------------------------------------------------------------
    */

    protected array $transitions = [

        'pending' => [
            'paid',
            'cancelled',
        ],

        'paid' => [
            'processing',
            'req_cancel',
            'cancelled',
        ],

        'processing' => [
            'picked_up',
            'req_cancel',
            'cancelled',
        ],

        'req_cancel' => [
            'paid',
            'processing',
            'cancelled',
        ],

        'picked_up' => [
            'shipped',
        ],

        'shipped' => [
            'completed',
        ],

        'completed' => [],

        'cancelled' => [],
    ];

    /*
    |--------------------------------------------------------------------------
    | Status Date Mapping
    |--------------------------------------------------------------------------
    */

    protected array $statusDates = [

        'paid' => 'paid_at',

        'picked_up' => 'picked_up_at',

        'shipped' => 'shipped_at',

        'completed' => 'completed_at',

        'cancelled' => 'cancelled_at',

    ];

    /*
    |--------------------------------------------------------------------------
    | Transition Checker
    |--------------------------------------------------------------------------
    */

    public function canTransition(Order $order,string $target): bool {
        return in_array($target, $this->transitions[$order->status] ?? [],true);
    }

    public function validate(Order $order,string $target): void {
        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        */
        if ($order->status === $target) {
            return;
        }
        if (! $this->canTransition($order, $target)) {
            throw new RuntimeException("Status {$order->status} tidak dapat berubah menjadi {$target}.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Change Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        Order $order,
        string $status,
        ?string $description = null,
        ?string $adminId = null,
    ): Order {

        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        */

        if ($order->status === $status) {
            return $order->fresh();
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Transition
        |--------------------------------------------------------------------------
        */

        $this->validate($order, $status);

        /*
        |--------------------------------------------------------------------------
        | Update Order
        |--------------------------------------------------------------------------
        */
        $previousStatus = $order->status;
        $data = [
            'status' => $status,
        ];

        if (isset($this->statusDates[$status])) {
            $data[$this->statusDates[$status]] = now();
        }

        $order->update($data);

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */

        $this->timelineService->record(
            order: $order,
            status: $status,
            description: $description,
            actor: $adminId ? 'admin' : 'system',
            adminId: $adminId,
        );

        /*
        |--------------------------------------------------------------------------
        | Dispatch Order Status Changed Event
        |--------------------------------------------------------------------------
        */

        event(new OrderStatusChanged(
            order: $order->fresh(),
            previousStatus: $previousStatus,
            newStatus: $status,
        ));

        return $order->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Available Transition
    |--------------------------------------------------------------------------
    */

    public function availableTransitions(
        Order $order
    ): array {

        return $this->transitions[$order->status] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Available Actions
    |--------------------------------------------------------------------------
    */

    public function customerActions(
        Order $order
    ): array {

        $canPay =
            $order->status !== 'cancelled'
            &&
            $order->payment_status === 'pending'
            &&
            filled($order->payment_expired_at)
            &&
            now()->lessThanOrEqualTo($order->payment_expired_at);

        return [

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'can_pay' => $canPay,

            /*
            |--------------------------------------------------------------------------
            | Cancellation
            |--------------------------------------------------------------------------
            */

            'can_request_cancel' =>
                in_array(
                    $order->status,
                    [
                        'paid',
                        'processing',
                    ],
                    true
                ) && is_null($order->cancellationRequest),

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'can_track_shipping' =>
                in_array(
                    $order->status,
                    [
                        'picked_up',
                        'shipped',
                        'completed',
                    ],
                    true
                ),

            /*
            |--------------------------------------------------------------------------
            | Invoice
            |--------------------------------------------------------------------------
            */

            'can_download_invoice' =>
                in_array(
                    $order->status,
                    [
                        'paid',
                        'processing',
                        'picked_up',
                        'shipped',
                        'completed',
                    ],
                    true
                ),
        ];
    }

    public function recordRefund(
        Order $order,
        ?string $description = null,
        ?string $adminId = null,
    ): OrderStatusHistory {

        return $this->timelineService->refund(
            order: $order,
            description: $description,
            actor: $adminId ? 'admin' : 'system',
            adminId: $adminId,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCompleted(Order $order): bool
    {
        return $order->status === 'completed';
    }

    public function isCancelled(Order $order): bool
    {
        return $order->status === 'cancelled';
    }

    public function isPendingPayment(Order $order): bool
    {
        return $order->payment_status === 'pending';
    }
}
- controller
<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderAdminService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderAdminService $adminService,
    ) {}

    public function index(Request $request)
    {
        $orders = $this->adminService->list($request);
        $stats = $this->adminService->stats();
        $statis = $this->adminService->statistics();

        return view(
            'admin.modules.order.index',
            compact('orders', 'stats', 'statis')
        );
    }

    public function show(string $id)
    {
        $order = $this->adminService->show($id);

        return view(
            'admin.modules.order.show',
            compact('order')
        );
    }

    public function processing(Order $order)
    {
        $order = $this->adminService->changeStatus(
            order: $order,
            status: 'processing',
            description: 'Pesanan mulai diproses'
        );

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diproses.',
            'data' => $order,
        ]);
    }
}
- blade action di show order
<x-admin.card>
<x-admin.card-header
    title="Action"
    description="Workflow dan tindakan untuk pesanan ini."
/>

<x-admin.card-body>

    @php
        $action = $order->action;
        $cancellationRequest = $order->cancellationRequest;
    @endphp

    <div class="flex flex-wrap flex-row justify-between items-center">
        <div class="flex flex-wrap flex-row items-center gap-3">
            {{-- =====================================================
                WORKFLOW ACTION
            ====================================================== --}}

            @if($action['route'])

                <x-admin.button
                    type="button"
                    :variant="$action['type']"
                    id="workflow-action"
                    :data-url="$action['route']"
                    :data-method="$action['method']"
                >
                    {{ $action['label'] }}
                </x-admin.button>

            @else

                <x-admin.badge
                    :color="$action['type']"
                >
                    {{ $action['label'] }}
                </x-admin.badge>

            @endif


            {{-- =====================================================
                CANCELLATION REQUEST ACTION
            ====================================================== --}}

            @if(
                $order->status === 'req_cancel'
                && $cancellationRequest
                && $cancellationRequest->status === 'pending'
            )

                {{-- Reject --}}

                <button
                    type="button"
                    id="reject-cancellation"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-50
                        focus:outline-none
                        focus:ring-2
                        focus:ring-gray-200
                    "
                >
                    <x-heroicon-o-x-mark class="mr-2 h-4 w-4"/>

                    Tolak Pembatalan
                </button>


                {{-- Approve --}}

                <button
                    type="button"
                    id="approve-cancellation"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        bg-red-600
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-red-700
                        focus:outline-none
                        focus:ring-2
                        focus:ring-red-200
                    "
                >
                    <x-heroicon-o-check class="mr-2 h-4 w-4"/>

                    Setujui Pembatalan
                </button>

            @endif


            {{-- =====================================================
                ADMIN CANCEL
            ====================================================== --}}

            @if(
                in_array(
                    $order->status,
                    ['pending', 'paid', 'processing'],
                    true
                )
            )

                <button
                    type="button"
                    id="admin-cancel-order"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        border
                        border-red-200
                        bg-white
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-red-600
                        transition
                        hover:bg-red-50
                        focus:outline-none
                        focus:ring-2
                        focus:ring-red-100
                    "
                >
                    Batalkan Order
                </button>

            @endif
        </div>
        
        <div class="flex flex-wrap flex-row items-center gap-3">
            {{-- =====================================================
                PACKING LABEL
            ====================================================== --}}

            @if($order->canDownloadPackingLabel())

                <x-admin.button
                    variant="outline"
                    icon="tag"
                    href="{{ route('admin.orders.packing-label', $order) }}"
                    target="_blank">
                    Packing Label
                </x-admin.button>

            @endif
            
            @if($order->canDownloadInvoice())

                <x-admin.button
                    type="button"
                    variant="outline"
                    icon="document-text"
                    onclick="window.open(
                        '{{ route('admin.orders.invoice', $order) }}',
                        '_blank'
                    )">
                    Invoice
                </x-admin.button>

            @endif
        </div>

    </div>

</x-admin.card-body>

</x-admin.card>

{{-- ================================================================
CUSTOMER CANCELLATION REQUEST
================================================================ --}}

@if(
    $order->status === 'req_cancel'
    && $cancellationRequest
    && $cancellationRequest->status === 'pending'
)

{{-- =============================================================
    APPROVE MODAL
============================================================= --}}

<div
    id="approve-cancellation-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="approve-cancellation-modal-title"
>

    {{-- Overlay --}}

    <div
        id="approve-cancellation-overlay"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
    ></div>


    {{-- Wrapper --}}

    <div
        class="
            relative
            flex
            min-h-full
            items-center
            justify-center
            p-4
        "
    >

        {{-- Modal --}}

        <div
            class="
                relative
                w-full
                max-w-lg
                overflow-hidden
                rounded-2xl
                bg-white
                shadow-2xl
            "
        >

            {{-- Header --}}

            <div
                class="
                    flex
                    items-start
                    justify-between
                    border-b
                    border-gray-200
                    px-6
                    py-5
                "
            >

                <div>

                    <h2
                        id="approve-cancellation-modal-title"
                        class="text-lg font-bold text-gray-900"
                    >
                        Setujui Pembatalan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $order->order_number }}
                    </p>

                </div>


                <button
                    type="button"
                    id="approve-cancellation-close"
                    class="
                        rounded-lg
                        p-2
                        text-gray-400
                        transition
                        hover:bg-gray-100
                        hover:text-gray-600
                    "
                    aria-label="Tutup"
                >

                    <x-heroicon-o-x-mark class="h-5 w-5"/>

                </button>

            </div>


            {{-- Body --}}

            <div class="space-y-5 px-6 py-6">

                {{-- Warning --}}

                <div
                    class="
                        rounded-xl
                        border
                        border-red-200
                        bg-red-50
                        p-4
                    "
                >

                    <div class="flex gap-3">

                        <x-heroicon-o-exclamation-triangle
                            class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                        />

                        <div>

                            <p class="text-sm font-semibold text-red-800">
                                Konfirmasi Pembatalan
                            </p>

                            <p class="mt-1 text-sm leading-6 text-red-700">

                                Permintaan pembatalan customer akan
                                disetujui. Order akan menjadi
                                <strong>dibatalkan</strong>, stok produk
                                akan dikembalikan ke inventory, dan refund
                                akan dibuat untuk pembayaran yang telah
                                diterima.

                            </p>

                        </div>

                    </div>

                </div>


                {{-- Customer Reason --}}

                <div>

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-gray-500
                        "
                    >
                        Alasan Customer
                    </p>

                    <div
                        class="
                            mt-2
                            rounded-xl
                            border
                            border-gray-200
                            bg-gray-50
                            p-4
                        "
                    >

                        <p
                            class="
                                text-sm
                                leading-6
                                text-gray-700
                            "
                        >
                            {{ $cancellationRequest->reason }}
                        </p>

                    </div>

                </div>


                {{-- Admin Notes --}}

                <div>

                    <label
                        for="approve-cancellation-notes"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-gray-700
                        "
                    >
                        Catatan Admin
                        <span class="font-normal text-gray-400">
                            (Opsional)
                        </span>
                    </label>

                    <textarea
                        id="approve-cancellation-notes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Tambahkan catatan jika diperlukan."
                        class="
                            block
                            w-full
                            resize-none
                            rounded-xl
                            border
                            border-gray-300
                            bg-white
                            px-4
                            py-3
                            text-sm
                            text-gray-900
                            placeholder:text-gray-400
                            transition
                            focus:border-red-500
                            focus:outline-none
                            focus:ring-2
                            focus:ring-red-100
                        "
                    ></textarea>

                    <p
                        id="approve-cancellation-error"
                        class="mt-1 hidden text-xs font-medium text-red-600"
                    ></p>

                </div>

            </div>


            {{-- Footer --}}

            <div
                class="
                    flex
                    items-center
                    justify-end
                    gap-3
                    border-t
                    border-gray-200
                    bg-gray-50
                    px-6
                    py-4
                "
            >

                <button
                    type="button"
                    id="approve-cancellation-cancel"
                    class="
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-100
                    "
                >
                    Batal
                </button>


                <button
                    type="button"
                    id="approve-cancellation-confirm"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        bg-red-600
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-red-700
                        focus:outline-none
                        focus:ring-2
                        focus:ring-red-200
                        disabled:cursor-not-allowed
                        disabled:opacity-50
                    "
                >
                    Setujui Pembatalan
                </button>

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
    REJECT MODAL
============================================================= --}}

<div
    id="reject-cancellation-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reject-cancellation-modal-title"
>

    {{-- Overlay --}}

    <div
        id="reject-cancellation-overlay"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
    ></div>


    {{-- Wrapper --}}

    <div
        class="
            relative
            flex
            min-h-full
            items-center
            justify-center
            p-4
        "
    >

        {{-- Modal --}}

        <div
            class="
                relative
                w-full
                max-w-lg
                overflow-hidden
                rounded-2xl
                bg-white
                shadow-2xl
            "
        >

            {{-- Header --}}

            <div
                class="
                    flex
                    items-start
                    justify-between
                    border-b
                    border-gray-200
                    px-6
                    py-5
                "
            >

                <div>

                    <h2
                        id="reject-cancellation-modal-title"
                        class="text-lg font-bold text-gray-900"
                    >
                        Tolak Pembatalan
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $order->order_number }}
                    </p>

                </div>


                <button
                    type="button"
                    id="reject-cancellation-close"
                    class="
                        rounded-lg
                        p-2
                        text-gray-400
                        transition
                        hover:bg-gray-100
                        hover:text-gray-600
                    "
                    aria-label="Tutup"
                >

                    <x-heroicon-o-x-mark class="h-5 w-5"/>

                </button>

            </div>


            {{-- Body --}}

            <div class="space-y-5 px-6 py-6">

                {{-- Information --}}

                <div
                    class="
                        rounded-xl
                        border
                        border-amber-200
                        bg-amber-50
                        p-4
                    "
                >

                    <div class="flex gap-3">

                        <x-heroicon-o-information-circle
                            class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                        />

                        <div>

                            <p class="text-sm font-semibold text-amber-900">
                                Permintaan Pembatalan
                            </p>

                            <p class="mt-1 text-sm leading-6 text-amber-800">

                                Jika ditolak, order akan dikembalikan ke
                                status sebelum customer mengajukan
                                pembatalan.

                            </p>

                        </div>

                    </div>

                </div>


                {{-- Customer Reason --}}

                <div>

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-gray-500
                        "
                    >
                        Alasan Customer
                    </p>

                    <div
                        class="
                            mt-2
                            rounded-xl
                            border
                            border-gray-200
                            bg-gray-50
                            p-4
                        "
                    >

                        <p
                            class="
                                text-sm
                                leading-6
                                text-gray-700
                            "
                        >
                            {{ $cancellationRequest->reason }}
                        </p>

                    </div>

                </div>


                {{-- Admin Notes --}}

                <div>

                    <label
                        for="reject-cancellation-notes"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-gray-700
                        "
                    >
                        Alasan / Catatan Penolakan
                        <span class="text-red-500">*</span>
                    </label>

                    <textarea
                        id="reject-cancellation-notes"
                        rows="4"
                        maxlength="1000"
                        placeholder="Contoh: Pesanan sudah diproses dan tidak dapat dibatalkan."
                        class="
                            block
                            w-full
                            resize-none
                            rounded-xl
                            border
                            border-gray-300
                            bg-white
                            px-4
                            py-3
                            text-sm
                            text-gray-900
                            placeholder:text-gray-400
                            transition
                            focus:border-gray-500
                            focus:outline-none
                            focus:ring-2
                            focus:ring-gray-200
                        "
                    ></textarea>

                    <p
                        id="reject-cancellation-error"
                        class="mt-1 hidden text-xs font-medium text-red-600"
                    ></p>

                </div>

            </div>


            {{-- Footer --}}

            <div
                class="
                    flex
                    items-center
                    justify-end
                    gap-3
                    border-t
                    border-gray-200
                    bg-gray-50
                    px-6
                    py-4
                "
            >

                <button
                    type="button"
                    id="reject-cancellation-cancel"
                    class="
                        rounded-lg
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-100
                    "
                >
                    Batal
                </button>


                <button
                    type="button"
                    id="reject-cancellation-confirm"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        rounded-lg
                        bg-gray-800
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-gray-900
                        focus:outline-none
                        focus:ring-2
                        focus:ring-gray-200
                        disabled:cursor-not-allowed
                        disabled:opacity-50
                    "
                >
                    Tolak Pembatalan
                </button>

            </div>

        </div>

    </div>

</div>

@endif

{{-- ================================================================
ADMIN CANCEL MODAL
================================================================ --}}

@if(
in_array(
$order->status,
['pending', 'paid', 'processing'],
true
)
)

<div
    id="admin-cancel-modal"
    class="fixed inset-0 z-50 hidden"
    aria-labelledby="admin-cancel-modal-title"
    role="dialog"
    aria-modal="true"
>


{{-- Overlay --}}

<div
    id="admin-cancel-overlay"
    class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
></div>


{{-- Modal Wrapper --}}

<div
    class="
        relative
        flex
        min-h-full
        items-center
        justify-center
        p-4
    "
>

    {{-- Modal --}}

    <div
        class="
            relative
            w-full
            max-w-lg
            overflow-hidden
            rounded-2xl
            bg-white
            shadow-2xl
        "
    >

        {{-- Header --}}

        <div
            class="
                flex
                items-start
                justify-between
                border-b
                border-gray-200
                px-6
                py-5
            "
        >

            <div>

                <h2
                    id="admin-cancel-modal-title"
                    class="text-lg font-bold text-gray-900"
                >
                    Batalkan Order
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $order->order_number }}
                </p>

            </div>


            {{-- Close --}}

            <button
                type="button"
                id="admin-cancel-close"
                class="
                    rounded-lg
                    p-2
                    text-gray-400
                    transition
                    hover:bg-gray-100
                    hover:text-gray-600
                "
                aria-label="Tutup"
            >

                <x-heroicon-o-x-mark class="h-5 w-5"/>

            </button>

        </div>


        {{-- Body --}}

        <div class="space-y-5 px-6 py-6">

            {{-- Warning --}}

            <div
                class="
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    p-4
                "
            >

                <div class="flex gap-3">

                    <x-heroicon-o-exclamation-triangle
                        class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                    />

                    <div>

                        <p class="text-sm font-semibold text-red-800">
                            Perhatian
                        </p>

                        <p class="mt-1 text-sm leading-6 text-red-700">

                            Order akan dibatalkan dan stok produk akan
                            dikembalikan ke inventory.

                            @if($order->payment_status === 'paid')
                                Pembayaran yang sudah diterima juga akan
                                diproses untuk refund.
                            @endif

                        </p>

                    </div>

                </div>

            </div>


            {{-- Reason --}}

            <div>

                <label
                    for="admin-cancel-reason"
                    class="mb-2 block text-sm font-semibold text-gray-700"
                >
                    Alasan Pembatalan
                    <span class="text-red-500">*</span>
                </label>

                <textarea
                    id="admin-cancel-reason"
                    rows="4"
                    maxlength="500"
                    placeholder="Contoh: Stok fisik produk tidak tersedia di gudang."
                    class="
                        block
                        w-full
                        resize-none
                        rounded-xl
                        border
                        border-gray-300
                        bg-white
                        px-4
                        py-3
                        text-sm
                        text-gray-900
                        placeholder:text-gray-400
                        transition
                        focus:border-red-500
                        focus:outline-none
                        focus:ring-2
                        focus:ring-red-100
                    "
                ></textarea>

                <div class="mt-1 flex justify-between">

                    <p
                        id="admin-cancel-error"
                        class="hidden text-xs font-medium text-red-600"
                    ></p>

                    <p class="ml-auto text-xs text-gray-400">
                        Maks. 500 karakter
                    </p>

                </div>

            </div>

        </div>


        {{-- Footer --}}

        <div
            class="
                flex
                items-center
                justify-end
                gap-3
                border-t
                border-gray-200
                bg-gray-50
                px-6
                py-4
            "
        >

            <button
                type="button"
                id="admin-cancel-cancel"
                class="
                    rounded-lg
                    border
                    border-gray-300
                    bg-white
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-gray-700
                    transition
                    hover:bg-gray-100
                "
            >
                Batal
            </button>


            <button
                type="button"
                id="admin-cancel-confirm"
                class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-lg
                    bg-red-600
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-red-700
                    focus:outline-none
                    focus:ring-2
                    focus:ring-red-200
                    disabled:cursor-not-allowed
                    disabled:opacity-50
                "
            >
                Batalkan Order
            </button>

        </div>

    </div>

</div>


</div>

@endif
@if(
    in_array(
        $order->status,
        ['pending', 'paid', 'processing'],
        true
    )
)
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {

            /*
            |--------------------------------------------------------------------------
            | ADMIN CANCEL MODAL
            |--------------------------------------------------------------------------
            */

            const openButton =
                document.getElementById('admin-cancel-order');

            const modal =
                document.getElementById('admin-cancel-modal');

            const overlay =
                document.getElementById('admin-cancel-overlay');

            const closeButton =
                document.getElementById('admin-cancel-close');

            const cancelButton =
                document.getElementById('admin-cancel-cancel');

            const confirmButton =
                document.getElementById('admin-cancel-confirm');

            const reasonInput =
                document.getElementById('admin-cancel-reason');

            const errorMessage =
                document.getElementById('admin-cancel-error');


            /*
            |--------------------------------------------------------------------------
            | Guard
            |--------------------------------------------------------------------------
            */

            if (!openButton || !modal) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Open Modal
            |--------------------------------------------------------------------------
            */

            const openModal = () => {

                modal.classList.remove('hidden');

                document.body.classList.add(
                    'overflow-hidden'
                );

                requestAnimationFrame(() => {
                    reasonInput?.focus();
                });
            };


            /*
            |--------------------------------------------------------------------------
            | Close Modal
            |--------------------------------------------------------------------------
            */

            const closeModal = () => {

                modal.classList.add('hidden');

                document.body.classList.remove(
                    'overflow-hidden'
                );

                if (reasonInput) {
                    reasonInput.value = '';
                }

                if (errorMessage) {

                    errorMessage.textContent = '';

                    errorMessage.classList.add(
                        'hidden'
                    );
                }
            };


            /*
            |--------------------------------------------------------------------------
            | Events
            |--------------------------------------------------------------------------
            */

            openButton.addEventListener(
                'click',
                openModal
            );

            closeButton?.addEventListener(
                'click',
                closeModal
            );

            cancelButton?.addEventListener(
                'click',
                closeModal
            );

            overlay?.addEventListener(
                'click',
                closeModal
            );


            /*
            |--------------------------------------------------------------------------
            | Escape
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                (event) => {

                    if (
                        event.key === 'Escape'
                        && !modal.classList.contains('hidden')
                    ) {
                        closeModal();
                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Confirm
            |--------------------------------------------------------------------------
            */

            confirmButton?.addEventListener(
                'click',
                async () => {

                    const reason =
                        reasonInput?.value.trim() ?? '';


                    /*
                    |--------------------------------------------------------------------------
                    | Validate
                    |--------------------------------------------------------------------------
                    */

                    if (!reason) {

                        errorMessage.textContent =
                            'Alasan pembatalan wajib diisi.';

                        errorMessage.classList.remove(
                            'hidden'
                        );

                        reasonInput?.focus();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Clear Error
                    |--------------------------------------------------------------------------
                    */

                    errorMessage.textContent = '';

                    errorMessage.classList.add(
                        'hidden'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Loading
                    |--------------------------------------------------------------------------
                    */

                    const originalText =
                        confirmButton.innerHTML;

                    confirmButton.disabled = true;

                    confirmButton.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    /*
                    |--------------------------------------------------------------------------
                    | Request
                    |--------------------------------------------------------------------------
                    */

                    try {

                        const csrfToken =
                            document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.getAttribute('content');


                        if (!csrfToken) {
                            throw new Error(
                                'CSRF token tidak ditemukan.'
                            );
                        }


                        const formData =
                            new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        formData.append(
                            'reason',
                            reason
                        );


                        const response =
                            await fetch(
                                @json(
                                    route(
                                        'admin.orders.cancel',
                                        $order
                                    )
                                ),
                                {
                                    method: 'POST',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            csrfToken,

                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },

                                    body: formData,
                                }
                            );


                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';


                        let data = null;


                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data =
                                await response.json();
                        }


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ??
                                'Gagal membatalkan order.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Redirect
                        |--------------------------------------------------------------------------
                        */

                        window.location.href =
                            data?.redirect
                            ??
                            @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Admin cancel order error:',
                            error
                        );


                        confirmButton.disabled =
                            false;

                        confirmButton.innerHTML =
                            originalText;


                        errorMessage.textContent =
                            error.message
                            ??
                            'Terjadi kesalahan saat membatalkan order.';

                        errorMessage.classList.remove(
                            'hidden'
                        );
                    }

                }
            );

        });
        </script>
    @endpush
@endif

@if(
    $order->status === 'req_cancel'
    && $cancellationRequest
    && $cancellationRequest->status === 'pending'
)
    @push('scripts')

        <script>
        document.addEventListener('DOMContentLoaded', () => {

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

            if (!csrfToken) {
                console.error('CSRF token tidak ditemukan.');
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Cancellation URLs
            |--------------------------------------------------------------------------
            */

            const approveUrl = @json(
                route(
                    'admin.orders.cancellation.approve',
                    $cancellationRequest
                )
            );

            const rejectUrl = @json(
                route(
                    'admin.orders.cancellation.reject',
                    $cancellationRequest
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Elements
            |--------------------------------------------------------------------------
            */

            const approveButton =
                document.getElementById('approve-cancellation');

            const approveModal =
                document.getElementById('approve-cancellation-modal');

            const approveOverlay =
                document.getElementById('approve-cancellation-overlay');

            const approveClose =
                document.getElementById('approve-cancellation-close');

            const approveCancel =
                document.getElementById('approve-cancellation-cancel');

            const approveConfirm =
                document.getElementById('approve-cancellation-confirm');

            const approveNotes =
                document.getElementById('approve-cancellation-notes');

            const approveError =
                document.getElementById('approve-cancellation-error');


            const rejectButton =
                document.getElementById('reject-cancellation');

            const rejectModal =
                document.getElementById('reject-cancellation-modal');

            const rejectOverlay =
                document.getElementById('reject-cancellation-overlay');

            const rejectClose =
                document.getElementById('reject-cancellation-close');

            const rejectCancel =
                document.getElementById('reject-cancellation-cancel');

            const rejectConfirm =
                document.getElementById('reject-cancellation-confirm');

            const rejectNotes =
                document.getElementById('reject-cancellation-notes');

            const rejectError =
                document.getElementById('reject-cancellation-error');


            /*
            |--------------------------------------------------------------------------
            | Modal Helper
            |--------------------------------------------------------------------------
            */

            const showModal = (modal) => {

                if (!modal) {
                    return;
                }

                modal.classList.remove('hidden');

                document.body.classList.add('overflow-hidden');
            };


            const hideModal = (modal) => {

                if (!modal) {
                    return;
                }

                modal.classList.add('hidden');

                const openModal =
                    document.querySelector(
                        '[role="dialog"]:not(.hidden)'
                    );

                if (!openModal) {
                    document.body.classList.remove('overflow-hidden');
                }
            };


            /*
            |--------------------------------------------------------------------------
            | APPROVE
            |--------------------------------------------------------------------------
            */

            const openApproveModal = () => {

                showModal(approveModal);

                requestAnimationFrame(() => {
                    approveNotes?.focus();
                });
            };


            const closeApproveModal = () => {

                hideModal(approveModal);

                if (approveNotes) {
                    approveNotes.value = '';
                }

                if (approveError) {
                    approveError.textContent = '';
                    approveError.classList.add('hidden');
                }
            };


            approveButton?.addEventListener(
                'click',
                openApproveModal
            );

            approveClose?.addEventListener(
                'click',
                closeApproveModal
            );

            approveCancel?.addEventListener(
                'click',
                closeApproveModal
            );

            approveOverlay?.addEventListener(
                'click',
                closeApproveModal
            );


            approveConfirm?.addEventListener(
                'click',
                async () => {

                    const originalText =
                        approveConfirm.innerHTML;

                    approveConfirm.disabled = true;

                    approveConfirm.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        const formData = new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        const notes =
                            approveNotes?.value.trim() ?? '';

                        if (notes) {

                            formData.append(
                                'admin_notes',
                                notes
                            );

                        }


                        const response = await fetch(
                            approveUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: formData,
                            }
                        );


                        if (response.redirected) {

                            window.location.href =
                                response.url;

                            return;
                        }


                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        let data = null;

                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data = await response.json();
                        }


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menyetujui pembatalan.'
                            );
                        }


                        window.location.href =
                            data?.redirect
                            ?? @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Approve cancellation error:',
                            error
                        );

                        approveConfirm.disabled = false;

                        approveConfirm.innerHTML =
                            originalText;

                        if (approveError) {

                            approveError.textContent =
                                error.message
                                ?? 'Terjadi kesalahan saat memproses pembatalan.';

                            approveError.classList.remove(
                                'hidden'
                            );
                        }

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | REJECT
            |--------------------------------------------------------------------------
            */

            const openRejectModal = () => {

                showModal(rejectModal);

                requestAnimationFrame(() => {
                    rejectNotes?.focus();
                });
            };


            const closeRejectModal = () => {

                hideModal(rejectModal);

                if (rejectNotes) {
                    rejectNotes.value = '';
                }

                if (rejectError) {
                    rejectError.textContent = '';
                    rejectError.classList.add('hidden');
                }
            };


            rejectButton?.addEventListener(
                'click',
                openRejectModal
            );

            rejectClose?.addEventListener(
                'click',
                closeRejectModal
            );

            rejectCancel?.addEventListener(
                'click',
                closeRejectModal
            );

            rejectOverlay?.addEventListener(
                'click',
                closeRejectModal
            );


            rejectConfirm?.addEventListener(
                'click',
                async () => {

                    const notes =
                        rejectNotes?.value.trim() ?? '';


                    if (!notes) {

                        rejectError.textContent =
                            'Alasan atau catatan penolakan wajib diisi.';

                        rejectError.classList.remove(
                            'hidden'
                        );

                        rejectNotes?.focus();

                        return;
                    }


                    const originalText =
                        rejectConfirm.innerHTML;

                    rejectConfirm.disabled = true;

                    rejectConfirm.innerHTML = `
                        <svg
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 01-4 4H4z"
                            ></path>
                        </svg>

                        Memproses...
                    `;


                    try {

                        const formData = new FormData();

                        formData.append(
                            '_method',
                            'PATCH'
                        );

                        formData.append(
                            'admin_notes',
                            notes
                        );


                        const response = await fetch(
                            rejectUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },

                                body: formData,
                            }
                        );


                        if (response.redirected) {

                            window.location.href =
                                response.url;

                            return;
                        }


                        const contentType =
                            response.headers.get(
                                'content-type'
                            ) || '';

                        let data = null;

                        if (
                            contentType.includes(
                                'application/json'
                            )
                        ) {
                            data = await response.json();
                        }


                        if (!response.ok) {

                            throw new Error(
                                data?.message
                                ?? 'Gagal menolak pembatalan.'
                            );
                        }


                        window.location.href =
                            data?.redirect
                            ?? @json(
                                route(
                                    'admin.orders.show',
                                    $order
                                )
                            );

                    } catch (error) {

                        console.error(
                            'Reject cancellation error:',
                            error
                        );

                        rejectConfirm.disabled = false;

                        rejectConfirm.innerHTML =
                            originalText;

                        rejectError.textContent =
                            error.message
                            ?? 'Terjadi kesalahan saat memproses penolakan.';

                        rejectError.classList.remove(
                            'hidden'
                        );

                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Escape
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                (event) => {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    closeApproveModal();
                    closeRejectModal();

                }
            );

        });
        </script>

    @endpush
@endif
