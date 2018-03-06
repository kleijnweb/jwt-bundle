<?php
//@codingStandardsIgnoreStart
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;


class TestKernel extends Kernel
{
//@codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new KleijnWeb\JwtBundle\KleijnWebJwtBundle(),
            new KleijnWeb\JwtBundle\Tests\Functional\App\TestBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle()
        ];

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config_' . $this->getEnvironment() .'.yml');
    }
}
