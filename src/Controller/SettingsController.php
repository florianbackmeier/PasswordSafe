<?php
namespace App\Controller;

use App\Security\Authentication\MfaService;
use App\Security\Authentication\UsernameKeyToken;
use App\Security\EncryptionService;
use App\Security\RSAService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends AbstractController
{
    /**
     * @Route("/settings", name="settings")
     * @Template("settings/overview.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsAction()
    {
        return array_merge($this->defaultModel(), array());
    }

    /**
     * @Route("/settings/password", name="settingsPassword", methods={"GET"})
     * @Template("settings/password.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsPasswordAction()
    {
        $token = $this->get('security.token_storage')->getToken();
        $db = $token->getUser()->getSafeDatabase();
        return array_merge($this->defaultModel(), array('keyiterations' => $db->getKeyIterations()));
    }

    /**
     * @Route("/settings/password", methods={"POST"})
     * @Security("has_role('ROLE_USER')")
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
     * @Security("has_role('ROLE_USER')")
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
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsRSA(RSAService $RSAService)
    {
        $RSAService->generateKeys($this->get('security.token_storage')->getToken());

        return $this->redirectToRoute('settings');
    }

    /**
     * @Route("/settings/personalization", name="settingsPersonalization", methods={"GET"})
     * @Template("settings/personalization.html.twig")
     * @Security("has_role('ROLE_USER')")
     */
    public function settingsPersonalizationAction()
    {
        $token = $this->get('security.token_storage')->getToken();
        $meta = $this->databaseService->getMeta($token);
        return array_merge($this->defaultModel(), array('meta' => $meta));
    }

    /**
     * @Route("/settings/personalization", methods={"POST"})
     * @Security("has_role('ROLE_USER')")
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

}
