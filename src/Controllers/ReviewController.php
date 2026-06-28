<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Review;
use eFiction\Models\Story;

class ReviewController extends BaseController
{
    public function index(): string
    {
        $reviews = (new Review($this->db()))->recent(20);
        return $this->render('reviews/index', ['reviews' => $reviews]);
    }

    public function add(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->validateCsrf()) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('/reviews');
        }

        $sid = (int) ($_POST['sid'] ?? 0);
        $chapid = (int) ($_POST['chapid'] ?? 0);
        $review = trim($_POST['review'] ?? '');
        $rating = (int) ($_POST['rating'] ?? 0);

        $storyModel = new Story($this->db());
        $story = $storyModel->find($sid);
        if (!$story || $review === '') {
            $this->flash('error', 'Invalid review.');
            $this->redirect('/story/' . $sid);
        }

        $user = $this->auth()->user();
        $data = [
            'sid' => $sid,
            'chapid' => $chapid,
            'uid' => $user ? $user['uid'] : 0,
            'reviewer' => $user ? $user['penname'] : trim($_POST['reviewer'] ?? 'Anonymous'),
            'email' => $user ? $user['email'] : trim($_POST['email'] ?? ''),
            'review' => $review,
            'rating' => $rating,
            'validated' => 1,
        ];

        (new Review($this->db()))->add($data);
        $this->flash('success', 'Review added.');
        $this->redirect('/story/' . $sid . ($chapid ? '/chapter/' . $chapid : ''));
    }
}
