Oke. Saya sudah membaca dan mengaudit `shipment.md` yang Anda lampirkan. File tersebut berisi kondisi **kode shipping saat ini**, mulai dari konfigurasi, `JntCargoClient`, `JntCargoService`, `CourierService`, `DeliveryService`, `OrderFulfillmentService`, `ShipmentService`, migration `shipments`, sampai routes customer/admin. 

Dan saya akan mengikuti prinsip yang kita sepakati sebelumnya:

> **Manual shipment tetap aktif dan tidak boleh rusak. Integrasi J&T Cargo dibangun di atas fondasi yang sudah ada, bukan mengganti workflow manual.**

## 1. Hasil audit kondisi sekarang

Secara arsitektur, fondasi Anda **sudah cukup bagus untuk mulai integrasi J&T Cargo**.

Saat ini alurnya sudah terpisah dengan cukup jelas:

```text
Order
  │
  ├── OrderWorkflowService
  │
  ├── OrderFulfillmentService
  │
  └── CourierService
          │
          ├── JntCargoService
          │       └── JntCargoClient
          │
          └── SentralCargoService
```

Kemudian shipment lokal tetap ditangani oleh:

```text
ShipmentService
       │
       └── shipments table
```

Sementara workflow manual menggunakan `createManual()`, sehingga pembuatan shipment manual tidak bergantung pada API J&T. 

**Ini penting dan sebaiknya kita pertahankan.**

---

# 2. Ada satu masalah besar yang harus kita perbaiki sebelum integrasi J&T

Konfigurasi J&T sekarang masih menggunakan:

```php
'api_key' => env('JNT_CARGO_API_KEY'),
'api_secret' => env('JNT_CARGO_API_SECRET'),
```

dan `.env`:

```env
JNT_CARGO_BASE_URL=null
JNT_CARGO_API_KEY=null
JNT_CARGO_API_SECRET=null
```



Padahal dari credential J&T yang sekarang Anda miliki, istilah yang digunakan adalah:

* **API Account**
* **Private Key**
* **Kode Pengguna / Customer Code**
* **Nama Sumber**

Jadi saya **tidak menyarankan** kita sekadar memasukkan:

```env
JNT_CARGO_API_KEY=...
JNT_CARGO_API_SECRET=...
```

karena nama tersebut tidak merepresentasikan struktur autentikasi J&T Cargo yang sebenarnya.

Lebih baik kita rapikan konfigurasi menjadi sesuatu yang benar-benar mengikuti terminologi API J&T.

---

# 3. Credential yang sudah Anda miliki

Dari informasi Anda, kita sudah punya:

| Credential                    | Status           |
| ----------------------------- | ---------------- |
| API Account                   | ✅ Ada            |
| Private Key                   | ✅ Ada            |
| Kode Pengguna / Customer Code | ✅ Ada            |
| Nama Sumber                   | ✅ Ada            |
| Password Customer Code        | ⚠️ Belum disebut |

Yang terakhir ini **perlu kita perhatikan**.

Dalam mekanisme autentikasi J&T Cargo, ada credential customer yang digunakan dalam pembentukan `cipherText`/digest body. Jadi sebelum kita mengimplementasikan digest final, kita perlu memastikan apakah akun/platform yang Anda daftarkan memberikan **password untuk Customer Code** atau mekanisme password tersebut berada di bagian lain platform.

**Jangan kirim password/private key/API credential rahasia kepada saya.** Cukup beri tahu apakah credential tersebut tersedia dan nama field-nya di platform.

---

# 4. `JntCargoClient` memang sengaja masih kosong — ini justru bagus

Saat ini `JntCargoClient` belum menebak-nebak autentikasi:

```php
/*
 | Authentication
 |
 | Jangan menebak nama header API J&T Cargo.
 |
 | Format authentication akan disesuaikan setelah dokumentasi
 | resmi dan credential perusahaan tersedia.
 */
```



Menurut saya **jangan hapus pendekatan ini dengan buru-buru**.

Sekarang kita sudah punya credential resmi, jadi tahap berikutnya memang mengubah client dari:

```text
HTTP client kosong
```

menjadi:

```text
JntCargoClient
      │
      ├── base URL
      ├── API Account
      ├── Private Key
      ├── Customer Code
      ├── customer credential
      │
      ├── timestamp
      ├── digest
      │
      └── POST J&T Cargo
```

Tetapi **belum langsung membuat `createShipment()`**.

---

# 5. `CourierShipmentResult` tidak perlu dibuang

DTO Anda sekarang:

```php
CourierShipmentResult
├── success
├── bookingCode
├── trackingNumber
├── labelUrl
├── status
├── metadata
└── message
```



