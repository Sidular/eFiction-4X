<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;
use eFiction\Models\Category;

class CategoriesController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        return $this->render('admin/categories', [
            'categories' => (new Category($this->db()))->tree(),
            'csrf' => $this->csrf(),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/categories');
        }

        if (!empty($_POST['category'])) {
            $this->db()->insert('categories', [
                'category' => trim($_POST['category']),
                'parentcatid' => (int) ($_POST['parentcatid'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'displayorder' => (int) ($_POST['displayorder'] ?? 0),
            ]);
        }
        $this->flash('success', 'Category saved.');
        $this->redirect('/admin/categories');
    }
}
