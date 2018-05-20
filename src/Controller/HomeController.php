<?php
namespace App\Controller;

use App\Security\DatabaseService;
use App\Service\CategoryService;
use App\Security\RSAService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController {

    public function __construct(DatabaseService $databaseService)
    {
        parent::__construct($databaseService);
    }


    /**
     * @Route("/", name="home")
     * @Template("index.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function homeAction()
    {
        $token = $this->get('security.token_storage')->getToken();

        $rows = $this->databaseService->getDatabaseRows($token, '');

        return array_merge($this->defaultModel(), array('db' => $rows, 'category' => 'default'));
    }

    /**
     * @Route("/category/{categorySlug}", name="category")
     * @Template("index.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function categoryAction($categorySlug, CategoryService $categoryService)
    {
        $token = $this->get('security.token_storage')->getToken();

        $category = $categoryService->getCategoryFromUrlSlug($categorySlug);
        $rows = $this->databaseService->getDatabaseRows($token, $category);

        return array_merge($this->defaultModel(), array('db' => $rows, 'category' => $category));
    }

    /**
     * @Route("/sharedItems", name="sharedItems")
     * @Template("sharedItems.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function sharedItemsAction(RSAService $RSAService)
    {
        $token = $this->get('security.token_storage')->getToken();
        $sharedItems = $RSAService->getSharedItems($token);

        $sortedItems = array();
        foreach ($sharedItems as $item) {
            $sortedItems[$item->getSharedItem()->getOrigin()->getUsername()][] = $item;
        }

        return array_merge($this->defaultModel(), array('db' => $sharedItems, 'sortedItems' => $sortedItems));
    }
}
