<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TitleCapitalizeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('title_case', [$this, 'titleCase']),
        ];
    }

    public function titleCase(string $string): string
    {
        return ucwords(strtolower($string));
    }
}