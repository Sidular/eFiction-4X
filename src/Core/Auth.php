<?php

declare(strict_types=1);

namespace eFiction\Core;

/**
 * Authentication and authorization.
 */
class Auth
{
    private ?array $user = null;

    public function __construct(
        private readonly Database $db,
        private readonly Session $session,
        private readonly Security $security
    ) {}

    public function attempt(string $penname, string $password): bool
    {
        $user = $this->db->fetch(
            'SELECT uid, penname, password, email, level, validated, userskin, language
             FROM ' . $this->db->table('authors') . '
             WHERE penname = :penname
             LIMIT 1',
            ['penname' => $penname]
        );

        if (!$user) {
            return false;
        }

        $hash = $user['password'];

        // Legacy md5 transparent upgrade
        if (strlen($hash) === 32 && hash_equals(md5($password), $hash)) {
            $this->upgradePassword($user['uid'], $password);
            $this->loginUser($user);
            return true;
        }

        if ($this->security->verifyPassword($password, $hash)) {
            if ($this->security->needsRehash($hash)) {
                $this->upgradePassword($user['uid'], $password);
            }
            $this->loginUser($user);
            return true;
        }

        return false;
    }

    private function upgradePassword(int $uid, string $password): void
    {
        $this->db->update(
            'authors',
            ['password' => $this->security->hashPassword($password)],
            'uid = :uid',
            ['uid' => $uid]
        );
    }

    public function loginUser(array $user): void
    {
        unset($user['password']);
        $this->user = $user;
        $this->session->set('user_id', $user['uid']);
        $this->session->set('penname', $user['penname']);
        $this->session->set('level', $user['level'] ?? 0);
        $this->session->set('userskin', $user['userskin'] ?? 'default');

        $this->db->update(
            'authors',
            ['lastvisit' => date('Y-m-d H:i:s')],
            'uid = :uid',
            ['uid' => $user['uid']]
        );
    }

    public function logout(): void
    {
        $this->user = null;
        $this->session->remove('user_id');
        $this->session->remove('penname');
        $this->session->remove('level');
        $this->session->remove('userskin');
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
            'SELECT uid, penname, email, level, validated, userskin, language, timezone, dateformat, timeformat
             FROM ' . $this->db->table('authors') . '
             WHERE uid = :uid
             LIMIT 1',
            ['uid' => $uid]
        );

        return $this->user;
    }

    public function id(): int
    {
        return (int) ($this->user()['uid'] ?? 0);
    }

    public function penname(): string
    {
        return (string) ($this->user()['penname'] ?? '');
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['level'] ?? 0) >= 1;
    }

    public function isSuperAdmin(): bool
    {
        return ($this->user()['level'] ?? 0) >= 2;
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            ResponseHelper::redirect('/user/login');
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

    public function skin(): string
    {
        return (string) ($this->user()['userskin'] ?? $this->session->get('userskin', 'default'));
    }
}

/**
 * Internal helper to avoid circular dependency with Response service.
 */
final class ResponseHelper
{
    public static function redirect(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit;
    }
}
