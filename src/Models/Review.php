<?php

declare(strict_types=1);

namespace eFiction\Models;

class Review extends Model
{
    protected function tableName(): string
    {
        return 'reviews';
    }

    public function forStory(int $sid, bool $validatedOnly = true): array
    {
        $sql = 'SELECT r.*, a.penname AS author_penname
                FROM ' . $this->table('reviews') . ' r
                LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = r.uid
                WHERE r.sid = :sid';
        if ($validatedOnly) {
            $sql .= ' AND r.validated = 1';
        }
        $sql .= ' ORDER BY r.date DESC';
        return $this->db->fetchAll($sql, ['sid' => $sid]);
    }

    public function forChapter(int $chapid, bool $validatedOnly = true): array
    {
        $sql = 'SELECT r.*, a.penname AS author_penname
                FROM ' . $this->table('reviews') . ' r
                LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = r.uid
                WHERE r.chapid = :chapid';
        if ($validatedOnly) {
            $sql .= ' AND r.validated = 1';
        }
        $sql .= ' ORDER BY r.date DESC';
        return $this->db->fetchAll($sql, ['chapid' => $chapid]);
    }

    public function recent(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT r.*, s.title AS story_title, a.penname AS author_penname
             FROM ' . $this->table('reviews') . ' r
             LEFT JOIN ' . $this->table('stories') . ' s ON s.sid = r.sid
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = r.uid
             WHERE r.validated = 1
             ORDER BY r.date DESC
             LIMIT ' . (int) $limit,
        );
    }

    public function add(array $data): int
    {
        $data['date'] = date('Y-m-d H:i:s');
        return $this->db->insert('reviews', $data);
    }

    public function countForStory(int $sid): int
    {
        return $this->db->count('reviews', 'sid = :sid AND validated = 1', ['sid' => $sid]);
    }
}
