<?php
namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends AbstractController
{
    /**
     * @Route("/export", name="export")
     * @Security("has_role('ROLE_USER')")
     */
    public function exportAction(Request $request)
    {
        if (!$this->validateToken($request->query->get('csrf_token'))) {
            return $this->createForbiddenResponse();
        }

        $tplLocator = new FileLocator(array($this->container->getParameter('kernel.root_dir').'/../templates/export'));
        $exportFile = $tplLocator->locate('backup.php.tpl', null, true);
        $export = file_get_contents($exportFile);

        $token = $this->get('security.token_storage')->getToken();
        $db = $token->getUser()->getSafeDatabase();
        $data = bin2hex($db->getData());
        $salt = bin2hex($db->getSalt());
        $iterations = $db->getKeyIterations();

        $export = str_replace('${data}', $data, $export);
        $export = str_replace('${salt}', $salt, $export);
        $export = str_replace('${iterations}', $iterations, $export);

        $srcLocator = new FileLocator(array($this->container->getParameter('kernel.root_dir')));
        $encryptionFile = $srcLocator->locate('Security/EncryptionService.php', null, true);
        $service = file_get_contents($encryptionFile);
        $service = implode("\n", array_slice(explode("\n", $service), 2));
        $export = str_replace('${service}', $service, $export);

        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="passwordSafe' . date('Ymd') . '.php";');
        $response->headers->set('Content-length', strlen($export));


        return $response->setContent($export);
    }


}
