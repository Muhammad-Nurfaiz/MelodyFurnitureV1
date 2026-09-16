D.1  Fix body digest salt
 ↓
D.2  Buat JntCargoOrderPayload
 ↓
D.3  Mapping Order → payload J&T
 ↓
D.4  Implement JntCargoClient::createShipment()
 ↓
D.5  Response → CourierShipmentResult
 ↓
D.6  Idempotency / duplicate protection
 ↓
D.7  Integrasi dengan OrderFulfillmentService
 ↓
D.8  Test dengan Http::fake()
 ↓
D.9  Test sandbox J&T

Tahap A — buat mapper murni

Misalnya:

scanCode J&T
      ↓
JntCargoTrackingStatusMapper
      ↓
status internal Melody

Mapper hanya menentukan:

1  → picked_up
3  → in_transit
4  → in_transit
5  → in_transit
10 → delivered

tanpa mengubah database.

Tahap B — test mapper

Kita pastikan seluruh scanCode menghasilkan status yang benar.

Tahap C — integrasikan mapper ke JntCargoService::tracking()

Baru CourierShipmentResult membawa status hasil mapping.

Tahap D — integrasikan dengan ShipmentService

Baru kita menentukan bagaimana status internal berubah secara otomatis tanpa merusak workflow markInTransit() / markDelivered().