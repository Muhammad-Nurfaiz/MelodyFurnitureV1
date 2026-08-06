import './bootstrap';

import Alpine from 'alpinejs';

/*
|--------------------------------------------------------------------------
| Core
|--------------------------------------------------------------------------
*/
import './admin/core/crud-base';

/*
|--------------------------------------------------------------------------
| Modules
|--------------------------------------------------------------------------
*/
import './admin/category';
import './admin/series';
import './admin/product-form';
import './admin/order/workflow';

/*
|--------------------------------------------------------------------------
| Product Media Manager
|--------------------------------------------------------------------------
*/
import mediaManager from './admin/product/media-manager';
import heroCrud from './admin/settings/hero';
import brandingCrud from "./admin/settings/branding";
import fileUpload from "./admin/components/file-upload";
import promoCrud from "./admin/settings/promo";

/*
|--------------------------------------------------------------------------
| Alpine
|--------------------------------------------------------------------------
*/
window.Alpine = Alpine;
Alpine.data('mediaManager', mediaManager);
Alpine.data('heroCrud', heroCrud);
Alpine.data("brandingCrud", brandingCrud);
Alpine.data("fileUpload", fileUpload);
Alpine.data("promoCrud", promoCrud);
Alpine.start();