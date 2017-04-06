<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\User;

use KleijnWeb\JwtBundle\Jwt\JwtToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtUserProvider implements UserProviderInterface
{
    const BASE_ROLE = 'IS_AUTHENTICATED_FULLY';

    /**
     * @var array
     */
    private $claims = [];

    /**
     * @deprecated
     * @param array $claims
     * @return array
     */
    public static function getRolesFromAudienceClaims(array $claims)
    {
        return self::extractRoles($claims);
    }

    /**
     * @param JwtToken $token
     */
    public function setClaimsUsingToken(JwtToken $token)
    {
        $this->claims[$token->getSubject()] = $token->getClaims();
    }

    /**
     * @param string $username
     * @return JwtUser
     */
    public function loadUserByUsername($username)
    {
        $claims = $this->getClaims($username);

        $roles = array_merge([self::BASE_ROLE], self::extractRoles($claims));

        return new JwtUser($username, $roles, $claims);
    }

    /**
     * @param UserInterface $user
     * @return JwtUser
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === JwtUser::class;
    }

    /**
     * @param string $username
     * @return array
     */
    private function getClaims(string $username): array
    {
        return $this->claims[$username];
    }

    /**
     * @param array $claims
     * @return array
     */
    private static function extractRoles(array $claims)
    {
        $roles = [];

        foreach ($claims as $claimKey => $claimValue) {
            if ($claimKey === 'aud') {
                if (is_array($claimValue)) {
                    foreach ($claimValue as $role) {
                        $roles[] = "ROLE_" . strtoupper($role);
                    }
                } elseif (is_string($claimValue)) {
                    $roles[] = "ROLE_" . strtoupper($claimValue);
                }
            }
        }

        return $roles;
    }
}
