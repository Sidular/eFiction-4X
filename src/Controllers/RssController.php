<?php

declare(strict_types=1);

namespace eFiction\Controllers;

use eFiction\Helpers;
use eFiction\Models\Story;
use eFiction\Models\Review;

class RssController extends BaseController
{
    public function index(): string
    {
        return $this->feed('stories');
    }

    public function feed(string $type): string
    {
        $siteTitle = $this->config()->get('site.title', 'eFiction Archive');
        $siteUrl = $this->config()->get('site.url', Helpers::baseUrl());

        $items = match ($type) {
            'reviews' => (new Review($this->db()))->recent(15),
            default => (new Story($this->db()))->latest(15),
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>' . $this->e($siteTitle) . '</title>' . "\n";
        $xml .= '<link>' . $this->e($siteUrl) . '</link>' . "\n";
        $xml .= '<description>Latest ' . $type . ' from ' . $this->e($siteTitle) . '</description>' . "\n";
        $xml .= '<language>en</language>' . "\n";

        foreach ($items as $item) {
            $title = $type === 'reviews' ? 'Review on ' . ($item['story_title'] ?? 'a story') : ($item['title'] ?? 'Untitled');
            $link = $type === 'reviews'
                ? $siteUrl . '/story/' . ($item['sid'] ?? 0)
                : $siteUrl . '/story/' . ($item['sid'] ?? 0);
            $description = $type === 'reviews' ? ($item['review'] ?? '') : ($item['summary'] ?? '');
            $date = isset($item['date']) ? date('r', strtotime($item['date'])) : date('r');

            $xml .= '<item>' . "\n";
            $xml .= '<title>' . $this->e($title) . '</title>' . "\n";
            $xml .= '<link>' . $this->e($link) . '</link>' . "\n";
            $xml .= '<description>' . $this->e(strip_tags($description)) . '</description>' . "\n";
            $xml .= '<pubDate>' . $date . '</pubDate>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo $xml;
        return '';
    }

    public function legacyFeed(): void
    {
        $type = $_GET['type'] ?? 'stories';
        $this->redirect('/rss/' . $type);
    }
}
