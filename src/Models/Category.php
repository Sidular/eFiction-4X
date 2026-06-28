<?php

declare(strict_types=1);

namespace eFiction\Models;

class Category extends Model
{
    protected function tableName(): string
    {
        return 'categories';
    }

    public function all(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->table('categories') . ' ORDER BY parentcatid, displayorder, category'
        );
    }

    public function tree(): array
    {
        $rows = $this->all();
        $tree = [];
        foreach ($rows as $row) {
            if ((int) $row['parentcatid'] === 0) {
                $row['children'] = [];
                $tree[(int) $row['catid']] = $row;
            }
        }
        foreach ($rows as $row) {
            $parent = (int) $row['parentcatid'];
            if ($parent > 0 && isset($tree[$parent])) {
                $tree[$parent]['children'][] = $row;
            }
        }
        return $tree;
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM ' . $this->table('categories') . ' WHERE catid = :catid LIMIT 1',
            ['catid' => $id]
        );
    }

    public function classifications(string $type): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->table('classifications') . '
             WHERE type = :type ORDER BY displayorder, name',
            ['type' => $type]
        );
    }

    public function allClassifications(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM ' . $this->table('classifications') . ' ORDER BY type, displayorder, name'
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['type']][] = $row;
        }
        return $grouped;
    }

    public function findClassification(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM ' . $this->table('classifications') . ' WHERE classid = :classid LIMIT 1',
            ['classid' => $id]
        );
    }
}
