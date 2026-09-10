<?php
declare(strict_types=1);

use App\Controllers\Account\DashboardController as AccountDashboard;
use App\Controllers\Account\LicenseController as AccountLicense;
use App\Controllers\Account\PaymentController as AccountPayment;
use App\Controllers\Account\RenewalController;
use App\Controllers\Admin\ActivityController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\DashboardController as AdminDashboard;
use App\Controllers\Admin\LicenseController as AdminLicense;
use App\Controllers\Admin\MessageController;
use App\Controllers\Admin\PaymentController as AdminPayment;
use App\Controllers\Admin\PlanController;
use App\Controllers\Admin\PostCategoryController;
use App\Controllers\Admin\PostController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Site\CheckoutController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\LanguageController;
use App\Controllers\Site\PostController as SitePostController;

// ─── Public marketing site ───────────────────────────────────
$router->get('/', HomeController::class, 'index');
$router->get('/products/{slug}', HomeController::class, 'product');
$router->get('/pricing', HomeController::class, 'pricing');
$router->get('/features', HomeController::class, 'features');
$router->get('/about', HomeController::class, 'about');
$router->get('/contact', HomeController::class, 'contact');
$router->post('/contact', HomeController::class, 'storeMessage');

// ─── Blog ────────────────────────────────────────────────────
$router->get('/blog', SitePostController::class, 'index');
$router->get('/blog/category/{slug}', SitePostController::class, 'category');
$router->get('/blog/{slug}', SitePostController::class, 'show');

// ─── Language switcher ───────────────────────────────────────
$router->get('/language/geo', LanguageController::class, 'geo');
$router->get('/language/{locale}', LanguageController::class, 'switch');

// ─── Checkout / purchase ─────────────────────────────────────
$router->get('/checkout', CheckoutController::class, 'index');
$router->post('/checkout', CheckoutController::class, 'process');

// ─── Auth ────────────────────────────────────────────────────
$router->get('/register', RegisterController::class, 'show');
$router->post('/register', RegisterController::class, 'store');
$router->get('/login', LoginController::class, 'show');
$router->post('/login', LoginController::class, 'store');
$router->get('/logout', LogoutController::class, 'logout');

// ─── Customer account ────────────────────────────────────────
$router->group('/account', function (App\Core\Router $router): void {
    $router->get('', AccountDashboard::class, 'index');
    $router->get('/licenses', AccountLicense::class, 'index');
    $router->get('/licenses/{id}', AccountLicense::class, 'show');
    $router->post('/licenses/{id}/renew', RenewalController::class, 'renew');
    $router->post('/activations/revoke', AccountLicense::class, 'revoke');
    $router->get('/payments', AccountPayment::class, 'index');
}, ['AuthMiddleware']);

// ─── Admin backend ───────────────────────────────────────────
$router->group('/admin', function (App\Core\Router $router): void {
    $router->get('', AdminDashboard::class, 'index');
    $router->get('/dashboard', AdminDashboard::class, 'index');

    $router->get('/customers', CustomerController::class, 'index');
    $router->get('/customers/{id}', CustomerController::class, 'show');
    $router->post('/customers/{id}', CustomerController::class, 'update');

    $router->get('/plans', PlanController::class, 'index');
    $router->get('/plans/create', PlanController::class, 'create');
    $router->post('/plans', PlanController::class, 'store');
    $router->get('/plans/{id}/edit', PlanController::class, 'edit');
    $router->post('/plans/{id}', PlanController::class, 'update');

    $router->get('/licenses', AdminLicense::class, 'index');
    $router->get('/licenses/create', AdminLicense::class, 'create');
    $router->post('/licenses', AdminLicense::class, 'store');
    $router->get('/licenses/{id}', AdminLicense::class, 'show');
    $router->post('/licenses/{id}/status', AdminLicense::class, 'updateStatus');
    $router->post('/licenses/{id}/extend', AdminLicense::class, 'extend');

    $router->get('/payments', AdminPayment::class, 'index');
    $router->post('/payments/{id}', AdminPayment::class, 'updateStatus');

    $router->get('/posts', PostController::class, 'index');
    $router->get('/posts/create', PostController::class, 'create');
    $router->post('/posts', PostController::class, 'store');
    $router->get('/posts/{id}/edit', PostController::class, 'edit');
    $router->post('/posts/{id}', PostController::class, 'update');
    $router->post('/posts/{id}/delete', PostController::class, 'delete');

    $router->get('/post-categories', PostCategoryController::class, 'index');
    $router->get('/post-categories/create', PostCategoryController::class, 'create');
    $router->post('/post-categories', PostCategoryController::class, 'store');
    $router->get('/post-categories/{id}/edit', PostCategoryController::class, 'edit');
    $router->post('/post-categories/{id}', PostCategoryController::class, 'update');
    $router->post('/post-categories/{id}/delete', PostCategoryController::class, 'delete');

    $router->get('/messages', MessageController::class, 'index');
    $router->post('/messages/{id}/read', MessageController::class, 'markRead');

    $router->get('/activities', ActivityController::class, 'index');
}, ['AdminMiddleware']);