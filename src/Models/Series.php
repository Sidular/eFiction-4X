<?php

declare(strict_types=1);

namespace eFiction\Models;

class Series extends Model
{
    protected function tableName(): string
    {
        return 'series';
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT se.*, a.penname
             FROM ' . $this->table('series') . ' se
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = se.uid
             WHERE se.seriesid = :seriesid AND se.validated = 1 LIMIT 1',
            ['seriesid' => $id]
        );
        if ($row) {
            $row['stories'] = $this->stories($id);
        }
        return $row;
    }

    public function stories(int $seriesid): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, ss.inorder, a.penname
             FROM ' . $this->table('series_stories') . ' ss
             JOIN ' . $this->table('stories') . ' s ON s.sid = ss.sid
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
             WHERE ss.seriesid = :seriesid AND s.validated = 1
             ORDER BY ss.inorder, s.sid',
            ['seriesid' => $seriesid]
        );
    }

    public function byAuthor(int $uid): array
    {
        return $this->db->fetchAll(
            'SELECT se.* FROM ' . $this->table('series') . ' se
             WHERE se.validated = 1 AND se.uid = :uid
             ORDER BY se.updated DESC',
            ['uid' => $uid]
        );
    }
}
