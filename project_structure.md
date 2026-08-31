# Project Structure

Berikut adalah struktur folder dan file di project Anda (folder \
ode_modules\, \endor\, \storage\, dan \.git\ disembunyikan untuk kerapian):

```text
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── .phpunit.result.cache
├── app
│   ├── Console
│   │   └── Commands
│   │       ├── CleanupTemporaryMedia.php
│   │       ├── ExpirePendingPaymentsCommand.php
│   │       ├── ImportJntShippingRates.php
│   │       ├── ImportSentralShippingRates.php
│   │       └── SyncShippingRates.php
│   ├── Enums
│   │   ├── OrderStatus.php
│   │   ├── PaymentStatus.php
│   │   ├── Status.php
│   │   ├── UserRole.php
│   │   └── VoucherStatus.php
│   ├── Events
│   │   └── OrderStatusChanged.php
│   ├── Helpers
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Admin
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── Category
│   │   │   │   │   └── CategoryController.php
│   │   │   │   ├── Customer
│   │   │   │   │   └── CustomerController.php
│   │   │   │   ├── Dashboard
│   │   │   │   │   └── DashboardController.php
│   │   │   │   ├── Document
│   │   │   │   │   ├── InvoiceController.php
│   │   │   │   │   └── PackingLabelController.php
│   │   │   │   ├── Export
│   │   │   │   │   ├── OrderExportController.php
│   │   │   │   │   └── VoucherUsageExportController.php
│   │   │   │   ├── Media
│   │   │   │   │   └── TemporaryMediaController.php
│   │   │   │   ├── Order
│   │   │   │   │   ├── OrderCancellationController.php
│   │   │   │   │   └── OrderController.php
│   │   │   │   ├── Payment
│   │   │   │   │   └── RefundController.php
│   │   │   │   ├── Product
│   │   │   │   │   ├── ProductController.php
│   │   │   │   │   └── ProductMediaController.php
│   │   │   │   ├── Profile
│   │   │   │   │   └── ProfileController.php
│   │   │   │   ├── Series
│   │   │   │   │   └── SeriesController.php
│   │   │   │   ├── Settings
│   │   │   │   │   └── SettingsController.php
│   │   │   │   ├── Shipment
│   │   │   │   │   └── ShipmentController.php
│   │   │   │   ├── Shipping
│   │   │   │   │   └── ShippingRateController.php
│   │   │   │   ├── Voucher
│   │   │   │   │   └── VoucherController.php
│   │   │   │   └── Whatsapp
│   │   │   │       ├── WhatsappAutomationController.php
│   │   │   │       └── WhatsappConnectionController.php
│   │   │   ├── Api
│   │   │   │   ├── CartController.php
│   │   │   │   ├── CheckoutController.php
│   │   │   │   ├── Customer
│   │   │   │   │   ├── CustomerInvoiceController.php
│   │   │   │   │   ├── CustomerOrderCancellationController.php
│   │   │   │   │   ├── CustomerOrderController.php
│   │   │   │   │   └── CustomerSessionController.php
│   │   │   │   ├── LocationController.php
│   │   │   │   ├── MidtransWebhookController.php
│   │   │   │   ├── OrderTrackingController.php
│   │   │   │   ├── PaymentResultController.php
│   │   │   │   ├── ResumePaymentController.php
│   │   │   │   └── ShippingController.php
│   │   │   ├── Auth
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   ├── Controller.php
│   │   │   └── ProfileController.php
│   │   ├── Middleware
│   │   │   ├── CustomerSessionMiddleware.php
│   │   │   └── ResolveGuestCustomer.php
│   │   ├── Requests
│   │   │   ├── Admin
│   │   │   │   ├── Category
│   │   │   │   │   ├── StoreCategoryRequest.php
│   │   │   │   │   └── UpdateCategoryRequest.php
│   │   │   │   ├── Customer
│   │   │   │   ├── Order
│   │   │   │   │   └── ExportOrderRequest.php
│   │   │   │   ├── Product
│   │   │   │   │   ├── StoreProductRequest.php
│   │   │   │   │   └── UpdateProductRequest.php
│   │   │   │   ├── Series
│   │   │   │   │   ├── StoreSeriesRequest.php
│   │   │   │   │   └── UpdateSeriesRequest.php
│   │   │   │   ├── Shipping
│   │   │   │   │   └── UpdateShippingRateRequest.php
│   │   │   │   └── Voucher
│   │   │   ├── Api
│   │   │   │   ├── Cart
│   │   │   │   │   ├── AddCartItemRequest.php
│   │   │   │   │   └── UpdateCartItemRequest.php
│   │   │   │   ├── CheckoutRequest.php
│   │   │   │   ├── CustomerSessionRequest.php
│   │   │   │   └── MidtransWebhookRequest.php
│   │   │   ├── Auth
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Customer
│   │   │   │   └── CancellationRequest.php
│   │   │   └── ProfileUpdateRequest.php
│   │   └── Resources
│   │       ├── CartItemResource.php
│   │       ├── CartResource.php
│   │       ├── Customer
│   │       │   ├── CancellationRequestResource.php
│   │       │   ├── OrderItemResource.php
│   │       │   ├── OrderStatusHistoryResource.php
│   │       │   ├── OrderTrackingResource.php
│   │       │   ├── OrderTrackingResponseResource.php
│   │       │   └── PaymentInformationResource.php
│   │       ├── CustomerResource.php
│   │       ├── OrderItemResource.php
│   │       ├── OrderResource.php
│   │       ├── OrderTrackingResource.php
│   │       ├── PaymentResource.php
│   │       └── VoucherResource.php
│   ├── Jobs
│   │   └── SendWhatsappMessage.php
│   ├── Listeners
│   │   └── SendOrderWhatsappNotification.php
│   ├── Models
│   │   ├── Admin.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Category.php
│   │   ├── ChatHistory.php
│   │   ├── Customer.php
│   │   ├── HeroSlide.php
│   │   ├── Order.php
│   │   ├── OrderCancelRequest.php
│   │   ├── OrderItem.php
│   │   ├── OrderStatusHistory.php
│   │   ├── Payment.php
│   │   ├── Product.php
│   │   ├── ProductMedia.php
│   │   ├── ProductSpecification.php
│   │   ├── PromoBanner.php
│   │   ├── Refund.php
│   │   ├── Series.php
│   │   ├── Setting.php
│   │   ├── Shipment.php
│   │   ├── ShippingCourier.php
│   │   ├── ShippingRate.php
│   │   ├── TemporaryMedia.php
│   │   ├── Voucher.php
│   │   └── WhatsappQueue.php
│   ├── Policies
│   │   ├── CategoryPolicy.php
│   │   ├── ProductPolicy.php
│   │   └── SeriesPolicy.php
│   ├── Providers
│   │   ├── AppServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Services
│   │   ├── Admin
│   │   │   ├── AdminProfileService.php
│   │   │   ├── AdminSettingsService.php
│   │   │   └── HeroSlideService.php
│   │   ├── Cart
│   │   │   └── CartService.php
│   │   ├── Category
│   │   │   └── CategoryService.php
│   │   ├── Customer
│   │   │   ├── CustomerCancellationService.php
│   │   │   ├── CustomerOrderService.php
│   │   │   ├── CustomerPaymentService.php
│   │   │   ├── CustomerService.php
│   │   │   ├── CustomerSessionService.php
│   │   │   └── CustomerTrackingService.php
│   │   ├── Dashboard
│   │   │   └── DashboardAnalyticsService.php
│   │   ├── Document
│   │   │   ├── InvoiceService.php
│   │   │   └── PackingLabelService.php
│   │   ├── Export
│   │   │   ├── OrderExportService.php
│   │   │   └── VoucherUsageExportService.php
│   │   ├── Inventory
│   │   │   └── ProductInventoryService.php
│   │   ├── Media
│   │   │   └── TemporaryMediaService.php
│   │   ├── Order
│   │   │   ├── OrderAdminService.php
│   │   │   ├── OrderCalculatorService.php
│   │   │   ├── OrderCancellationService.php
│   │   │   ├── OrderFulfillmentService.php
│   │   │   ├── OrderNumberService.php
│   │   │   ├── OrderQueryService.php
│   │   │   ├── OrderService.php
│   │   │   ├── OrderTimelineService.php
│   │   │   ├── OrderTrackingService.php
│   │   │   ├── OrderTrackingTokenService.php
│   │   │   └── OrderWorkflowService.php
│   │   ├── Payment
│   │   │   ├── MidtransPayloadBuilder.php
│   │   │   ├── MidtransService.php
│   │   │   ├── MidtransWebhookService.php
│   │   │   ├── PaymentExpirationService.php
│   │   │   ├── PaymentResultService.php
│   │   │   ├── PaymentService.php
│   │   │   ├── RefundNumberService.php
│   │   │   ├── RefundService.php
│   │   │   └── ResumePaymentService.php
│   │   ├── Product
│   │   │   ├── ProductMediaService.php
│   │   │   └── ProductService.php
│   │   ├── Series
│   │   │   └── SeriesService.php
│   │   ├── Shipping
│   │   │   ├── Courier
│   │   │   │   ├── CourierInterface.php
│   │   │   │   ├── JntCargoService.php
│   │   │   │   └── SentralCargoService.php
│   │   │   ├── CourierService.php
│   │   │   ├── DeliveryService.php
│   │   │   ├── ShipmentService.php
│   │   │   ├── ShippingRateImportService.php
│   │   │   └── ShippingService.php
│   │   ├── Voucher
│   │   │   ├── VoucherAdminService.php
│   │   │   ├── VoucherQueryService.php
│   │   │   └── VoucherService.php
│   │   └── Whatsapp
│   │       ├── WhatsappConnectionService.php
│   │       ├── WhatsappGatewayService.php
│   │       ├── WhatsappMessageTemplateService.php
│   │       ├── WhatsappNotificationService.php
│   │       └── WhatsappSenderService.php
│   └── View
│       └── Components
│           ├── Admin
│           │   └── SidebarItem.php
│           ├── AppLayout.php
│           └── GuestLayout.php
├── artisan
├── bootstrap
│   ├── app.php
│   ├── cache
│   │   ├── .gitignore
│   │   ├── blade-icons.php
│   │   ├── config.php
│   │   ├── events.php
│   │   ├── packages.php
│   │   ├── routes-v7.php
│   │   └── services.php
│   └── providers.php
├── composer.json
├── composer.lock
├── config
│   ├── admin-menu.php
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── customer.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── midtrans.php
│   ├── nusantara.php
│   ├── payment.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── services.php
│   └── session.php
├── database
│   ├── .gitignore
│   ├── database.sqlite
│   ├── factories
│   │   └── UserFactory.php
│   ├── migrations
│   │   ├── 2026_06_05_000000_create_nusantara_tables.php
│   │   ├── 2026_06_25_160306_create_admins_table.php
│   │   ├── 2026_06_25_160430_create_customers_table.php
│   │   ├── 2026_06_25_160533_create_categories_table.php
│   │   ├── 2026_06_25_160724_create_series_table.php
│   │   ├── 2026_06_25_160800_create_products_table.php
│   │   ├── 2026_06_25_161015_create_product_media_table.php
│   │   ├── 2026_06_25_161110_create_product_specifications_table.php
│   │   ├── 2026_06_25_161149_create_carts_table.php
│   │   ├── 2026_06_25_161234_create_cart_items_table.php
│   │   ├── 2026_06_25_161318_create_vouchers_table.php
│   │   ├── 2026_06_25_161410_create_orders_table.php
│   │   ├── 2026_06_25_161442_create_order_items_table.php
│   │   ├── 2026_06_25_161500_create_payments_table.php
│   │   ├── 2026_06_25_161525_create_chat_histories_table.php
│   │   ├── 2026_06_25_161547_create_whatsapp_queues_table.php
│   │   ├── 2026_07_19_020902_create_temporary_media_table.php
│   │   ├── 2026_07_22_074555_create_order_status_histories_table.php
│   │   ├── 2026_07_22_081244_add_raw_notification_to_payments_table.php
│   │   ├── 2026_07_23_124506_create_order_cancel_requests_table.php
│   │   ├── 2026_07_23_125451_add_previous_status_to_order_cancel_requests_table.php
│   │   ├── 2026_07_23_130724_create_refunds_table.php
│   │   ├── 2026_07_23_133448_add_refund_number_to_refunds_table.php
│   │   ├── 2026_07_23_134635_create_shipments_table.php
│   │   ├── 2026_07_24_104236_add_tracking_token_to_orders_table.php
│   │   ├── 2026_07_24_162240_create_personal_access_tokens_table.php
│   │   ├── 2026_07_25_122836_add_last_tracking_sync_to_shipments_table.php
│   │   ├── 2026_08_02_085033_convert_refunds_to_uuid.php
│   │   ├── 2026_08_05_040722_add_profile_photo_to_admins_table.php
│   │   ├── 2026_08_05_063925_create_settings_table.php
│   │   ├── 2026_08_05_064049_create_hero_slides_table.php
│   │   ├── 2026_08_05_064113_create_promo_banners_table.php
│   │   ├── 2026_08_07_071624_make_phone_required_on_customers_table.php
│   │   ├── 2026_08_07_202618_add_customer_snapshot_to_orders_table.php
│   │   ├── 2026_08_11_012150_create_jobs_table.php
│   │   ├── 2026_08_12_044910_add_order_id_to_whatsapp_queues_table.php
│   │   ├── 2026_08_12_065547_add_attempts_and_sent_at_to_whatsapp_queues_table.php
│   │   ├── 2026_08_13_215123_create_shipping_couriers_table.php
│   │   ├── 2026_08_13_215218_create_shipping_rates_table.php
│   │   ├── 2026_08_17_185803_add_sku_to_products_table.php
│   │   ├── 2026_08_17_193721_add_product_sku_to_order_items_table.php
│   │   └── 2026_08_17_201818_remove_material_details_from_product_specifications_table.php
│   └── seeders
│       ├── AdminSeeder.php
│       ├── CategorySeeder.php
│       ├── DatabaseSeeder.php
│       ├── ProductSeeder.php
│       ├── SeriesSeeder.php
│       └── ShippingCourierSeeder.php
├── file for export document.md
├── form harga khusus Galang Citra Mitra Maju Mapan Pt MLG364.xlsx
├── hasil tinker test 5.md
├── HITUNG ONGKIR.xlsx
├── lihat_kolom.php
├── melody_db
├── melody_db.db
├── ngrok.exe
├── package-lock.json
├── package.json
├── phpunit.xml
├── postcss.config.js
├── progress.md
├── project_structure.md
├── public
│   ├── .htaccess
│   ├── build
│   │   ├── assets
│   │   │   ├── app-CxvFdCxN.css
│   │   │   └── app-YdM_3SCp.js
│   │   └── manifest.json
│   ├── favicon.ico
│   ├── hot
│   ├── index.php
│   └── robots.txt
├── README.md
├── resources
│   ├── css
│   │   ├── admin.css
│   │   └── app.css
│   ├── js
│   │   ├── admin
│   │   │   ├── category.js
│   │   │   ├── components
│   │   │   │   └── file-upload.js
│   │   │   ├── core
│   │   │   │   └── crud-base.js
│   │   │   ├── dashboard
│   │   │   │   └── index.js
│   │   │   ├── order
│   │   │   │   └── workflow.js
│   │   │   ├── product
│   │   │   │   └── media-manager.js
│   │   │   ├── product-form.js
│   │   │   ├── series.js
│   │   │   ├── settings
│   │   │   │   ├── branding.js
│   │   │   │   ├── hero.js
│   │   │   │   └── promo.js
│   │   │   └── shipping-rate.js
│   │   ├── app.js
│   │   ├── bootstrap.js
│   │   └── utils
│   └── views
│       ├── admin
│       │   ├── layouts
│       │   │   ├── app.blade.php
│       │   │   ├── navbar.blade.php
│       │   │   └── sidebar.blade.php
│       │   └── modules
│       │       ├── category
│       │       │   └── index.blade.php
│       │       ├── customer
│       │       │   ├── index.blade.php
│       │       │   └── show.blade.php
│       │       ├── dashboard
│       │       │   └── index.blade.php
│       │       ├── order
│       │       │   ├── index.blade.php
│       │       │   ├── partials
│       │       │   │   ├── actions.blade.php
│       │       │   │   ├── customer.blade.php
│       │       │   │   ├── items.blade.php
│       │       │   │   ├── payment.blade.php
│       │       │   │   ├── refund.blade.php
│       │       │   │   ├── shipping.blade.php
│       │       │   │   ├── summary.blade.php
│       │       │   │   └── timeline.blade.php
│       │       │   └── show.blade.php
│       │       ├── product
│       │       │   ├── create.blade.php
│       │       │   ├── edit.blade.php
│       │       │   ├── index.blade.php
│       │       │   └── _form.blade.php
│       │       ├── profile
│       │       │   └── index.blade.php
│       │       ├── series
│       │       │   └── index.blade.php
│       │       ├── settings
│       │       │   └── index.blade.php
│       │       ├── shipping
│       │       │   └── index.blade.php
│       │       ├── voucher
│       │       │   ├── create.blade.php
│       │       │   ├── index.blade.php
│       │       │   └── show.blade.php
│       │       └── whatsapp
│       │           └── index.blade.php
│       ├── auth
│       │   ├── confirm-password.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── reset-password.blade.php
│       │   └── verify-email.blade.php
│       ├── components
│       │   ├── admin
│       │   │   ├── alert
│       │   │   │   └── dialog.blade.php
│       │   │   ├── avatar.blade.php
│       │   │   ├── badge.blade.php
│       │   │   ├── button.blade.php
│       │   │   ├── card-body.blade.php
│       │   │   ├── card-footer.blade.php
│       │   │   ├── card-header.blade.php
│       │   │   ├── card.blade.php
│       │   │   ├── empty-state.blade.php
│       │   │   ├── feedback
│       │   │   │   ├── alert.blade.php
│       │   │   │   ├── confirm-dialog.blade.php
│       │   │   │   ├── loading-overlay.blade.php
│       │   │   │   ├── loading.blade.php
│       │   │   │   ├── skeleton.blade.php
│       │   │   │   └── toast.blade.php
│       │   │   ├── form
│       │   │   │   ├── checkbox.blade.php
│       │   │   │   ├── currency.blade.php
│       │   │   │   ├── file-upload.blade.php
│       │   │   │   ├── group.blade.php
│       │   │   │   ├── input.blade.php
│       │   │   │   ├── number.blade.php
│       │   │   │   ├── search-input.blade.php
│       │   │   │   ├── select.blade.php
│       │   │   │   ├── textarea.blade.php
│       │   │   │   ├── toggle.blade.php
│       │   │   │   └── validation-error.blade.php
│       │   │   ├── icon-button.blade.php
│       │   │   ├── modal
│       │   │   │   ├── body.blade.php
│       │   │   │   ├── footer.blade.php
│       │   │   │   ├── form.blade.php
│       │   │   │   ├── header.blade.php
│       │   │   │   └── modal.blade.php
│       │   │   ├── page-header.blade.php
│       │   │   ├── pagination
│       │   │   │   ├── links.blade.php
│       │   │   │   └── pagination.blade.php
│       │   │   ├── price.blade.php
│       │   │   ├── product
│       │   │   │   ├── gallery-item.blade.php
│       │   │   │   └── media-manager.blade.php
│       │   │   ├── sidebar-item.blade.php
│       │   │   ├── state
│       │   │   │   ├── empty.blade.php
│       │   │   │   ├── error.blade.php
│       │   │   │   └── loading.blade.php
│       │   │   ├── stats
│       │   │   │   ├── card.blade.php
│       │   │   │   └── grid.blade.php
│       │   │   ├── stepper
│       │   │   │   ├── index.blade.php
│       │   │   │   └── item.blade.php
│       │   │   ├── table
│       │   │   │   ├── actions.blade.php
│       │   │   │   ├── card.blade.php
│       │   │   │   ├── empty.blade.php
│       │   │   │   ├── pagination.blade.php
│       │   │   │   ├── table.blade.php
│       │   │   │   ├── tbody.blade.php
│       │   │   │   ├── td.blade.php
│       │   │   │   ├── th.blade.php
│       │   │   │   ├── thead.blade.php
│       │   │   │   ├── toolbar.blade.php
│       │   │   │   └── tr.blade.php
│       │   │   ├── test.blade.php
│       │   │   ├── toast
│       │   │   │   ├── container.blade.php
│       │   │   │   ├── flash.blade.php
│       │   │   │   └── toast.blade.php
│       │   │   └── wizard
│       │   │       ├── navigation.blade.php
│       │   │       ├── progress.blade.php
│       │   │       └── step.blade.php
│       │   ├── application-logo.blade.php
│       │   ├── auth-session-status.blade.php
│       │   ├── danger-button.blade.php
│       │   ├── dropdown-link.blade.php
│       │   ├── dropdown.blade.php
│       │   ├── input-error.blade.php
│       │   ├── input-label.blade.php
│       │   ├── modal.blade.php
│       │   ├── nav-link.blade.php
│       │   ├── primary-button.blade.php
│       │   ├── responsive-nav-link.blade.php
│       │   ├── secondary-button.blade.php
│       │   └── text-input.blade.php
│       ├── dashboard.blade.php
│       ├── documents
│       │   ├── invoice
│       │   │   └── order.blade.php
│       │   └── packing-label
│       │       └── order.blade.php
│       ├── layouts
│       │   ├── app.blade.php
│       │   ├── guest.blade.php
│       │   └── navigation.blade.php
│       ├── profile
│       │   ├── edit.blade.php
│       │   └── partials
│       │       ├── delete-user-form.blade.php
│       │       ├── update-password-form.blade.php
│       │       └── update-profile-information-form.blade.php
│       └── welcome.blade.php
├── routes
│   ├── admin.php
│   ├── api.php
│   ├── auth.php
│   ├── console.php
│   └── web.php
├── tailwind.config.js
├── tests
│   ├── Feature
│   │   ├── Admin
│   │   │   └── Whatsapp
│   │   │       └── WhatsappQueueEndpointTest.php
│   │   ├── Auth
│   │   │   ├── AuthenticationTest.php
│   │   │   ├── EmailVerificationTest.php
│   │   │   ├── PasswordConfirmationTest.php
│   │   │   ├── PasswordResetTest.php
│   │   │   ├── PasswordUpdateTest.php
│   │   │   └── RegistrationTest.php
│   │   ├── ExampleTest.php
│   │   ├── Order
│   │   │   ├── OrderWhatsappNotificationTest.php
│   │   │   ├── OrderWhatsappQueueDispatchTest.php
│   │   │   └── OrderWorkflowWhatsappNotificationTest.php
│   │   └── ProfileTest.php
│   ├── TestCase.php
│   └── Unit
│       ├── ExampleTest.php
│       ├── Jobs
│       │   └── SendWhatsappMessageTest.php
│       └── Services
│           ├── Order
│           │   └── OrderVoucherTest.php
│           ├── Payment
│           │   └── RefundServiceTest.php
│           ├── Voucher
│           │   └── VoucherServiceTest.php
│           └── Whatsapp
│               ├── WhatsappMessageTemplateServiceTest.php
│               ├── WhatsappNotificationServiceTest.php
│               └── WhatsappSenderServiceTest.php
├── tree.cjs
├── vite.config.js
└── waha
```
