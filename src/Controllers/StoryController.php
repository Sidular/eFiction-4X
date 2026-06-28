<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Story;
use eFiction\Models\Review;

class StoryController extends BaseController
{
    public function view(int $id): string
    {
        $storyModel = new Story($this->db());
        $story = $storyModel->find($id);
        if (!$story) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Story not found.']);
        }

        $chapters = $storyModel->findChapters($id);
        $reviews = (new Review($this->db()))->forStory($id);

        return $this->render('story/view', [
            'story' => $story,
            'chapters' => $chapters,
            'reviews' => $reviews,
        ]);
    }

    public function chapter(int $id, int $chapter): string
    {
        $storyModel = new Story($this->db());
        $story = $storyModel->find($id);
        if (!$story) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Story not found.']);
        }

        $chapterRow = $this->db()->fetch(
            'SELECT * FROM ' . $this->db()->table('chapters') . '
             WHERE sid = :sid AND chapid = :chapid AND validated = 1 LIMIT 1',
            ['sid' => $id, 'chapid' => $chapter]
        );
        if (!$chapterRow) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Chapter not found.']);
        }

        $text = $storyModel->chapterText((int) $chapterRow['chapid']);
        $reviews = (new Review($this->db()))->forChapter((int) $chapterRow['chapid']);

        return $this->render('story/chapter', [
            'story' => $story,
            'chapter' => $chapterRow,
            'text' => $text,
            'reviews' => $reviews,
        ]);
    }

    public function legacyView(): void
    {
        $sid = (int) ($_GET['sid'] ?? 0);
        $chapid = (int) ($_GET['chapid'] ?? 0);
        if ($chapid > 0) {
            $this->redirect('/story/' . $sid . '/chapter/' . $chapid);
        }
        $this->redirect('/story/' . $sid);
    }
}
