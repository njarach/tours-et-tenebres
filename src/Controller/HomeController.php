<?php

namespace App\Controller;

use App\Service\MarkdownService\MarkdownService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private MarkdownService $markdownService;

    public function __construct(MarkdownService $markdownService)
    {
        $this->markdownService = $markdownService;
    }

    /**
     * @throws Exception
     */
    #[Route('/markdown/{filePath}', name: 'markdown_render')]
    public function renderMarkdownFile(string $filePath, SessionInterface $session): Response
    {
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The markdown file does not exist');
        }

        $parsedContent = $this->markdownService->parseMarkdownFile($filePath);

        // Store the current file path in the session
        $session->set('previous_file_path', $filePath);

        // List of markdown files and directories
        $markdownDirectory = $this->getParameter('kernel.project_dir') . '/markdown_files/';
        $directoryTree = $this->scanDirectory($markdownDirectory);

        return $this->render('markdown/render.html.twig', [
            'content' => $parsedContent['content'],
            'metadata' => $parsedContent['metadata'],
            'directoryTree' => $directoryTree
        ]);
    }

    #[Route('/home',name: 'home')]
    public function listMarkdownFiles(): Response
    {
        // List of markdown files and directories
        $markdownDirectory = $this->getParameter('kernel.project_dir') . '/markdown_files/';
        $directoryTree = $this->scanDirectory($markdownDirectory);
        return $this->render('home/home.html.twig', [
            'directoryTree' => $directoryTree
        ]);
    }

    private function scanDirectory(string $markdownDirectory): array
    {
        $finder = new Finder();
        $finder->in($markdownDirectory)->directories()->depth('== 0');

        $result = [];
        foreach ($finder as $dir) {
            $dirPath = $dir->getRealPath();
            $result[$dir->getFilename()] = [
                'type' => 'directory',
                'files' => $this->scanDirectory($dirPath)
            ];
        }

        $finder->in($markdownDirectory)->files()->name('*.md')->depth('== 0');
        foreach ($finder as $file) {
            $result[$file->getFilenameWithoutExtension()] = [
                'type' => 'file',
                'path' => $file->getRealPath(),
                'name' => $this->formatTitle($file->getFilenameWithoutExtension())
            ];
        }
        return $result;
    }

    private function formatTitle(string $filename): string
    {
        // Replace dashes and underscores with spaces and capitalize each word
        return ucwords(str_replace(['-', '_'], ' ', $filename));
    }
}
