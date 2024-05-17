<?php

namespace App\Service\MarkdownService;

use Exception;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

class MarkdownService
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter();
    }

    /**
     * @throws Exception
     */
    public function toHtml(string $markdown): string
    {
        try {
            return $this->converter->convert($markdown);
        } catch (CommonMarkException $e) {
            throw new Exception($e->getMessage());
        }
    }
}