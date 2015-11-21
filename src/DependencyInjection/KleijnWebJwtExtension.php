<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\DependencyInjection;

use KleijnWeb\JwtBundle\Request\ContentDecoder;
use KleijnWeb\JwtBundle\Serializer\SerializationTypeResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

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

        $container->setParameter('swagger.auth.keys', $config['auth']['keys']);

    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return "swagger";
    }
}
