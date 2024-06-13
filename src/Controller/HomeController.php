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
    #[Route('/markdown/{filePath}', name: 'markdown_render', requirements: ['filePath' => '.+'])]
    public function renderMarkdownFile(string $filePath): Response
    {
        $filePath = urldecode($filePath);
        $filePath = str_replace('\\',DIRECTORY_SEPARATOR, $filePath);
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The markdown file does not exist');
        }

        $parsedContent = $this->markdownService->parseMarkdownFile($filePath);

        return $this->render('markdown/render.html.twig', [
            'content' => $parsedContent['content'],
            'metadata' => $parsedContent['metadata']
        ]);
    }

    #[Route('/', name: 'home')]
    public function listMarkdownFiles(): Response
    {
        // List of markdown files and directories that goes in the sidebar
        $markdownDirectory = $this->getParameter('kernel.project_dir') . '/markdown_files/';
        $directoryTree = $this->scanDirectory($markdownDirectory, $markdownDirectory);

        return $this->render('home/home.html.twig', [
            'directoryTree' => $directoryTree
        ]);
    }

    private function scanDirectory(string $markdownDirectory, string $baseDirectory): array
    {
        $finder = new Finder();
        $finder->in($markdownDirectory)->directories()->depth('== 0');

        $result = [];
        foreach ($finder as $dir) {
            $dirPath = $dir->getRealPath();
            $result[$dir->getFilename()] = [
                'type' => 'directory',
                'files' => $this->scanDirectory($dirPath, $baseDirectory)
            ];
        }

        $finder->in($markdownDirectory)->files()->name('*.md')->depth('== 0');
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $relativeFilePath = str_replace(DIRECTORY_SEPARATOR,'\\',$filePath);
            $result[$file->getFilenameWithoutExtension()] = [
                'type' => 'file',
                'path' => $relativeFilePath,
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
