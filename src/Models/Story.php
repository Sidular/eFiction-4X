<?php

declare(strict_types=1);

namespace eFiction\Models;

class Story extends Model
{
    protected function tableName(): string
    {
        return 'stories';
    }

    public function find(int $id, bool $includeUnvalidated = false): ?array
    {
        $sql = 'SELECT s.*, a.penname, a.uid AS author_id
                FROM ' . $this->table('stories') . ' s
                LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
                WHERE s.sid = :sid';
        if (!$includeUnvalidated) {
            $sql .= ' AND s.validated = 1';
        }
        return $this->db->fetch($sql, ['sid' => $id]);
    }

    public function findChapters(int $sid, bool $includeUnvalidated = false): array
    {
        $sql = 'SELECT * FROM ' . $this->table('chapters') . ' WHERE sid = :sid';
        if (!$includeUnvalidated) {
            $sql .= ' AND validated = 1';
        }
        $sql .= ' ORDER BY inorder, chapid';
        return $this->db->fetchAll($sql, ['sid' => $sid]);
    }

    public function findChapter(int $chapid): ?array
    {
        return $this->db->fetch(
            'SELECT c.*, s.title AS story_title, s.sid, a.penname
             FROM ' . $this->table('chapters') . ' c
             JOIN ' . $this->table('stories') . ' s ON s.sid = c.sid
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
             WHERE c.chapid = :chapid AND c.validated = 1 AND s.validated = 1',
            ['chapid' => $chapid]
        );
    }

    public function latest(int $limit = 10, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, a.penname FROM ' . $this->table('stories') . ' s
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
             WHERE s.validated = 1
             ORDER BY s.updated DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
        );
    }

    public function featured(int $limit = 1): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, a.penname FROM ' . $this->table('stories') . ' s
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
             WHERE s.featured = 1 AND s.validated = 1
             ORDER BY s.updated DESC
             LIMIT ' . (int) $limit,
        );
    }

    public function byCategory(int $catid, int $limit = 20, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, a.penname FROM ' . $this->table('stories') . ' s
             LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
             WHERE s.validated = 1 AND (s.catid LIKE :exact OR s.catid LIKE :start OR s.catid LIKE :end OR s.catid LIKE :middle)
             ORDER BY s.updated DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            [
                'exact'  => "{$catid}",
                'start'  => "{$catid},%",
                'end'    => "%,{$catid}",
                'middle' => "%,{$catid},%",
            ]
        );
    }

    public function byAuthor(int $uid, int $limit = 100, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT s.* FROM ' . $this->table('stories') . ' s
             WHERE s.validated = 1 AND s.uid = :uid
             ORDER BY s.updated DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
            ['uid' => $uid]
        );
    }

    public function search(string $query, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = ['s.validated = 1'];
        $params = [];

        if ($query !== '') {
            $where[] = '(s.title LIKE :q OR s.summary LIKE :q)';
            $params['q'] = '%' . $query . '%';
        }
        if (!empty($filters['catid'])) {
            $where[] = 's.catid LIKE :catid';
            $params['catid'] = '%' . $filters['catid'] . '%';
        }
        if (!empty($filters['rid'])) {
            $where[] = 's.rid = :rid';
            $params['rid'] = $filters['rid'];
        }
        if (!empty($filters['uid'])) {
            $where[] = 's.uid = :uid';
            $params['uid'] = $filters['uid'];
        }
        if (!empty($filters['completed'])) {
            $where[] = 's.completed = :completed';
            $params['completed'] = $filters['completed'] ? 1 : 0;
        }

        $sql = 'SELECT s.*, a.penname FROM ' . $this->table('stories') . ' s
                LEFT JOIN ' . $this->table('authors') . ' a ON a.uid = s.uid
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.updated DESC
                LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function chapterText(int $chapid): string
    {
        $path = $this->db->config()->storiesPath() . '/' . $chapid . '.txt';
        if (!file_exists($path)) {
            return '';
        }
        return (string) file_get_contents($path);
    }
}
