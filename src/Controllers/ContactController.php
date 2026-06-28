<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Mailer;

class ContactController extends BaseController
{
    public function index(): string
    {
        return $this->render('contact/index', [
            'csrf' => $this->csrf(),
            'sent' => false,
            'errors' => [],
        ]);
    }

    public function send(): string
    {
        $errors = [];
        if (!$this->validateCsrf()) {
            $errors[] = 'Invalid security token.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($name === '') {
                $errors[] = 'Name is required.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email is required.';
            }
            if ($subject === '') {
                $errors[] = 'Subject is required.';
            }
            if ($message === '') {
                $errors[] = 'Message is required.';
            }

            if (empty($errors)) {
                $mailer = $this->app->get(Mailer::class);
                $mailer->send(
                    $this->config()->get('site.email'),
                    'Contact: ' . $subject,
                    "From: {$name} <{$email}>\n\n" . $message
                );
                return $this->render('contact/index', [
                    'csrf' => $this->csrf(),
                    'sent' => true,
                    'errors' => [],
                ]);
            }
        }

        return $this->render('contact/index', [
            'csrf' => $this->csrf(),
            'sent' => false,
            'errors' => $errors,
        ]);
    }

    public function legacyIndex(): void
    {
        $this->redirect('/contact');
    }
}
