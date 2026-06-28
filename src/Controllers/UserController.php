<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Author;
use eFiction\Models\Story;
use eFiction\Models\Series;

class UserController extends BaseController
{
    public function profile(): string
    {
        $id = $this->auth()->id() ?: (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $this->redirect('/user/login');
        }

        $author = (new Author($this->db()))->find($id);
        if (!$author) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'User not found.']);
        }

        $stories = (new Story($this->db()))->byAuthor($id);
        $series = (new Series($this->db()))->byAuthor($id);

        return $this->render('user/profile', [
            'author' => $author,
            'stories' => $stories,
            'series' => $series,
        ]);
    }

    public function action(string $action): string
    {
        return match ($action) {
            'login' => $this->login(),
            'register' => $this->register(),
            'logout' => $this->logout(),
            'edit' => $this->edit(),
            default => $this->profile(),
        };
    }

    private function login(): string
    {
        if ($this->auth()->check()) {
            $this->redirect('/');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $penname = $this->string('penname');
            $password = $_POST['password'] ?? '';
            if ($this->auth()->attempt($penname, $password)) {
                $this->flash('success', 'Welcome back, ' . $penname . '!');
                $this->redirect('/');
            }
            $error = 'Invalid penname or password.';
        }

        return $this->render('user/login', [
            'error' => $error,
            'csrf' => $this->csrf(),
        ]);
    }

    private function register(): string
    {
        if ($this->auth()->check()) {
            $this->redirect('/');
        }

        $errors = [];
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $errors[] = 'Invalid security token.';
            } else {
                $data = [
                    'penname' => trim($_POST['penname'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'password' => $_POST['password'] ?? '',
                ];
                $confirm = $_POST['password_confirm'] ?? '';

                if ($data['penname'] === '') {
                    $errors[] = 'Penname is required.';
                } elseif ((new Author($this->db()))->findByPenname($data['penname'])) {
                    $errors[] = 'Penname already taken.';
                }
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'A valid email is required.';
                }
                if (strlen($data['password']) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                }
                if ($data['password'] !== $confirm) {
                    $errors[] = 'Passwords do not match.';
                }

                if (empty($errors)) {
                    (new Author($this->db()))->create($data);
                    $this->flash('success', 'Registration successful. Please log in.');
                    $this->redirect('/user/login');
                }
            }
        }

        return $this->render('user/register', [
            'errors' => $errors,
            'data' => $data,
            'csrf' => $this->csrf(),
        ]);
    }

    private function logout(): string
    {
        $this->auth()->logout();
        $this->flash('success', 'You have been logged out.');
        $this->redirect('/');
        return '';
    }

    private function edit(): string
    {
        $this->requireAuth();
        $uid = $this->auth()->id();
        $authorModel = new Author($this->db());
        $author = $authorModel->find($uid);
        if (!$author) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Profile not found.']);
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrf()) {
                $errors[] = 'Invalid security token.';
            } else {
                $update = [
                    'realname' => trim($_POST['realname'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'age' => (int) ($_POST['age'] ?? 0) ?: null,
                    'location' => trim($_POST['location'] ?? ''),
                    'bio' => trim($_POST['bio'] ?? ''),
                    'website' => trim($_POST['website'] ?? ''),
                ];
                if (!filter_var($update['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'A valid email is required.';
                }
                if (empty($errors)) {
                    $authorModel->updateProfile($uid, $update);
                    $this->flash('success', 'Profile updated.');
                    $this->redirect('/user/profile');
                }
            }
        }

        return $this->render('user/edit', [
            'author' => $author,
            'errors' => $errors,
            'csrf' => $this->csrf(),
        ]);
    }

    public function legacyAction(): void
    {
        $action = $_GET['action'] ?? 'profile';
        $this->redirect('/user/' . $action . (!empty($_GET['uid']) ? '?id=' . (int) $_GET['uid'] : ''));
    }
}
