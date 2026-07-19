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
│   │   │   │   ├── Order
│   │   │   │   │   └── OrderController.php
│   │   │   │   ├── Product
│   │   │   │   │   └── ProductController.php
│   │   │   │   ├── Series
│   │   │   │   └── Voucher
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
│   │   └── Requests
│   │       ├── Admin
│   │       │   ├── Category
│   │       │   │   ├── StoreCategoryRequest.php
│   │       │   │   └── UpdateCategoryRequest.php
│   │       │   ├── Customer
│   │       │   ├── Order
│   │       │   ├── Product
│   │       │   ├── Series
│   │       │   └── Voucher
│   │       ├── Auth
│   │       │   └── LoginRequest.php
│   │       └── ProfileUpdateRequest.php
│   ├── Models
│   │   ├── Admin.php
│   │   ├── Cart.php
│   │   ├── CartItem.php
│   │   ├── Category.php
│   │   ├── ChatHistory.php
│   │   ├── Customer.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Payment.php
│   │   ├── Product.php
│   │   ├── ProductMedia.php
│   │   ├── ProductSpecification.php
│   │   ├── Series.php
│   │   ├── Voucher.php
│   │   └── WhatsappQueue.php
│   ├── Policies
│   │   └── CategoryPolicy.php
│   ├── Providers
│   │   └── AppServiceProvider.php
│   ├── Services
│   │   └── Category
│   │       └── CategoryService.php
│   ├── Traits
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
│   ├── queue.php
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
│   │   └── 2026_06_25_161547_create_whatsapp_queues_table.php
│   └── seeders
│       ├── AdminSeeder.php
│       ├── CategorySeeder.php
│       ├── DatabaseSeeder.php
│       └── SeriesSeeder.php
├── melody_db
├── package-lock.json
├── package.json
├── phpunit.xml
├── postcss.config.js
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
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views
│       ├── admin
│       │   ├── components
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
│       │   │   │   ├── file-upload.blade.php
│       │   │   │   ├── group.blade.php
│       │   │   │   ├── input.blade.php
│       │   │   │   ├── search-input.blade.php
│       │   │   │   ├── select.blade.php
│       │   │   │   ├── textarea.blade.php
│       │   │   │   ├── toggle.blade.php
│       │   │   │   └── validation-error.blade.php
│       │   │   ├── icon-button.blade.php
│       │   │   ├── modal
│       │   │   │   ├── body.blade.php
│       │   │   │   ├── footer.blade.php
│       │   │   │   ├── header.blade.php
│       │   │   │   └── modal.blade.php
│       │   │   ├── page-header.blade.php
│       │   │   ├── pagination
│       │   │   │   └── pagination.blade.php
│       │   │   ├── sidebar-item.blade.php
│       │   │   ├── state
│       │   │   │   ├── empty.blade.php
│       │   │   │   ├── error.blade.php
│       │   │   │   └── loading.blade.php
│       │   │   ├── stats
│       │   │   │   ├── card.blade.php
│       │   │   │   └── grid.blade.php
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
│       │   │   └── toast
│       │   │       ├── container.blade.php
│       │   │       ├── flash.blade.php
│       │   │       └── toast.blade.php
│       │   ├── layouts
│       │   │   ├── app.blade.php
│       │   │   ├── navbar.blade.php
│       │   │   └── sidebar.blade.php
│       │   └── modules
│       │       ├── category
│       │       │   ├── create.blade.php
│       │       │   ├── edit.blade.php
│       │       │   ├── index.blade.php
│       │       │   └── _form.blade.php
│       │       ├── customer
│       │       ├── dashboard
│       │       │   └── index.blade.php
│       │       ├── order
│       │       ├── product
│       │       ├── series
│       │       └── voucher
│       ├── auth
│       │   ├── confirm-password.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   ├── reset-password.blade.php
│       │   └── verify-email.blade.php
│       ├── components
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
└── vite.config.js
```
