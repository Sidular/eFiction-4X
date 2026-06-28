<?php

declare(strict_types=1);

namespace eFiction\Admin;

use eFiction\Controllers\BaseController;
use eFiction\Models\Author;

class MembersController extends BaseController
{
    public function index(): string
    {
        $this->requireAdmin();
        $members = $this->db()->fetchAll(
            'SELECT uid, penname, email, level, validated, date FROM ' . $this->db()->table('authors') . ' ORDER BY date DESC'
        );
        return $this->render('admin/members', ['members' => $members, 'csrf' => $this->csrf()]);
    }

    public function edit(int $id): string
    {
        $this->requireAdmin();
        $member = (new Author($this->db()))->find($id);
        if (!$member) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Member not found.']);
        }
        return $this->render('admin/member_edit', ['member' => $member, 'csrf' => $this->csrf()]);
    }

    public function save(int $id): void
    {
        $this->requireAdmin();
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/members/' . $id);
        }

        $update = [
            'penname' => trim($_POST['penname'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'level' => (int) ($_POST['level'] ?? 0),
            'validated' => (int) ($_POST['validated'] ?? 1),
        ];
        (new Author($this->db()))->updateProfile($id, $update);
        $this->flash('success', 'Member updated.');
        $this->redirect('/admin/members');
    }
}
