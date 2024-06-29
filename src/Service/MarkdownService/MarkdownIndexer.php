<?php

namespace App\Service\MarkdownService;

class MarkdownIndexer
{
    private $markdownDirectory;
    private $index = [];

    public function __construct(string $markdownDirectory)
    {
        $this->markdownDirectory = $markdownDirectory;
        $this->indexMarkdownFiles();
    }

    private function indexMarkdownFiles()
    {
        $files = glob($this->markdownDirectory . '/*.md');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->indexFile($file, $content);
        }
    }

    private function indexFile(string $filePath, string $content)
    {
        if (preg_match('/---(.*?)---/s', $content, $matches)) {
            $frontmatter = Yaml::parse($matches[1]);
            $body = trim(str_replace($matches[0], '', $content));
            $this->index[] = [
                'file' => basename($filePath),
                'title' => $frontmatter['title'] ?? 'Untitled',
                'tags' => $frontmatter['tags'] ?? [],
                'content' => $body
            ];
        }
    }

    public function search(string $query)
    {
        return array_filter($this->index, function ($item) use ($query) {
            return stripos($item['title'], $query) !== false || stripos($item['content'], $query) !== false;
        });
    }
}