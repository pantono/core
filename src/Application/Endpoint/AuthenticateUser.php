<?php

namespace Pantono\Core\Application\Endpoint;

use League\Fractal\Resource\ResourceAbstract;
use Pantono\Authentication\UserAuthentication;
use Pantono\Core\Router\Endpoint\AbstractEndpoint;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pantono\Authentication\Exception\AccessDeniedException;
use Pantono\Authentication\Exception\PasswordNeedsRehashException;
use Symfony\Component\HttpFoundation\Session\Session;
use League\Fractal\Resource\Item;
use Pantono\Core\Decorator\GenericArrayDecorator;

class AuthenticateUser extends AbstractEndpoint
{
    private UserAuthentication $userAuthentication;
    private Session $session;

    public function __construct(UserAuthentication $userAuthentication, Session $session)
    {
        $this->userAuthentication = $userAuthentication;
        $this->session = $session;
    }

    public function processRequest(ParameterBag $parameters): ResourceAbstract|array
    {
        $emailAddress = $parameters->get('email_address');
        $password = $parameters->get('password');

        $user = $this->userAuthentication->getUserByEmailAddress($emailAddress);
        if ($user === null) {
            throw new NotFoundHttpException('User does not exist');
        }

        try {
            if ($user->authenticate($password) === false) {
                throw new AccessDeniedException('Invalid username/password');
            }
        } catch (PasswordNeedsRehashException $e) {
            $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
            $this->userAuthentication->saveUser($user);
        }
        $token = $this->userAuthentication->addTokenForUser($user);
        $this->session->set('api_token', $token->getToken());

        return new Item(['success' => true], new GenericArrayDecorator());
    }
}
