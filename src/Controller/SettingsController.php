<?php
namespace App\Controller;

use App\Entity\DatabaseUsernamePasswordRow;
use App\Entity\SharedPassword;
use App\Entity\SharedPasswordType;
use App\Entity\User;
use App\Security\Authentication\MfaService;
use App\Security\Authentication\UsernameKeyToken;
use App\Security\DatabaseService;
use App\Security\EncryptionService;
use App\Security\RSAService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends AbstractController
{
    private $RSAService;

    public function __construct(DatabaseService $databaseService, RSAService $RSAService, EntityManagerInterface $entityManager)
    {
        parent::__construct($databaseService, $entityManager);
        $this->RSAService = $RSAService;
    }

    /**
     * @Route("/settings", name="settings")
     * @Template("settings/overview.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsAction()
    {
        return array_merge($this->defaultModel(), array('heritages' => $this->getHeritages()));
    }

    /**
     * @Route("/settings/password", name="settingsPassword", methods={"GET"})
     * @Template("settings/password.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsPasswordAction()
    {
        $token = $this->get('security.token_storage')->getToken();
        $db = $token->getUser()->getSafeDatabase();
        return array_merge($this->defaultModel(), array('keyiterations' => $db->getKeyIterations()));
    }

    /**
     * @Route("/settings/password", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsPasswordActionPost(Request $request, EncryptionService $encryptionService)
    {
        if (!$this->validateToken($request->request->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }
        $token = $this->get('security.token_storage')->getToken();
        $user = $token->getUser();


        $oldPassword = $request->request->get('oldpassword');
        $password = $request->request->get('password');
        $password2 = $request->request->get('password2');
        $iterations = intval($request->request->get('keyiterations'));

        $errors = array();

        $oldKey = $encryptionService->generateKey($oldPassword, $user->getSafeDatabase()->getSalt(), $user->getSafeDatabase()->getKeyIterations());
        if (!$encryptionService->isValidKey($oldKey, $user->getSafeDatabase())) {
            $errors['oldpassword'] = 'Your old password is incorrect.';
        }
        if (empty($password)) {
            $errors['password'] = 'Your password can\'t be empty.';
        }
        if ($password != $password2) {
            $errors['password'] = 'Your passwords do not match.';
        }
        if ($iterations <= 0) {
            $errors['keyiterations'] = 'This number should be greater than null.';
        }

        if (!empty($errors)) {
            $db = $user->getSafeDatabase();
            return $this->render('settings/password.html.twig', array_merge($this->defaultModel(), array('errors' => $errors, 'keyiterations' => $db->getKeyIterations())));
        }

        $em = $this->getDoctrine()->getManager();
        $rows = $this->databaseService->getDatabaseRows($token);
        $db = $user->getSafeDatabase();

        $key = $encryptionService->generateKey($password, $db->getSalt(), $iterations);
        $db->setKeyIterations($iterations);
        $encryptionService->encrypt($key, $db, $rows);

        $user->setSafeDatabase($db);
        $em->persist($db);
        $em->persist($user);
        $em->flush();

        $newToken = new UsernameKeyToken($user, $key, 'default', $user->getRoles());
        $this->get('security.token_storage')->setToken($newToken);

        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/mfa", name="settingsMFA", methods={"GET"})
     * @Template("settings/mfa.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsMFA(MfaService $mfaService)
    {
        $token = $this->get('security.token_storage')->getToken();
        $user = $token->getUser();

        $url = $mfaService->createMfaURL($user);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        ]);
        $qrcode = new QRCode($options);
        return array_merge($this->defaultModel(), array('qrCode' => $qrcode->render($url), 'url' => $url));
    }

    /**
     * @Route("/settings/rsaKeys", name="settingsRegenerateKeys", methods={"GET"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsRSA(RSAService $RSAService)
    {
        $RSAService->generateKeys($this->get('security.token_storage')->getToken());

        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/personalization", name="settingsPersonalization", methods={"GET"})
     * @Template("settings/personalization.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsPersonalizationAction()
    {
        $token = $this->get('security.token_storage')->getToken();
        $meta = $this->databaseService->getMeta($token);
        return array_merge($this->defaultModel(), array('meta' => $meta));
    }

    /**
     * @Route("/settings/personalization", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsPersonalizationActionPost(Request $request)
    {
        if (!$this->validateToken($request->request->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }
        $token = $this->get('security.token_storage')->getToken();

        $startCategory = $request->request->get('startCategory');
        $meta = $this->databaseService->getMeta($token);
        if ( $startCategory === '_' ) {
            $startCategory = '';
        }
        $meta->add('startCategory', $startCategory);

        $this->databaseService->saveMetaRow($token, $meta);

        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/inheritance", name="settingsInheritance", methods={"GET"})
     * @Template("settings/inheritance.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsInheritanceAction()
    {
        $inheritance = $this->getPrivateInheritance();
        if ( !$inheritance ) {
            $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ['username' => 'ASC']);
            unset($users[array_search($this->getUser(), $users)]);
            return array_merge($this->defaultModel(), array('users' => $users, 'csrf_token' => $this->generateToken()));
        } else {
            return array_merge($this->defaultModel(), array('inheritance' => $inheritance, 'csrf_token' => $this->generateToken()));
        }
    }

    /**
     * @Route("/settings/inheritance", methods={"POST"})
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsInheritancePostAction(Request $request) {
        if (!$this->validateToken($request->request->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }

        if ( $request->request->get('task') == 'disableInheritance' ) {
            $inheritance = $this->getPrivateInheritance();

            $this->getDoctrine()->getManager()->remove($inheritance);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('settingsInheritance');
        }

        $share = new SharedPassword();
        $share->setType(SharedPasswordType::INHERITANCE);

        $share->setOrigin($this->getUser());
        $username = $request->request->get('user');
        $receiver = $this->getDoctrine()->getRepository(User::class)->find($username);
        $share->setReceiver($receiver);

        $password = $request->request->get('password');
        $row = new DatabaseUsernamePasswordRow();
        $row->setUsername($this->getUser());
        $row->setPassword($password);

        $encryptedData = $this->RSAService->encrypt($row, $receiver);
        $share->setEncryptedData($encryptedData);

        $attr = new stdClass();
        $attr->email = $request->request->get('email');
        $share->setAttributes($attr);

        $this->getDoctrine()->getManager()->persist($share);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('settingsInheritance');
    }

    /**
     * @Route("/settings/heritages", name="settingsHeritages", methods={"GET"})
     * @Template("settings/heritages.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsHeritagesAction()
    {
        $model = array('requested' => array());

        $heritages = $this->getHeritages();
        foreach ( $heritages as $heritage ) {
            $attr = $heritage->getAttributes();
            if ( isset($attr->requested) && $attr->requested < time() - 5 * 24 * 60 * 60 *60 ) {
                $row = $this->RSAService->getSharedItem($heritage->getId(), $this->get('security.token_storage')->getToken());
                $model['requested'][$heritage->getId()] = $row;

                // Disable MFA for origin
                $origin = $heritage->getOrigin();
                $origin->setMfaKey('');
                $this->getDoctrine()->getManager()->persist($origin);
                $this->getDoctrine()->getManager()->flush();
            }
        }
        $model['heritages'] = $heritages;
        $model['csrf_token'] = $this->generateToken();
        return array_merge($this->defaultModel(), $model);
    }

    /**
     * @Route("/settings/heritages/{username}", name="settingsHeritagesUser", methods={"GET"})
     * @Template("settings/heritages.html.twig")
     * @Security("is_granted('ROLE_USER')")
     */
    public function settingsHeritagesRequestAction($username)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($username);
        $heritages = $this->getHeritages();
        $heritage = array_filter($heritages, function(SharedPassword $h) use ($user) { return $h->getOrigin() == $user; });

        if ( $heritage ) {
            $heritage = $heritage[0];
            $attributes = $heritage->getAttributes();


            $requester = $this->getUser()->getUsername();
            mail($attributes->email, 'PasswordSafe - Trusted access requested',
                "Hello $username\n\r\n\r$requester requested access.\n\r\n\rIf this is not wished by you, please deny the request in the PasswordSafe.\n\r\n\rRegards\n\rPasswordSafe");

            $attributes->requested = time();
            $heritage->setAttributes($attributes);

            $this->getDoctrine()->getManager()->persist($heritage);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('settingsHeritages');
    }

    private function getHeritages() {
        return $this->entityManager->getRepository(SharedPassword::class)->findBy(array('receiver' => $this->getUser(), 'type' => SharedPasswordType::INHERITANCE));
    }

}
