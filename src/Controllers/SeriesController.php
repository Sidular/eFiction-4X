<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Models\Series;

class SeriesController extends BaseController
{
    public function view(int $id): string
    {
        $series = (new Series($this->db()))->find($id);
        if (!$series) {
            http_response_code(404);
            return $this->render('error', ['code' => 404, 'message' => 'Series not found.']);
        }
        return $this->render('series/view', ['series' => $series]);
    }

    public function legacyView(): void
    {
        $sid = (int) ($_GET['sid'] ?? 0);
        $this->redirect('/series/' . $sid);
    }
}