Ini masih cocok dengan kebutuhan kita.

Nanti respons J&T bisa diterjemahkan ke DTO ini.

Contohnya secara konsep:

```text
J&T response
     │
     ▼
JntCargoService
     │
     ▼
CourierShipmentResult
     │
     ▼
ShipmentService
     │
     ▼
shipments
```

Jadi **domain aplikasi Anda tidak perlu tahu struktur mentah JSON J&T**.

Ini bagus untuk mempertahankan kemampuan mendukung:

```text
J&T Cargo
Sentral Cargo
Courier lain di masa depan
```

melalui `CourierInterface`.

---

# 6. Ada masalah arsitektur yang nanti harus kita tangani

Saya menemukan satu bagian yang **belum aman untuk integrasi API otomatis**.

`DeliveryService` sekarang membutuhkan `Admin`:

```php
public function markPickedUp(
    Shipment $shipment,
    Admin $admin
)
```

dan `markInTransit()` serta `markDelivered()` juga membutuhkan `Admin`. 

Sementara `DeliveryService` sendiri memiliki:

```php
?string $createdBy = 'system'
```

tetapi kemudian `system` ditolak:

```php
if ($createdBy === 'system') {
    throw new RuntimeException(
        'DeliveryService membutuhkan Admin untuk proses shipment.'
    );
}
```



Untuk **manual admin flow**, ini tidak masalah.

Tetapi nanti J&T akan bisa mengirim:

```text
Webhook
   ↓
Laravel
   ↓
Shipment status update
   ↓
Order status update
```

Tidak ada Admin yang menekan tombol.

Jadi nanti kita perlu memperkenalkan konsep **system/webhook actor** dengan benar.

Namun **jangan kita ubah sekarang** sebelum bagian webhook J&T kita kerjakan.

---

# 7. `OrderFulfillmentService` juga perlu perhatian khusus

Saat ini:

```text
OrderFulfillmentService
        ↓
CourierService
        ↓
JntCargoService
        ↓
createShipment()
```

dan baru setelah courier berhasil:

```text
Order → processing
        ↓
Shipment lokal dibuat
```



Secara konsep ini bagus untuk API courier.

Tetapi ada konsekuensi penting:

```text
Laravel DB Transaction
        │
        ├── call API J&T
        │
        ├── J&T berhasil membuat shipment
        │
        └── Laravel kemudian gagal
```

J&T **tidak akan ikut rollback**.

Ini merupakan masalah klasik:

> **database transaction ≠ external API transaction**

Karena itu nanti ketika kita mengaktifkan `createOrder` J&T, kita harus membuat mekanisme **idempotency / duplicate protection** berdasarkan `txlogisticId`.

Ini penting supaya klik dua kali atau retry HTTP tidak membuat dua shipment J&T.

**Belum perlu kita implementasikan sekarang**, tetapi saya tandai sebagai requirement wajib.

---

# 8. Database shipment sudah mendukung kebutuhan dasar J&T

Struktur lokal Anda sudah memiliki:

```text
shipments
├── order_id
├── courier
├── service
├── booking_code
├── tracking_number
├── label_url
├── status
├── metadata
├── picked_up_at
├── delivered_at
└── last_tracking_sync_at
```

dan `order_id` sudah diperbaiki menjadi UUID serta unique sehingga satu order hanya mempunyai satu shipment. 

Ini sudah cocok sebagai fondasi.

Khusus J&T:

```text
booking_code
```

nantinya bisa kita gunakan untuk menyimpan identifier transaksi internal/J&T jika memang hasil API membutuhkan pemetaan tersebut.

Sedangkan:

```text
tracking_number
```

akan menyimpan nomor resi/AWB.

Kita tidak perlu membuat tabel shipment baru hanya karena J&T.

---

# 9. Manual shipment benar-benar aman untuk tetap berjalan

Bagian ini saya anggap **protected area** selama pengerjaan J&T.

Saat ini:

```php
createManual()
```

langsung membuat:

```text
Shipment
courier
service
booking_code = null
tracking_number = null
status = waiting_pickup
metadata.source = manual
```



Jadi kita akan mempertahankan dua jalur:

### Manual

```text
Admin
 ↓
Buat Pengiriman
 ↓
Shipment lokal
 ↓
Input Resi
 ↓
Pickup
 ↓
Transit
 ↓
Delivered
```

### J&T API

```text
Admin / Automation
 ↓
JntCargoService
 ↓
J&T Cargo API
 ↓
Create Order
 ↓
billCode / AWB
 ↓
Shipment lokal
 ↓
Webhook / Tracking
 ↓
Pickup → Transit → Delivered
```

**Keduanya coexist.**

---

