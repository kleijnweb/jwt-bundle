<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Tests\Functional;

use KleijnWeb\JwtBundle\Tests\Authenticator\AuthenticatorTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class FunctionalTest extends WebTestCase
{
    // @codingStandardsIgnoreStart
    const PSK_TOKEN = AuthenticatorTest::TEST_TOKEN;
    const HMAC_TOKEN = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleVR3byJ9.eyJwcm4iOiJqb2huIiwiaXNzIjoiaHR0cDovL2FwaS5zZXJ2ZXIyLmNvbS9vYXV0aDIvdG9rZW4ifQ.vdGhD5E4Ibj2Tndlh_0pPgJsOuRUpAn1QYu5miB6qwjrXhKCicuTKOuC9x2_2ErUOApv5KiblYds_gcWONdGKx1tQyQa1dsuhrkiVn_VJAsaaix8nJiHAuNv-ukm8mnSWJoVuOcTQIQG8IaupviyphEAEdjrm9QQhvzERgdFUT4bdCdfywrC37oYEAH5bHpiiUK2UzyNuUIHwOP_gWODodbEWRJOxtefwJ_vdpqHvSZzyW7Vei4mCtr2vE1k2qBvG_Qjw2ebLfEdX58k6-eYa7phle9hYjA_q-I8Y-S1ulBiVf_tpvayk8-4lWup9Wbg_BT2vDJOidQgM4l9jV9QHg';
    const DYN_HMAC_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleVRocmVlIn0.eyJwcm4iOiJqb2UiLCJpc3MiOiJodHRwOi8vYXBpLnNlcnZlcjIuY29tL29hdXRoMi90b2tlbiJ9.fv9yrTk3AnPTle_ikBY2EjIFhb1xaxKO4-Vop2AxnME';
    // @codingStandardsIgnoreEnd

    /**
     * @test
     */
    public function canGetUnsecuredContentWithoutToken()
    {
        $this->assertSame(
            'UNSECURED CONTENT',
            $this->makeRequest('/unsecured')
        );
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function cannotGetSecuredContentWithoutToken()
    {
        $this->makeRequest('/secured');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function cannotGetSecuredContentWitInvalidToken()
    {
        $this->makeRequest('/secured', 'foo');
    }

    /**
     * @test
     */
    public function canGetSecuredContentWitValidPskToken()
    {
        $this->assertSame(
            'SECURED CONTENT',
            $this->makeRequest('/secured', self::PSK_TOKEN)
        );
    }

    /**
     * @test
     */
    public function canGetSecuredContentWitValidHmacToken()
    {
        $this->assertSame(
            'SECURED CONTENT',
            $this->makeRequest('/secured', self::HMAC_TOKEN)
        );
    }

    /**
     * @test
     */
    public function canGetSecuredContentWithSecretLoader()
    {
        $this->assertSame(
            'CONTENT SECURED WITH SECRET LOADER',
            $this->makeRequest('/secured-with-secret-loader', self::DYN_HMAC_TOKEN)
        );
    }

    /**
     * @param string $url
     * @param string $token
     *
     * @return string
     */
    private function makeRequest($url, $token = null)
    {
        $client = $this->createClient();
        $server = [];
        if ($token) {
            $server = ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
        }
        $client->request('GET', $url, $parameters = [], $files = [], $server);

        return $client->getResponse()->getContent();
    }
}
