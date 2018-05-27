<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     * @Security("has_role('ROLE_USER')")
     */
    public function searchAction(Request $request)
    {
        $search = $request->request->get('search');
        $result = array();
        if ( !empty($search) ) {
            $token = $this->get('security.token_storage')->getToken();
            $rows = $this->databaseService->getDatabaseRows($token, null);


            foreach ($rows as $row) {
                if (stristr($row->getName(), $search)) {
                    $result[] = $row;
                }
            }
        }
        return $this->render('search.html.twig', array_merge($this->defaultModel(), array('db' => $result)));
    }
}
