<?php
namespace App\Service;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigService extends AbstractExtension
{
    private $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('base64_encode', array($this, 'base64_encode')),
            new TwigFilter('categorySlug', array($this, 'categorySlug')),
        );
    }

    public function base64_encode($arg1)
    {
        return base64_encode($arg1);
    }

    public function categorySlug($arg1)
    {
        return $this->categoryService->generateUrlSlug($arg1);
    }

    public function getName()
    {
        return 'twigService';
    }
}
