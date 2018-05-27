<?php
namespace App\Controller;

use App\Security\RSAService;
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
        return $this->render('search.html.twig', array_merge($this->defaultModel(), array('db' => $result, 'searchKey' => $search)));
    }

    /**
     * @Route("/search_shared", name="search_shared")
     * @Security("has_role('ROLE_USER')")
     */
    public function searchSharedAction(Request $request, RSAService $RSAService)
    {
        $search = html_entity_decode($request->request->get('search'));
        $result = array();
        if ( !empty($search) ) {
            $token = $this->get('security.token_storage')->getToken();
            $sharedItems = $RSAService->getSharedItems($token);
            foreach ( $sharedItems as $item ) {
                if (stristr($item->getName(), $search)) {
                    $result[] = $item;
                }
            }
        }
        return $this->render('searchAjax.html.twig', array_merge($this->defaultModel(), array('db' => $result)));
    }
}
