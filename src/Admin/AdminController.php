<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;
use eFiction\Models\Story;
use eFiction\Models\Author;

class AdminController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $db = $this->db();
        return $this->render('admin/dashboard', [
            'storyCount' => $db->count('stories', 'validated = 1'),
            'pendingStories' => $db->count('stories', 'validated = 0'),
            'authorCount' => $db->count('authors'),
            'reviewCount' => $db->count('reviews', 'validated = 1'),
            'pendingReviews' => $db->count('reviews', 'validated = 0'),
            'recentStories' => (new Story($db))->latest(5),
            'recentMembers' => (new Author($db))->recent(5),
        ]);
    }

    public function legacyIndex(): void
    {
        $this->redirect('/admin');
    }
}
