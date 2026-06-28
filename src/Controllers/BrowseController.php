<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Category;
use eFiction\Models\Story;

class BrowseController extends BaseController
{
    public function index(): string
    {
        $categoryModel = new Category($this->db());
        return $this->render('browse/index', [
            'categories' => $categoryModel->tree(),
        ]);
    }

    public function type(string $type): string
    {
        $categoryModel = new Category($this->db());
        $items = match ($type) {
            'categories' => $categoryModel->all(),
            'characters' => $categoryModel->classifications('character'),
            'genres' => $categoryModel->classifications('genre'),
            'ratings' => $categoryModel->classifications('rating'),
            'warnings' => $categoryModel->classifications('warning'),
            default => [],
        };

        return $this->render('browse/list', [
            'type' => $type,
            'items' => $items,
        ]);
    }

    public function legacyIndex(): void
    {
        $type = $_GET['type'] ?? 'category';
        if ($type === 'category') {
            $this->redirect('/browse');
        }
        $this->redirect('/browse/' . $type . 's');
    }
}
