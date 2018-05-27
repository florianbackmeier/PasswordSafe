<?php
namespace App\Security\Authentication;


use App\Security\DatabaseService;
use App\Service\CategoryService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{

    private $databaseService;
    private $router;
    private $categoryService;

    public function __construct(DatabaseService $databaseService, RouterInterface $router, CategoryService $categoryService)
    {
        $this->databaseService = $databaseService;
        $this->router = $router;
        $this->categoryService = $categoryService;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $meta = $this->databaseService->getMeta($token);
        $startCategory = $meta->get('startCategory');
        if (!empty($startCategory)) {
            return new RedirectResponse($this->router->generate('category', array('categorySlug' => $this->categoryService->generateUrlSlug($startCategory))));
        } else {
            return new RedirectResponse($this->router->generate('home'));
        }
    }

}
