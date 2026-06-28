<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;
use eFiction\Models\Story;

class StoriesController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $stories = $this->db()->fetchAll(
            'SELECT s.sid, s.title, s.validated, s.completed, a.penname, s.date
             FROM ' . $this->db()->table('stories') . ' s
             LEFT JOIN ' . $this->db()->table('authors') . ' a ON a.uid = s.uid
             ORDER BY s.date DESC'
        );
        return $this->render('admin/stories', ['stories' => $stories, 'csrf' => $this->csrf()]);
    }

    public function edit(int $id): string
    {
        $this->requireAdmin();
        $story = (new Story($this->db()))->find($id, true);
        if (!$story) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Story not found.']);
        }
        return $this->render('admin/story_edit', [
            'story' => $story,
            'chapters' => (new Story($this->db()))->findChapters($id, true),
            'csrf' => $this->csrf(),
        ]);
    }

    public function save(int $id): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/stories/' . $id);
        }

        $update = [
            'title' => trim($_POST['title'] ?? ''),
            'summary' => trim($_POST['summary'] ?? ''),
            'validated' => (int) ($_POST['validated'] ?? 0),
            'completed' => (int) ($_POST['completed'] ?? 0),
            'featured' => (int) ($_POST['featured'] ?? 0),
            'comments' => (int) ($_POST['comments'] ?? 1),
        ];
        $this->db()->update('stories', $update, 'sid = :sid', ['sid' => $id]);
        $this->flash('success', 'Story updated.');
        $this->redirect('/admin/stories');
    }
}
