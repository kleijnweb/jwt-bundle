<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtAuthenticatedToken extends AbstractToken
{
    /**
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user)
    {
        parent::__construct($user->getRoles());

        $this->setAuthenticated(count($this->getRoles()) > 0);

        $this->setUser($user);
    }

    /**
     * Always returns empty string
     *
     * @return string
     */
    public function getCredentials()
    {
        return '';
    }
}
