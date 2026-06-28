<?php

declare(strict_types=1);

namespace eFiction\Controllers;

class HomeController extends BaseController
{
    public function index(): string
    {
        $db = $this->db();

        $latest = $db->fetchAll(
            'SELECT s.sid, s.title, s.summary, s.rid, s.catid, s.charid, s.classid, s.uid, s.completed, s.date, s.updated, s.wordcount, s.rating, a.penname
             FROM ' . $db->table('stories') . ' s
             LEFT JOIN ' . $db->table('authors') . ' a ON a.uid = s.uid
             WHERE s.validated = 1 AND s.completed = 1
             ORDER BY s.updated DESC
             LIMIT 10'
        );

        $featured = $db->fetch(
            'SELECT s.sid, s.title, s.summary, a.penname
             FROM ' . $db->table('stories') . ' s
             LEFT JOIN ' . $db->table('authors') . ' a ON a.uid = s.uid
             WHERE s.featured = 1 AND s.validated = 1
             ORDER BY s.updated DESC
             LIMIT 1'
        );

        $news = $db->fetchAll(
            'SELECT * FROM ' . $db->table('news') . ' ORDER BY nid DESC LIMIT 5'
        );

        return $this->render('home', [
            'latest' => $latest,
            'featured' => $featured,
            'news' => $news,
        ]);
    }
}
