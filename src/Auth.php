<?php

declare(strict_types=1);

namespace eFiction;

class Auth
{
    private ?array $user = null;

    public function __construct(
        private Database $db,
        private Session $session,
        private Config $config
    ) {}

    public function attempt(string $penname, string $password): bool
    {
        $user = $this->db->fetch(
            'SELECT * FROM ' . $this->db->table('authors') . ' WHERE penname = :penname LIMIT 1',
            ['penname' => $penname]
        );

        if (!$user) {
            return false;
        }

        $hash = $user['password'] ?? '';

        // Legacy md5 support with transparent upgrade
        if (strlen($hash) === 32 && hash_equals(md5($password), $hash)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->db->update('authors', ['password' => $newHash], 'uid = :uid', ['uid' => $user['uid']]);
            $this->loginUser($user);
            return true;
        }

        if (password_verify($password, $hash)) {
            if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
                $this->db->update('authors', ['password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])], 'uid = :uid', ['uid' => $user['uid']]);
            }
            $this->loginUser($user);
            return true;
        }

        return false;
    }

    public function loginUser(array $user): void
    {
        unset($user['password']);
        $this->user = $user;
        $this->session->set('user_id', $user['uid']);
        $this->session->set('penname', $user['penname']);
        $this->session->set('level', $user['level'] ?? 0);

        $this->db->update('authors', ['lastvisit' => date('Y-m-d H:i:s')], 'uid = :uid', ['uid' => $user['uid']]);
    }

    public function logout(): void
    {
        $this->user = null;
        $this->session->remove('user_id');
        $this->session->remove('penname');
        $this->session->remove('level');
    }

    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }
        $uid = $this->session->get('user_id');
        if (!$uid) {
            return null;
        }
        $this->user = $this->db->fetch(
            'SELECT uid, penname, email, level, validated, userskin FROM ' . $this->db->table('authors') . ' WHERE uid = :uid LIMIT 1',
            ['uid' => $uid]
        );
        return $this->user;
    }

    public function id(): int
    {
        return (int) ($this->user()['uid'] ?? 0);
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['level'] ?? 0) >= 1;
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            Helpers::redirect('/user/login');
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            exit('Access denied.');
        }
    }
}
