<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Authenticator;

use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use KleijnWeb\JwtBundle\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class Authenticator implements SimplePreAuthenticatorInterface
{
    /**
     * @var JwtKey[]
     */
    private $keys = [];

    /**
     * @param JwtKey[] $keys
     */
    public function __construct(array $keys)
    {
        foreach ($keys as $key) {
            $this->keys[$key->getId()] = $key;
        }
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
     * @param Request $request
     * @param string  $providerKey
     *
     * @return PreAuthenticatedToken
     */
    public function createToken(Request $request, $providerKey)
    {
        $tokenString = $request->headers->get('Authorization');

        if (0 === strpos((string)$tokenString, 'Bearer ')) {
            $tokenString = substr($tokenString, 7);
        }

        if (!$tokenString) {
            throw new BadCredentialsException('No API key found');
        }

        try {
            $token = new JwtToken($tokenString);
            $key   = $this->getKeyById($token->getKeyId());
            $key->validateToken($token);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid key', 0, $e);
        }

        return new PreAuthenticatedToken('anon.', $token, $providerKey);
    }

    /**
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param string                $providerKey
     *
     * @return PreAuthenticatedToken
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        /** @var $jwtToken JwtToken */
        if (!($jwtToken = $token->getCredentials()) instanceof JwtToken) {
            throw new \UnexpectedValueException("Expected credentials to be a JwtToken object");
        }

        $user = $userProvider->loadUserByUsername($jwtToken->getSubject());

        if ($user instanceof UserInterface) {
            $user = $this->setUserRolesFromAudienceClaims($user, $token);
        }

        return new PreAuthenticatedToken($user, $token, $providerKey, $user->getRoles());
    }

    /**
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * @param UserInterface  $user
     * @param TokenInterface $token
     *
     * @return UserInterface
     */
    public function setUserRolesFromAudienceClaims(UserInterface $user, TokenInterface $token)
    {
        /** @var JwtToken $credentials */
        $credentials = $token->getCredentials();

        foreach ($credentials->getClaims() as $claimKey => $claimValue) {
            if ($claimKey === 'aud') {
                if (is_array($claimValue)) {
                    foreach ($claimValue as $role) {
                        $user->addRole("ROLE_" . strtoupper($role));
                    }
                } elseif (is_string($claimValue)) {
                    $user->addRole("ROLE_" . strtoupper($claimValue));
                }
            }
        }

        return $user;
    }
}
