<?php
namespace App\Controller;

use App\Security\DatabaseService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;

class AbstractController extends SymfonyController
{

    protected $databaseService;

    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    protected function defaultModel()
    {
        $token = $this->get('security.token_storage')->getToken();

        $categories = $this->databaseService->getCategories($token);
        natcasesort($categories);
        return array('categories' => $categories, 'csrf_token' => $this->generateToken());
    }

    protected function generateToken()
    {
        $csrf = $this->get('security.csrf.token_manager');
        $token = $csrf->getToken('');
        return $token;
    }

    protected function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }
        $csrf = $this->get('security.csrf.token_manager');
        return $csrf->isTokenValid(new CsrfToken('', $token));
    }

    protected function createForbiddenResponse()
    {
        return $this->createResponse(Response::HTTP_FORBIDDEN);
    }

    protected function createNotFoundResponse()
    {
        return $this->createResponse(Response::HTTP_NOT_FOUND);
    }

    protected function createBadRequestResponse()
    {
        return $this->createResponse(Response::HTTP_BAD_REQUEST);
    }

    protected function createResponse($status = Response::HTTP_OK)
    {
        $response = new Response();
        $response->setStatusCode($status);
        return $response;
    }
}
