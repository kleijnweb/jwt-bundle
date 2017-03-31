<?php declare(strict_types = 1);
namespace KleijnWeb\JwtBundle\User;

interface UserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{
    /**
     * @param array $roles
     * @return mixed
     */
    public function setRoles($roles);

    public function addRole($role);

    public function removeRole($role);
}
