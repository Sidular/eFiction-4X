<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Story;
use eFiction\Models\Category;

class SearchController extends BaseController
{
    public function index(): string
    {
        $categoryModel = new Category($this->db());
        return $this->render('search/index', [
            'categories' => $categoryModel->all(),
            'ratings' => $categoryModel->classifications('rating'),
            'results' => [],
            'query' => '',
            'filters' => [],
        ]);
    }

    public function search(): string
    {
        $query = trim($_POST['q'] ?? '');
        $filters = [
            'catid' => (int) ($_POST['catid'] ?? 0),
            'rid' => (int) ($_POST['rid'] ?? 0),
            'completed' => (int) ($_POST['completed'] ?? 0),
        ];
        $filters = array_filter($filters);

        $categoryModel = new Category($this->db());
        $results = (new Story($this->db()))->search($query, $filters, 50);

        return $this->render('search/index', [
            'categories' => $categoryModel->all(),
            'ratings' => $categoryModel->classifications('rating'),
            'results' => $results,
            'query' => $query,
            'filters' => $filters,
        ]);
    }

    public function legacyIndex(): void
    {
        $this->redirect('/search');
    }
}