# 10. Routes juga tidak perlu kita ubah dulu

Routes manual saat ini sudah tersedia:

```text
POST   /admin/orders/{order}/shipment
PATCH  /admin/orders/{order}/shipment/tracking-number
PATCH  /admin/orders/{order}/shipment/pickup
PATCH  /admin/orders/{order}/shipment/transit
PATCH  /admin/orders/{order}/shipment/delivered
PATCH  /admin/orders/{order}/shipment/cancel
```



**Jangan kita sentuh.**

Nanti API J&T bisa bekerja di service layer tanpa mengganggu endpoint manual.

---

# 11. Jadi urutan pengerjaan yang saya sarankan sekarang

Kita **belum langsung membuat Create Order J&T**.

Saya ingin kita kerjakan secara bertahap seperti ini:

### Tahap A — Rapikan konfigurasi J&T

Ubah konsep:

```env
JNT_CARGO_API_KEY
JNT_CARGO_API_SECRET
```

menjadi konfigurasi yang merepresentasikan credential J&T sebenarnya.

Misalnya secara konsep:

```env
JNT_CARGO_BASE_URL=
JNT_CARGO_API_ACCOUNT=
JNT_CARGO_PRIVATE_KEY=
JNT_CARGO_CUSTOMER_CODE=
JNT_CARGO_CUSTOMER_PASSWORD=
JNT_CARGO_SOURCE_NAME=
JNT_CARGO_TIMEOUT=30
```

**Nama final field akan kita tentukan berdasarkan dokumentasi resmi**, bukan asal membuat nama.

---

### Tahap B — Buat `JntCargoAuthenticator`

Pisahkan:

```text
JntCargoClient
```

dari:

```text
JntCargoAuthenticator
```

sehingga tanggung jawabnya menjadi:

```text
JntCargoAuthenticator
├── timestamp
├── digest
├── body digest
└── authentication headers
```

Sedangkan:

```text
JntCargoClient
├── HTTP
├── endpoint
├── form request
└── response
```

Ini akan membuat testing digest dengan **Tinker** jauh lebih mudah.

---

### Tahap C — Test authentication saja

Belum membuat order.

Kita test:

```text
API Account
Private Key
Customer Code
Customer credential
       ↓
digest
       ↓
request
       ↓
J&T Sandbox
```

Dengan Tinker kita bisa memastikan:

```text
timestamp        ✓
bizContent       ✓
digest           ✓
HTTP header      ✓
endpoint         ✓
response         ✓
```

Kalau ini sudah berhasil, baru kita lanjut.

---

### Tahap D — `Create Order`

Baru kita implementasikan:

```text
JntCargoService::createShipment()
```

dengan mapping:

```text
Order Laravel
      ↓
J&T Create Order Request
      ↓
J&T Response
      ↓
CourierShipmentResult
      ↓
Shipment lokal
```

Di tahap ini kita juga akan membuat mapping field order → J&T secara eksplisit.

---

### Tahap E — Check Order

Kemudian:

```text
getOrders
```

untuk memastikan order yang kita buat benar-benar tercatat di J&T.

---

### Tahap F — Tracking

Baru:

```text
logistics/trace
```

dan kita hubungkan dengan:

```text
Shipment.last_tracking_sync_at
```

---

### Tahap G — Webhook

Setelah API request/response sudah stabil:

```text
J&T
 ↓
Webhook Laravel
 ↓
Validasi payload
 ↓
Cari shipment
 ↓
Update shipment
 ↓
Update order
 ↓
Timeline
 ↓
WhatsApp
```

Di sinilah kita akan menyelesaikan masalah `system actor` yang saya temukan tadi.

---

### Tahap H — Waybill / print

Kemudian:

```text
printOrder
```

dan integrasikan dengan:

```text
label_url
```

atau mekanisme penyimpanan label yang sesuai dengan respons API sebenarnya.

---

# 12. Satu hal yang saya butuhkan sebelum kita coding

**Jangan kirim API Account, Private Key, password, atau credential rahasia di chat.**

Saya hanya perlu Anda memastikan satu hal:

> **Di J&T Cargo Platform, apakah Anda juga mendapatkan / memiliki Password untuk Kode Pengguna (Customer Code)?**

Jawab cukup:

**A. Ada, saya tahu password-nya dan field-nya di platform**

atau

**B. Tidak ada / saya tidak menemukan password tersebut**

atau

**C. Nama sumber yang saya maksud ternyata adalah credential/password tersebut**

Setelah itu kita bisa mulai **Tahap A: merapikan `config/shipping.php` dan `.env`**, kemudian langsung **test konfigurasi menggunakan Tinker**, tanpa menyentuh workflow manual shipment yang sudah berjalan.
