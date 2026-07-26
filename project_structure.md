# Project Structure

Berikut adalah struktur folder dan file di project Anda (folder \
ode_modules\, \endor\, \storage\, dan \.git\ disembunyikan untuk kerapian):

```text
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── app
│   ├── Console
│   │   └── Commands
│   │       ├── CleanupTemporaryMedia.php
│   │       └── ExpirePendingPaymentsCommand.php
│   ├── Enums
│   │   ├── OrderStatus.php
│   │   ├── PaymentStatus.php
│   │   ├── Status.php
│   │   ├── UserRole.php
│   │   └── VoucherStatus.php
│   ├── Helpers
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Admin
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── Category
│   │   │   │   │   └── CategoryController.php
│   │   │   │   ├── Customer
│   │   │   │   ├── Dashboard
│   │   │   │   │   └── DashboardController.php
│   │   │   │   ├── Media
│   │   │   │   │   └── TemporaryMediaController.php
│   │   │   │   ├── Order
│   │   │   │   │   └── OrderController.php
│   │   │   │   ├── Product
│   │   │   │   │   ├── ProductController.php
│   │   │   │   │   └── ProductMediaController.php
│   │   │   │   ├── Series
│   │   │   │   │   └── SeriesController.php
│   │   │   │   ├── Shipment
│   │   │   │   │   └── ShipmentController.php
│   │   │   │   └── Voucher
│   │   │   ├── Api
│   │   │   │   ├── CheckoutController.php
│   │   │   │   ├── Customer
│   │   │   │   │   └── CustomerOrderController.php
│   │   │   │   └── MidtransWebhookController.php
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
│   │   │   ├── Checkout
│   │   │   │   └── CheckoutController.php
│   │   │   ├── Controller.php
│   │   │   ├── Payment
│   │   │   │   └── NotificationController.php
│   │   │   └── ProfileController.php
│   │   ├── Requests
│   │   │   ├── Admin
│   │   │   │   ├── Category
│   │   │   │   │   ├── StoreCategoryRequest.php
│   │   │   │   │   └── UpdateCategoryRequest.php
│   │   │   │   ├── Customer
│   │   │   │   ├── Order
│   │   │   │   ├── Product
│   │   │   │   │   ├── StoreProductRequest.php
│   │   │   │   │   └── UpdateProductRequest.php
│   │   │   │   ├── Series
│   │   │   │   │   ├── StoreSeriesRequest.php
│   │   │   │   │   └── UpdateSeriesRequest.php
│   │   │   │   └── Voucher
│   │   │   ├── Api
│   │   │   │   └── CheckoutRequest.php
│   │   │   ├── Auth
│   │   │   │   └── LoginRequest.php
│   │   │   ├── CheckoutRequest.php
│   │   │   ├── Customer
│   │   │   │   └── CancellationRequest.php
│   │   │   └── ProfileUpdateRequest.php
│   │   └── Resources
│   │       ├── Customer
│   │       │   ├── CancellationRequestResource.php
│   │       │   ├── OrderItemResource.php
│   │       │   ├── OrderStatusHistoryResource.php
│   │       │   ├── OrderTrackingResource.php
│   │       │   ├── OrderTrackingResponseResource.php
│   │       │   └── PaymentInformationResource.php
│   │       ├── OrderItemResource.php
│   │       ├── OrderResource.php
│   │       └── PaymentResource.php
│   ├── Models
│   │   ├── Admin.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Category.php
│   │   ├── ChatHistory.php
│   │   ├── Customer.php
│   │   ├── Order.php
│   │   ├── OrderCancelRequest.php
│   │   ├── OrderItem.php
│   │   ├── OrderStatusHistory.php
│   │   ├── Payment.php
│   │   ├── Product.php
│   │   ├── ProductMedia.php
│   │   ├── ProductSpecification.php
│   │   ├── Refund.php
│   │   ├── Series.php
│   │   ├── Shipment.php
│   │   ├── TemporaryMedia.php
│   │   ├── Voucher.php
│   │   └── WhatsappQueue.php
│   ├── Policies
│   │   ├── CategoryPolicy.php
│   │   ├── ProductPolicy.php
│   │   └── SeriesPolicy.php
│   ├── Providers
│   │   └── AppServiceProvider.php
│   ├── Services
│   │   ├── Category
│   │   │   └── CategoryService.php
│   │   ├── Customer
│   │   │   ├── CustomerCancellationService.php
│   │   │   ├── CustomerOrderService.php
│   │   │   ├── CustomerPaymentService.php
│   │   │   └── CustomerTrackingService.php
│   │   ├── Inventory
│   │   │   └── ProductInventoryService.php
│   │   ├── Media
│   │   │   └── TemporaryMediaService.php
│   │   ├── Order
│   │   │   ├── OrderCalculatorService.php
│   │   │   ├── OrderCancellationService.php
│   │   │   ├── OrderFulfillmentService.php
│   │   │   ├── OrderNumberService.php
│   │   │   ├── OrderQueryService.php
│   │   │   ├── OrderService.php
│   │   │   ├── OrderTimelineService.php
│   │   │   ├── OrderTrackingTokenService.php
│   │   │   └── OrderWorkflowService.php
│   │   ├── Payment
│   │   │   ├── MidtransPayloadBuilder.php
│   │   │   ├── MidtransService.php
│   │   │   ├── MidtransWebhookService.php
│   │   │   ├── PaymentExpirationService.php
│   │   │   ├── PaymentService.php
│   │   │   ├── RefundNumberService.php
│   │   │   └── RefundService.php
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
│   │   │   └── ShippingService.php
│   │   └── Voucher
│   │       └── VoucherService.php
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
│   │   ├── packages.php
│   │   └── services.php
│   └── providers.php
├── chat.md
├── chat.txt
├── composer.json
├── composer.lock
├── config
│   ├── admin-menu.php
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── midtrans.php
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
│   │   └── 2026_07_25_122836_add_last_tracking_sync_to_shipments_table.php
│   └── seeders
│       ├── AdminSeeder.php
│       ├── CategorySeeder.php
│       ├── DatabaseSeeder.php
│       ├── DemoOrderTrackingSeeder.php
│       └── SeriesSeeder.php
├── lihat_kolom.php
├── melody_db
├── melody_db.db
├── package-lock.json
├── package.json
├── phpunit.xml
├── postcss.config.js
├── project_structure.md
├── public
│   ├── .htaccess
│   ├── build
│   │   ├── assets
│   │   │   ├── app-BfpX1doZ.js
│   │   │   └── app-Dq_P0PcW.css
│   │   └── manifest.json
│   ├── favicon.ico
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
│   │   │   ├── core
│   │   │   │   └── crud-base.js
│   │   │   ├── product
│   │   │   │   └── media-manager.js
│   │   │   ├── product-form.js
│   │   │   └── series.js
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
│       │       ├── dashboard
│       │       │   └── index.blade.php
│       │       ├── order
│       │       │   └── index.blade.php
│       │       ├── product
│       │       │   ├── create.blade.php
│       │       │   ├── edit.blade.php
│       │       │   ├── index.blade.php
│       │       │   ├── steps
│       │       │   │   ├── information.blade.php
│       │       │   │   ├── media.blade.php
│       │       │   │   ├── pricing.blade.php
│       │       │   │   └── specification.blade.php
│       │       │   └── _form.blade.php
│       │       ├── series
│       │       │   └── index.blade.php
│       │       └── voucher
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
│   │   ├── Auth
│   │   │   ├── AuthenticationTest.php
│   │   │   ├── EmailVerificationTest.php
│   │   │   ├── PasswordConfirmationTest.php
│   │   │   ├── PasswordResetTest.php
│   │   │   ├── PasswordUpdateTest.php
│   │   │   └── RegistrationTest.php
│   │   ├── ExampleTest.php
│   │   └── ProfileTest.php
│   ├── TestCase.php
│   └── Unit
│       └── ExampleTest.php
├── tree.cjs
└── vite.config.js
```
