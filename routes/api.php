<?php

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Page\PageController;
use App\Http\Controllers\Api\FileManagerController;
use App\Http\Controllers\Api\ArticleCategoryController;
use App\Http\Controllers\Api\Page\PublicPageController;


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {   return $request->user();    });
    
    // dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // pages
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::get('/pages/{id}', [PageController::class, 'show']);
    Route::put('/pages/{id}', [PageController::class, 'update']);
    Route::get('/pages-menu', [PageController::class, 'pages_menu']);

    // albums
    Route::apiResource('albums', AlbumController::class);

    // fetch animations
    Route::get('/options', [OptionController::class, 'index']);

    // menus
    Route::apiResource('menus', MenuController::class);
    Route::patch('/menus/{menu}/activate', [MenuController::class, 'setActive']);

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

    // articles
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/fetch-article-categories', [ArticleController::class, 'fetch_categories']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);

    // users
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/fetch_roles', [UserController::class, 'fetch_roles']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
});

//public
Route::get('/public/pages/{slug}', [PublicPageController::class, 'show']);
Route::get('/public/menus/active', [PublicPageController::class, 'active']);
Route::get('/public/footer', [PublicPageController::class, 'footer']);