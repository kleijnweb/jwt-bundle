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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Request $request
     *
     * @return Response
     */
    public function secureAction(Request $request)
    {
        return new Response('SECURED CONTENT');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Request $request
     *
     * @return Response
     */
    public function unsecureAction(Request $request)
    {
        return new Response('UNSECURED CONTENT');
    }
}
