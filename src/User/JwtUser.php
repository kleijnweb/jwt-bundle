<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtUser implements UserInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var (Role|string)[]
     */
    private $roles = [];

    /**
     * @var array
     */
    private $claims = [];

    /**
     * @param string $username
     * @param array  $roles
     * @param array  $claims
     */
    public function __construct(string $username, array $roles, array $claims)
    {
        $this->username = $username;
        $this->roles    = $roles;
        $this->claims   = $claims;
    }

    /**
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return array
     */
    public function getClaims(): array
    {
        return $this->claims;
    }

    public function eraseCredentials()
    {
        //NOOP
    }

    public function getPassword()
    {
        return '';
    }

    public function getSalt()
    {
        return '';
    }
}
