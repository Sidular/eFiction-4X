<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;

class SkinsController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $skins = array_map('basename', glob(__DIR__ . '/../../templates/layouts/*', GLOB_ONLYDIR) ?: []);
        return $this->render('admin/skins', ['skins' => $skins, 'csrf' => $this->csrf()]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/skins');
        }
        $skin = preg_replace('/[^a-z0-9_-]/i', '', $_POST['skin'] ?? 'default');
        $this->db()->update('settings', ['skin' => $skin], 'sitekey = :key', ['key' => $this->config()->siteKey()]);
        $this->flash('success', 'Skin updated.');
        $this->redirect('/admin/skins');
    }
}
