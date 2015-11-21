<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle;

use KleijnWeb\JwtBundle\DependencyInjection\KleijnWebJwtExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class KleijnWebJwtBundle extends Bundle
{
    /**
     * @return string The Bundle namespace
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @return ExtensionInterface
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new KleijnWebJwtExtension();
        }

        return $this->extension;
    }
}
