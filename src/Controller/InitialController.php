<?php
namespace App\Controller;

use App\Security\Authentication\MfaService;
use App\Security\Authentication\UsernameKeyToken;
use App\Security\Authentication\UserProvider;
use App\Security\EncryptionService;
use App\Security\RSAService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class InitialController extends AbstractController
{
    /**
     * @Route("/first_login", name="first_login")
     * @Template("first_login.html.twig")
     */
    public function firstLoginAction(Request $request, UserProvider $userProvider, EncryptionService $encryptionService, RSAService $RSAService, MfaService $mfaService)
    {
        if (!$request->query->has('username')) {
            return $this->createBadRequestResponse('Parameter username is missing.');
        }
        $username = $request->query->get('username');

        try {
            $user = $userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            throw $this->createNotFoundException('');
        }
        if ($user->getSafeDatabase() != null) {
            throw $this->createNotFoundException('');
        }

        $password = $this->databaseService->createInitialDatabase($user);

        $key = $encryptionService->generateKey($password, $user->getSafeDatabase()->getSalt(), $user->getSafeDatabase()->getKeyIterations());

        $token = new UsernameKeyToken($user, $key, 'internal', $user->getRoles());
        $RSAService->generateKeys($token);

        $url = $mfaService->createMfaURL($user);
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
        ]);
        $qrcode = new QRCode($options);

        return array('username' => $user->getUsername(), 'password' => $password, 'qrCode' => $qrcode->render($url), 'url' => $url);
    }

}
