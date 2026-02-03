<?php

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Page\PageController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\FileManagerController;
use App\Http\Controllers\Api\LayoutPresetController;
use App\Http\Controllers\Api\WebsiteSettingController;
use App\Http\Controllers\Api\ArticleCategoryController;
use App\Http\Controllers\Api\Page\PublicPageController;
use App\Http\Controllers\Api\PermissionMatrixController;


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AccountController::class, 'me']);
    Route::post('/user/profile', [AccountController::class, 'updateProfile']);
    Route::put('/user/email', [AccountController::class, 'updateEmail']);
    Route::put('/user/password', [AccountController::class, 'updatePassword']);

    // dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // pages
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::get('/pages/{id}', [PageController::class, 'show']);
    Route::put('/pages/{id}', [PageController::class, 'update']);
    Route::patch('/pages/{id}', [PageController::class, 'update']);
    Route::delete('/pages/{id}', [PageController::class, 'destroy']);
    Route::post('/pages/restore', [PageController::class, 'restore']);
    Route::get('/pages-menu', [PageController::class, 'pages_menu']);

    // albums
    Route::apiResource('albums', AlbumController::class);

    // fetch animations
    Route::get('/options', [OptionController::class, 'index']);

    // menus
    Route::apiResource('menus', MenuController::class);
    Route::patch('/menus/{menu}/activate', [MenuController::class, 'setActive']);
    Route::post('/menus/restore', [MenuController::class, 'restore']);
    Route::post('/menus/{id}/restore', [MenuController::class, 'restoreById']);

    // file manager
    Route::prefix('filemanager')->group(function () {
        Route::get('/', [FileManagerController::class, 'index']);
        Route::post('/upload', [FileManagerController::class, 'upload']);
        Route::post('/folder', [FileManagerController::class, 'createFolder']);
        Route::delete('/', [FileManagerController::class, 'delete']);
    });

    // article categories
    Route::get('/article-categories', [ArticleCategoryController::class, 'index']);
    Route::post('/article-categories', [ArticleCategoryController::class, 'store']);
    Route::get('/article-categories/{category}', [ArticleCategoryController::class, 'show']);
    Route::put('/article-categories/{category}', [ArticleCategoryController::class, 'update']);

    // Compatibility endpoint used by some frontends
    // e.g. GET /api/categories?type=product
    Route::get('/categories', function (Request $request) {
        $type = $request->input('type');

        if ($type === 'product') {
            return app(ProductCategoryController::class)->index($request);
        }

        if ($type === 'article') {
            return app(ArticleCategoryController::class)->index($request);
        }

        return response()->json([
            'message' => 'Unknown category type',
            'allowed' => ['product', 'article'],
        ], 404);
    });

    // articles
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/fetch-article-categories', [ArticleController::class, 'fetch_categories']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    Route::post('/articles/restore', [ArticleController::class, 'restore']);
    Route::post('/articles/{id}/restore', [ArticleController::class, 'restoreById']);

    // products
    Route::get('/product-categories', [ProductCategoryController::class, 'index']);
    Route::get('/fetch-product-categories', [ProductCategoryController::class, 'index']);
    Route::post('/product-categories', [ProductCategoryController::class, 'store']);
    Route::get('/product-categories/{category}', [ProductCategoryController::class, 'show']);
    Route::put('/product-categories/{category}', [ProductCategoryController::class, 'update']);
    Route::patch('/product-categories/{category}', [ProductCategoryController::class, 'update']);
    Route::delete('/product-categories/{category}', [ProductCategoryController::class, 'destroy']);
    Route::post('/product-categories/restore', [ProductCategoryController::class, 'restore']);
    Route::post('/product-categories/{id}/restore', [ProductCategoryController::class, 'restoreById']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/restore', [ProductController::class, 'restore']);
    Route::post('/products/{id}/restore', [ProductController::class, 'restoreById']);

    // users
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/fetch_roles', [UserController::class, 'fetch_roles']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);

    // audit
    Route::get('/audit-trails', [AuditTrailController::class, 'index']);

    // website settings
    Route::get('/website-settings', [WebsiteSettingController::class, 'show']);

    Route::post('/website-settings/website', [WebsiteSettingController::class, 'updateWebsite']);
    Route::post('/website-settings/contact', [WebsiteSettingController::class, 'updateContact']);
    Route::post('/website-settings/privacy', [WebsiteSettingController::class, 'updatePrivacy']);

    Route::get('/website-settings/social', [WebsiteSettingController::class, 'getSocials']);
    Route::post('/website-settings/social', [WebsiteSettingController::class, 'updateSocials']);

    // roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);

    // access right
    Route::get('/permissions/matrix', [PermissionMatrixController::class, 'index']);
    Route::post('/permissions/sync', [PermissionMatrixController::class, 'sync']);

    // ai assistant
    Route::post('/ai/page-assistant', [AiAssistantController::class, 'generate']);

    // layout presets
    Route::prefix('layout-presets')->group(function () {
        Route::get('/', [LayoutPresetController::class, 'index']);
        Route::post('/', [LayoutPresetController::class, 'store']);
        Route::put('/{id}', [LayoutPresetController::class, 'update']);
        Route::delete('/{id}', [LayoutPresetController::class, 'destroy']);
    });

});

//public
Route::get('/public/pages/{slug}', [PublicPageController::class, 'show']);
Route::get('/public/menus/active', [PublicPageController::class, 'active']);
Route::get('/public/footer', [PublicPageController::class, 'footer']);

// Public search routes
// Back-compat aliases for frontend helpers expecting /search and /public/search
Route::get('/search', [SearchController::class, 'quickSearch']);
Route::get('/public/search', [SearchController::class, 'quickSearch']);

Route::get('/search/pages', [SearchController::class, 'searchPages']);
Route::get('/search/global', [SearchController::class, 'globalSearch']);
Route::get('/search/quick', [SearchController::class, 'quickSearch']);

Route::prefix('public-articles')->group(function () {
    Route::get('/', [PublicPageController::class, 'public_articles']);
    Route::get('/{slug}', [PublicPageController::class, 'public_articles_show']);
});
Route::get('/public-article-categories', [PublicPageController::class, 'public_article_categories']);
Route::get('/public-articles-archive', [PublicPageController::class, 'archive']);

Route::post('/contact', [PublicPageController::class, 'send']);
