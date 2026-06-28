<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;

class SettingsController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $settings = $this->db()->fetch(
            'SELECT * FROM ' . $this->db()->table('settings') . ' WHERE sitekey = :key',
            ['key' => $this->config()->siteKey()]
        ) ?: [];
        return $this->render('admin/settings', [
            'settings' => $settings,
            'csrf' => $this->csrf(),
        ]);
    }

    public function save(): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/settings');
        }

        $allowed = ['sitetitle', 'siteemail', 'siteurl', 'language', 'maintenance', 'reviewsallowed', 'anonreviews', 'regvalidate', 'captcha'];
        $data = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = is_numeric($_POST[$key]) ? (int) $_POST[$key] : $_POST[$key];
            }
        }

        $this->db()->update('settings', $data, 'sitekey = :key', ['key' => $this->config()->siteKey()]);
        $this->flash('success', 'Settings saved.');
        $this->redirect('/admin/settings');
    }
}
