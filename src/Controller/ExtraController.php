<?php
namespace App\Controller;

use App\Service\CategoryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class ExtraController extends AbstractDatabaseController
{
    /**
     * @Route("/editCategory/{categorySlug}", name="editCategory")
     * @Template("editCategory.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function editCategoryAction($categorySlug, CategoryService $categoryService)
    {
        $token = $this->get('security.token_storage')->getToken();

        $category = $categoryService->getCategoryFromUrlSlug($categorySlug);

        return array_merge($this->defaultModel(), array('category' => $category));
    }

    /**
     * @Route("/updateCategory", name="updateCategory")
     * @Method({"POST"})
     * @Security("has_role('ROLE_USER')")
     */
    public function readAction(Request $request, CategoryService $categoryService)
    {
        $token = $this->get('security.token_storage')->getToken();

        $oldCategory = $categoryService->getCategoryFromUrlSlug($request->request->get('categorySlug'));
        $category = $request->request->get('category');

        $rows = $this->databaseService->getDatabaseRows($token, $oldCategory);
        foreach ($rows as $row) {
            $row->updateAttribute('category', $category);
            $this->_updateDatabase($row);
        }
        return $this->redirectToRoute('category', array('categorySlug' => $categoryService->generateUrlSlug($category)));
    }

}
