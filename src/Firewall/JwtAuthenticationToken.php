<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Firewall;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class JwtAuthenticationToken extends AbstractToken
{
    /**
     * @var string
     */
    private $tokenString;

    /**
     * @param array  $roles
     * @param string $tokenString
     */
    public function __construct(array $roles = [], string $tokenString = null)
    {
        $this->tokenString = $tokenString;
        parent::__construct($roles);

        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * @return string
     */
    public function getCredentials()
    {
        return $this->tokenString;
    }

    public function eraseCredentials()
    {
        $this->tokenString = '';

        parent::eraseCredentials();
    }
}