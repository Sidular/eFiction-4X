<?php

declare(strict_types=1);

use eFiction\App;
use eFiction\Router;
use eFiction\Controllers\HomeController;
use eFiction\Controllers\BrowseController;
use eFiction\Controllers\StoryController;
use eFiction\Controllers\SeriesController;
use eFiction\Controllers\UserController;
use eFiction\Controllers\ReviewController;
use eFiction\Controllers\SearchController;
use eFiction\Controllers\ContactController;
use eFiction\Controllers\RssController;
use eFiction\Admin\AdminController;
use eFiction\Admin\SettingsController;
use eFiction\Admin\MembersController;
use eFiction\Admin\StoriesController;
use eFiction\Admin\CategoriesController;
use eFiction\Admin\ClassificationsController;
use eFiction\Admin\ValidationsController;
use eFiction\Admin\SkinsController;
use eFiction\Admin\BlocksController;

/** @var App $app */
$router = $app->get(Router::class);

$router->middleware(function (App $app) {
    $session = $app->get(\eFiction\Session::class);
    $session->start();
    $auth = $app->get(\eFiction\Auth::class);
    $auth->user(); // warm session user
    $cfg = $app->get(\eFiction\Config::class);
    if ($cfg->get('site.maintenance') && !($auth->user()['level'] ?? 0)) {
        http_response_code(503);
        echo $app->get(\eFiction\Template::class)->render('maintenance');
        return false;
    }
    return true;
});

// Public routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/browse', [BrowseController::class, 'index']);
$router->get('/browse/{type}', [BrowseController::class, 'type']);
$router->get('/story/{id}', [StoryController::class, 'view']);
$router->get('/story/{id}/chapter/{chapter}', [StoryController::class, 'chapter']);
$router->get('/series/{id}', [SeriesController::class, 'view']);
$router->get('/user/{id}', [UserController::class, 'profile']);
$router->get('/user/{action}', [UserController::class, 'action']);
$router->post('/user/{action}', [UserController::class, 'action']);
$router->get('/reviews', [ReviewController::class, 'index']);
$router->post('/reviews/add', [ReviewController::class, 'add']);
$router->get('/search', [SearchController::class, 'index']);
$router->post('/search', [SearchController::class, 'search']);
$router->get('/contact', [ContactController::class, 'index']);
$router->post('/contact', [ContactController::class, 'send']);
$router->get('/rss', [RssController::class, 'index']);
$router->get('/rss/{type}', [RssController::class, 'feed']);

// Legacy entry-point redirects
$router->get('/viewstory.php', [StoryController::class, 'legacyView']);
$router->get('/viewseries.php', [SeriesController::class, 'legacyView']);
$router->get('/viewuser.php', [UserController::class, 'legacyView']);
$router->get('/browse.php', [BrowseController::class, 'legacyIndex']);
$router->get('/user.php', [UserController::class, 'legacyAction']);
$router->get('/admin.php', [AdminController::class, 'legacyIndex']);
$router->get('/search.php', [SearchController::class, 'legacyIndex']);
$router->get('/contact.php', [ContactController::class, 'legacyIndex']);
$router->get('/rss.php', [RssController::class, 'legacyFeed']);

// Admin routes
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings', [SettingsController::class, 'save']);
$router->get('/admin/members', [MembersController::class, 'index']);
$router->get('/admin/members/{id}', [MembersController::class, 'edit']);
$router->post('/admin/members/{id}', [MembersController::class, 'save']);
$router->get('/admin/stories', [StoriesController::class, 'index']);
$router->get('/admin/stories/{id}', [StoriesController::class, 'edit']);
$router->post('/admin/stories/{id}', [StoriesController::class, 'save']);
$router->get('/admin/categories', [CategoriesController::class, 'index']);
$router->post('/admin/categories', [CategoriesController::class, 'save']);
$router->get('/admin/classifications', [ClassificationsController::class, 'index']);
$router->post('/admin/classifications', [ClassificationsController::class, 'save']);
$router->get('/admin/validations', [ValidationsController::class, 'index']);
$router->post('/admin/validations/{id}', [ValidationsController::class, 'process']);
$router->get('/admin/skins', [SkinsController::class, 'index']);
$router->post('/admin/skins', [SkinsController::class, 'save']);
$router->get('/admin/blocks', [BlocksController::class, 'index']);
$router->post('/admin/blocks', [BlocksController::class, 'save']);
