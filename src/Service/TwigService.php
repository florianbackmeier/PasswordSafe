<?php
namespace App\Service;

use Twig\TwigFilter;
use Twig_Environment;
use Twig_Extension;

class TwigService extends Twig_Extension
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

    public function initRuntime(Twig_Environment $environment)
    {
    }

    public function getGlobals()
    {
    }
}
