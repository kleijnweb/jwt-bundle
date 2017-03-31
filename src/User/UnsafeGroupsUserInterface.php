<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\User;

interface UnsafeGroupsUserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{
    /**
     * @param array $roles
     * @return mixed
     */
    public function setRoles(array $roles);

    /**
     * @param mixed $role
     * @return mixed
     */
    public function addRole($role);

    /**
     * @param $role
     * @return mixed
     */
    public function removeRole($role);
}
