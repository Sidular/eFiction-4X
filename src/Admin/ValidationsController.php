<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;

class ValidationsController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $db = $this->db();
        $stories = $db->fetchAll(
            'SELECT s.sid, s.title, s.date, a.penname
             FROM ' . $db->table('stories') . ' s
             LEFT JOIN ' . $db->table('authors') . ' a ON a.uid = s.uid
             WHERE s.validated = 0
             ORDER BY s.date DESC'
        );
        $chapters = $db->fetchAll(
            'SELECT c.chapid, c.title, c.date, s.title AS story_title, a.penname
             FROM ' . $db->table('chapters') . ' c
             JOIN ' . $db->table('stories') . ' s ON s.sid = c.sid
             LEFT JOIN ' . $db->table('authors') . ' a ON a.uid = c.uid
             WHERE c.validated = 0
             ORDER BY c.date DESC'
        );
        return $this->render('admin/validations', [
            'stories' => $stories,
            'chapters' => $chapters,
            'csrf' => $this->csrf(),
        ]);
    }

    public function process(int $id): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/validations');
        }

        $type = $_POST['type'] ?? 'story';
        $action = $_POST['action'] ?? 'approve';
        $table = $type === 'chapter' ? 'chapters' : 'stories';
        $column = $type === 'chapter' ? 'chapid' : 'sid';

        if ($action === 'approve') {
            $this->db()->update($table, ['validated' => 1], "{$column} = :id", ['id' => $id]);
            $this->flash('success', 'Approved.');
        } else {
            $this->db()->delete($table, "{$column} = :id", ['id' => $id]);
            $this->flash('success', 'Deleted.');
        }
        $this->redirect('/admin/validations');
    }
}
