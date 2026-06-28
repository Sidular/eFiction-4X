<?php

declare(strict_types=1);

namespace eFiction\Models;

class Author extends Model
{
    protected function tableName(): string
    {
        return 'authors';
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT uid, penname, realname, email, age, birthday, location, bio, website, level, date, lastvisit
             FROM ' . $this->table('authors') . ' WHERE uid = :uid LIMIT 1',
            ['uid' => $id]
        );
        if ($row) {
            $row['story_count'] = $this->db->count('stories', 'uid = :uid AND validated = 1', ['uid' => $id]);
            $row['review_count'] = $this->db->count('reviews', 'uid = :uid', ['uid' => $id]);
        }
        return $row;
    }

    public function findByPenname(string $penname): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM ' . $this->table('authors') . ' WHERE penname = :penname LIMIT 1',
            ['penname' => $penname]
        );
    }

    public function create(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['date'] = date('Y-m-d H:i:s');
        $uid = $this->db->insert('authors', $data);
        $this->db->insert('authorprefs', ['uid' => $uid]);
        return $uid;
    }

    public function updateProfile(int $uid, array $data): bool
    {
        return $this->db->update('authors', $data, 'uid = :uid', ['uid' => $uid]) > 0;
    }

    public function recent(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT uid, penname, date FROM ' . $this->table('authors') . '
             ORDER BY date DESC LIMIT ' . (int) $limit,
        );
    }
}
