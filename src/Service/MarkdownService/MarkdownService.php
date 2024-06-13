<?php

namespace App\Service\MarkdownService;

use Exception;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class MarkdownService
{
    private CommonMarkConverter $converter;
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->converter = new CommonMarkConverter();
        $this->params = $params;
    }

    /**
     * @throws Exception
     */

    public function toHtml(string $markdown): string
    {
        // First convert Obsidian links to markdown links
        $markdown = $this->convertObsidianLinks($markdown);

        try {
            return $this->converter->convert($markdown);
        } catch (CommonMarkException $e) {
            throw new Exception($e->getMessage());
        }
    }
    private function convertObsidianLinks(string $markdown): string
    {
        // Convert [[Link]] or [[Link|Link Text]] to [Link Text](link)
        $pattern = '/\[\[([^]|]+)(?:\|([^]]+))?]]/';
        return preg_replace_callback($pattern, function ($matches) {
            $filePath = $matches[1];
            $linkText = $matches[2] ?? $filePath;

            // Find the markdown file path for the given link text
            $filePath = $this->findMarkdownFile($filePath);

            if ($filePath) {
                $normalizedPath =  str_replace(DIRECTORY_SEPARATOR,'\\',$filePath);
                $encodedFilePath = urlencode("$normalizedPath");
                return "<a hx-boost=true hx-push-url=false hx-target=#main-content href=/markdown/$encodedFilePath>$linkText</a>";
            } else {
                return $matches[0]; // Return the original text if no file is found
            }
        }, $markdown);
    }

    private function findMarkdownFile(string $linkText): ?string
    {
        $finder = new Finder();
        $finder->files()->in($this->getMarkdownDirectory())->name('*.md');

        // Trim any extra characters at the beginning of the link text
        $linkText = trim($linkText); // Add any extra characters you want to remove

        foreach ($finder as $file) {
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            if ($filename === $linkText) {
                return $file->getRealPath();
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function parseMarkdownFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        // Extract YAML front matter if present
        if (preg_match('/^---(.*?)---\s*(.*)$/s', $content, $matches)) {
            $yaml = Yaml::parse($matches[1]);
            $markdown = $matches[2];
        } else {
            $yaml = [];
            $markdown = $content;
        }

        $htmlContent = $this->toHtml($markdown);

        return [
            'metadata' => $yaml,
            'content' => $htmlContent
        ];
    }

    private function getMarkdownDirectory(): ?string
    {
        return $this->params->get('kernel.project_dir') . '/markdown_files/';
    }
}