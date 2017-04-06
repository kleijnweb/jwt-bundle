<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Firewall;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
use KleijnWeb\JwtBundle\Authentication\JwtAuthenticationToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class JwtAuthenticationListener implements ListenerInterface
{
    const HEADER_AUTH = 'Authorization';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var string
     */
    private $header;

    /**
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param string                         $header
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, string $header = self::HEADER_AUTH)
    {
        $this->tokenStorage          = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->header                = $header;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        if (!$token = $this->createToken($event->getRequest())) {
            return;
        }

        $this->tokenStorage->setToken($this->authenticationManager->authenticate($token));
    }

    /**
     * @param Request $request
     *
     * @return JwtAuthenticationToken|null
     */
    public function createToken(Request $request)
    {
        $tokenString = $request->headers->get($this->header);

        if ($this->header == self::HEADER_AUTH && 0 === strpos((string)$tokenString, 'Bearer ')) {
            $tokenString = substr($tokenString, 7);
        }

        if (!$tokenString) {
            return null;
        }

        return new JwtAuthenticationToken([], $tokenString);
    }
}
