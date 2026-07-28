<?php

namespace App\Http\Controllers\Admin\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
    ) {}

    public function store(Request $request,Order $order) {
        $data = $request->validate([
            'booking_code' => ['nullable','string'],
            'tracking_number' => ['nullable','string'],
            'label_url' => ['nullable','string'],
            'status' => ['nullable','string'],
            'metadata' => ['nullable','array'],
        ]);

        $shipment = $this->shipmentService->create($order,$data);

        return response()->json([
            'message' => 'Shipment berhasil dibuat.',
            'data' => $shipment,
        ]);
    }

    public function pickup(Shipment $shipment) {
        $shipment = $this->shipmentService->markPickedUp($shipment,auth()->user());
        return response()->json([
            'message'=>'Shipment berhasil dipickup.',
            'data'=>$shipment,
        ]);
    }

    public function transit(Shipment $shipment) {
        $shipment = $this->shipmentService->markInTransit($shipment,auth()->user());
        return response()->json([
            'message'=>'Shipment sedang dikirim.',
            'data'=>$shipment,
        ]);
    }

    public function delivered(Shipment $shipment) {
        $shipment = $this->shipmentService->markDelivered($shipment,auth()->user());
        return response()->json([
            'message'=>'Shipment berhasil dikirim.',
            'data'=>$shipment,
        ]);
    }
    
    public function cancel(Shipment $shipment) {
        $shipment = $this->shipmentService->cancel($shipment,auth()->user());
        return response()->json([
            'message'=>'Shipment dibatalkan.',
            'data'=>$shipment,
        ]);
    }

}
