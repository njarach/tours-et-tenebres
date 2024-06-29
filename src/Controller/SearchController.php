<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search_files')]
    public function searchFiles(Request $request): Response
    {
        $query = $request->query->get('searchbar', '');

        $markdownDirectory = $this->getParameter('kernel.project_dir') . '/markdown_files/';
        $results = $this->searchMarkdownFiles($markdownDirectory, $query);

        return $this->render('search/search_results.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    private function searchMarkdownFiles(string $markdownDirectory, float|bool|int|string|null $query): array
    {
        $finder = new Finder();
        $results = [];

        // Check if the query is valid (at least 2 characters)
        if (!empty($query) && strlen($query) >= 2) {
            $finder->files()->in($markdownDirectory)->name('*.md');

            foreach ($finder as $file) {
                $filename = $file->getFilenameWithoutExtension();
                // Perform a case-insensitive search on the filename
                if (stripos($filename, $query) !== false) {
                    $filePath = $file->getRealPath();
                    $relativeFilePath = str_replace(DIRECTORY_SEPARATOR,'\\',$filePath);
                    $results[] = [
                        'name' => $filename,
                        'path' => $relativeFilePath
                    ];
                }
            }
        }

        return $results;
    }
}
