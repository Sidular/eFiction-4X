<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;

class BlocksController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $blocks = $this->db()->fetchAll('SELECT * FROM ' . $this->db()->table('blocks') . ' ORDER BY displayorder');
        return $this->render('admin/blocks', ['blocks' => $blocks, 'csrf' => $this->csrf()]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/blocks');
        }
        $this->db()->update('blocks', [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'displayorder' => (int) ($_POST['displayorder'] ?? 0),
        ], 'blockid = :id', ['id' => (int) ($_POST['blockid'] ?? 0)]);
        $this->flash('success', 'Block updated.');
        $this->redirect('/admin/blocks');
    }
}
