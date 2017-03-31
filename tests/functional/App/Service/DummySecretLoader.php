<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Tests\Functional\App\Service;

use KleijnWeb\JwtBundle\Authenticator\JwtToken;
use KleijnWeb\JwtBundle\Authenticator\SecretLoader;

class DummySecretLoader implements SecretLoader
{
    /**
     * @param JwtToken $token
     *
     * @return string
     */
    public function load(JwtToken $token)
    {
        return 'externally loaded secret';
    }
}