<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\DependencyInjection;

use KleijnWeb\JwtBundle\Jwt\JwtKey;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class KleijnWebJwtExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $keys = [];

        foreach ($config['keys'] as $keyId => $keyConfig) {
            $keyConfig['kid'] = $keyId;
            $keyDefinition    = new Definition('jwt.keys.' . $keyId);
            $keyDefinition->setClass(JwtKey::class);

            if (isset($keyConfig['loader'])) {
                $keyConfig['loader'] = new Reference($keyConfig['loader']);
            }
            $keyDefinition->addArgument($keyConfig);
            $keys[] = $keyDefinition;
        }

        $container->getDefinition('jwt.security.authentication.provider')->addArgument($keys);
        $container->getDefinition('jwt.token_issuer')->addArgument($keys);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return "jwt";
    }
}
