<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Authentication;

use KleijnWeb\JwtBundle\Jwt\JwtKey;
use KleijnWeb\JwtBundle\Jwt\JwtToken;
use KleijnWeb\JwtBundle\User\JwtUserProvider;
use KleijnWeb\JwtBundle\User\UnsafeGroupsUserInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtAuthenticationProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var JwtKey[]
     */
    private $keys = [];

    /**
     * @param UserProviderInterface $userProvider
     * @param JwtKey[]              $keys
     */
    public function __construct(UserProviderInterface $userProvider, array $keys)
    {
        foreach ($keys as $key) {
            $this->keys[$key->getId()] = $key;
        }
        $this->userProvider = $userProvider;
    }

    /**
     * @param string|null $id
     *
     * @return JwtKey
     */
    public function getKeyById(string $id = null)
    {
        if ($id) {
            if (!isset($this->keys[$id])) {
                throw new AuthenticationException("Unknown 'kid' $id");
            }

            return $this->keys[$id];
        }
        if (count($this->keys) > 1) {
            throw new AuthenticationException("Missing 'kid'");
        }

        return current($this->keys);
    }

    /**
     * @param TokenInterface $token
     * @return JwtAuthenticatedToken
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new \LogicException(
                sprintf("Token of type '%s' not supported by '%s'", get_class($token), get_class($this))
            );
        }
        try {
            $jwtToken = new JwtToken($token->getCredentials());
            $key      = $this->getKeyById($jwtToken->getKeyId());
            $key->validateToken($jwtToken);
        } catch (\Exception $e) {
            throw new BadCredentialsException('Invalid JWT token', 0, $e);
        }

        $username = $jwtToken->getSubject();

        if ($this->userProvider instanceof JwtUserProvider) {
            // Not ideal, sequential coupling
            $this->userProvider->setClaimsUsingToken($jwtToken);
        }

        $user = $this->userProvider->loadUserByUsername($username);

        if (!$this->userProvider instanceof JwtUserProvider && $user instanceof UnsafeGroupsUserInterface) {
            $this->setUserRolesFromAudienceClaims($user, $jwtToken->getClaims());
        }

        return new JwtAuthenticatedToken($user);
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof JwtAuthenticationToken;
    }

    /**
     * @param UnsafeGroupsUserInterface $user
     * @param array                     $claims
     */
    private function setUserRolesFromAudienceClaims(UnsafeGroupsUserInterface $user, array $claims)
    {
        foreach (JwtUserProvider::getRolesFromAudienceClaims($claims) as $role) {
            $user->addRole($role);
        }
    }
}

