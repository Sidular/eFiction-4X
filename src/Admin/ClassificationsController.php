<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;
use eFiction\Models\Category;

class ClassificationsController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        return $this->render('admin/classifications', [
            'classifications' => (new Category($this->db()))->allClassifications(),
            'csrf' => $this->csrf(),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/classifications');
        }

        if (!empty($_POST['name']) && !empty($_POST['type'])) {
            $this->db()->insert('classifications', [
                'name' => trim($_POST['name']),
                'type' => trim($_POST['type']),
                'description' => trim($_POST['description'] ?? ''),
                'displayorder' => (int) ($_POST['displayorder'] ?? 0),
            ]);
        }
        $this->flash('success', 'Classification saved.');
        $this->redirect('/admin/classifications');
    }
}
