<?php
namespace App\Controller;

use App\Entity\DatabaseRow;
use App\Entity\SharedPassword;
use App\Entity\User;
use App\Security\DatabaseService;
use App\Security\RSAService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class CRUDController extends AbstractDatabaseController
{
    private $ENTITY_NAMESPACE = 'App\Entity';
    private $RSAService;

    public function __construct(DatabaseService $databaseService, RSAService $RSAService, EntityManagerInterface $entityManager)
    {
        parent::__construct($databaseService, $entityManager);
        $this->RSAService = $RSAService;
    }

    /**
     * @Route("/get", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function readAction(Request $request)
    {
        $id = $request->query->get('id');
        $row = $this->_getRow($id);
        if ($row == null) {
            return $this->createNotFoundResponse();
        }
        return $this->render('_types/' . $row->getType() . '.html.twig', array_merge($this->defaultModel(), array('entry' => $row)));
    }

    /**
     * @Route("/getSharedItem", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function getSharedItem(Request $request, RSAService $RSAService)
    {
        $id = $request->query->get('id');
        $row = $RSAService->getSharedItem($id, $this->get('security.token_storage')->getToken());

        return $this->render('_types/' . $row->getType() . '.html.twig', array_merge($this->defaultModel(), array('entry' => $row, 'sharedItem'=> true)));
    }

    /**
     * @Route("/add/{type}/{category}", name="add", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function formAddAction($type, $category)
    {
        $category = base64_decode($category . '==');
        if ($category == 'default') {
            $category = '';
        }
        if (!class_exists($this->ENTITY_NAMESPACE . '\Database' . $type . 'Row')) {
            return $this->createBadRequestResponse();
        }
        $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ['username' => 'ASC']);
        unset($users[array_search($this->getUser(), $users)]);
        return $this->render('_types/' . $type . 'Form.html.twig', array('entry' => array(), 'categories' => $this->_getCategories(), 'category' => $category, 'users' => $users, 'csrf_token' => $this->generateToken()));
    }

    /**
     * @Route("/edit", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function formEditAction(Request $request)
    {
        $id = $request->query->get('id');
        $row = $this->_getRow($id);
        if ($row == null) {
            return $this->createNotFoundResponse();
        }

        $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ['username' => 'ASC']);
        unset($users[array_search($this->getUser(), $users)]);
        return $this->render('_types/' . $row->getType() . 'Form.html.twig', array('entry' => $row, 'categories' => $this->_getCategories(), 'category' => $row->getCategory(), 'users' => $users, 'csrf_token' => $this->generateToken()));
    }

    /**
     * @Route("/delete", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function deleteAction(Request $request)
    {
        if (!$this->validateToken($request->query->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }
        $id = $request->query->get('id');

        $row = $this->_getRow($id);
        $category = $row->getCategory();

        $this->_deleteRow($id);

        if (!empty($category)) {
            return $this->redirectToRoute('category', array('categorySlug' => str_replace('=', '', base64_encode($category))));
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/save", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function saveAction(Request $request)
    {
        if (!$this->validateToken($request->request->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }

        $id = $request->request->get('id');

        $row = null;
        if ($id != null) {
            $row = $this->_getRow($id);
            $isNew = false;
        }
        if ($row == null) {
            $type = $request->request->get('type');
            $class = $this->ENTITY_NAMESPACE . '\Database' . $type . 'Row';
            if (!class_exists($class)) {
                return $this->createBadRequestResponse();
            }
            $row = new $class();
            $isNew = true;
        }

        $row->save($request);

        $this->handleShares($row, $request->request->has('share') ? $request->request->get('share') : array());

        if ($isNew) {
            $this->_addToDatabase($row);
        } else {
            $this->_updateDatabase($row);
        }


        if (!empty($row->getCategory())) {
            $category = $row->getCategory();
            return $this->redirectToRoute('category', array('categorySlug' => str_replace('=', '', base64_encode($category))));
        }
        return $this->redirectToRoute('home');
    }

    private function handleShares(DatabaseRow $row, $newShares)
    {
        foreach ($newShares as $shareUsername) {
            $this->sharePassword($row, $shareUsername);
        }
        $shared = $row->getAttribute('shares');
        if ($shared == null) {
            $shared = new \stdClass;
        }
        foreach ($shared as $sharedUser => $sharedId) {
            if (!in_array($sharedUser, $newShares)) {
                $share = $this->getDoctrine()->getRepository(SharedPassword::class)->find($sharedId);
                if ($share == null) {
                    unset($shared->$sharedUser);
                } elseif ($share->getOrigin() == $this->getUser()) {
                    $this->getDoctrine()->getManager()->remove($share);
                    unset($shared->$sharedUser);
                }
            }
        }
        $row->updateAttribute('shares', $shared);

        $this->getDoctrine()->getManager()->flush();
    }

    private function sharePassword(DatabaseRow $row, $username)
    {
        $shares = $row->getAttribute('shares');
        if ($shares == null) {
            $shares = new \stdClass;
        }
        if (array_key_exists($username, $shares)) {
            $share = $this->getDoctrine()->getRepository(SharedPassword::class)->find($shares->$username);
            if ($share->getOrigin() != $this->getUser()) {
                $share = new SharedPassword();
            }
        } else {
            $share = new SharedPassword();
        }

        $share->setOrigin($this->getUser());
        $receiver = $this->getDoctrine()->getRepository(User::class)->find($username);
        $share->setReceiver($receiver);

        $encryptedData = $this->RSAService->encrypt($row, $receiver);
        $share->setEncryptedData($encryptedData);

        $this->getDoctrine()->getManager()->persist($share);
        $this->getDoctrine()->getManager()->flush();

        $shares->$username = $share->getId();
        $row->updateAttribute('shares', $shares);
    }
}
