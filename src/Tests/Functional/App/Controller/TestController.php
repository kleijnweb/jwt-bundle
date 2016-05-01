<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Tests\Functional\App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class TestController
{
    /**
     * @return Response
     */
    public function secureAction()
    {
        return new Response('SECURED CONTENT');
    }

    /**
     * @return Response
     */
    public function securedWithSecretLoaderAction()
    {
        return new Response('CONTENT SECURED WITH SECRET LOADER');
    }

    /**
     * @return Response
     */
    public function unsecuredAction()
    {
        return new Response('UNSECURED CONTENT');
    }
}
