<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Authenticator;

use KleijnWeb\JwtBundle\Authenticator\Authenticator;
use KleijnWeb\JwtBundle\Authenticator\JwtKey;
use KleijnWeb\JwtBundle\Authenticator\JwtToken;
use KleijnWeb\JwtBundle\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\User\User;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class AuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart

    /**
     * Created using jwt.io
     */
    const TEST_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleU9uZSJ9.eyJwcm4iOiJqb2huIiwiaXNzIjoiaHR0cDovL2FwaS5zZXJ2ZXIxLmNvbS9vYXV0aDIvdG9rZW4ifQ._jXjAWMzwwG1v5N3ZOEUoLGSINtmwLsvQdfYkYAcWiY';

    const JKEY_CLASS = 'KleijnWeb\JwtBundle\Authenticator\JwtKey';

    /**
     * @var array
     */
    private static $keyConfig = [
        'keyOne' =>
            [
                'issuer' => 'http://api.server1.com/oauth2/token',
                'secret' => 'A Pre-Shared Key',
                'type'   => 'HS256',
            ],
        'keyTwo' =>
            [
                'issuer' => 'http://api.server2.com/oauth2/token',
                'type'   => 'RS256',
                'secret' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCqGKukO1De7zhZj6+H0qtjTkVxwTCpvKe4eCZ0F',
            ],
    ];

    // @codingStandardsIgnoreEnd

    /**
     * @var JwtKey[]
     */
    private $keys = [];

    protected function setUp()
    {
        foreach (self::$keyConfig as $keyId => $config) {
            $config['kid']      = $keyId;
            $this->keys[$keyId] = new JwtKey($config);
        }
    }

    /**
     * @test
     */
    public function getGetKeysUsingIndexesInConfig()
    {
        $authenticator = new Authenticator($this->keys);

        $this->assertInstanceOf(self::JKEY_CLASS, $authenticator->getKeyById('keyOne'));
        $this->assertInstanceOf(self::JKEY_CLASS, $authenticator->getKeyById('keyTwo'));
    }

    /**
     * @test
     */
    public function willGetSingleKeyWhenKeyIdIsNull()
    {
        $config = $this->keys;
        unset($config['keyTwo']);

        $authenticator = new Authenticator($config);

        $this->assertInstanceOf(self::JKEY_CLASS, $authenticator->getKeyById(null));
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function willFailWhenTryingToGetKeyWithoutIdWhenThereAreMoreThanOne()
    {
        $authenticator = new Authenticator($this->keys);

        $this->assertInstanceOf(self::JKEY_CLASS, $authenticator->getKeyById(null));
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function willFailWhenTryingToGetUnknownKey()
    {
        $authenticator = new Authenticator($this->keys);

        $this->assertInstanceOf(self::JKEY_CLASS, $authenticator->getKeyById('blah'));
    }

    /**
     * @test
     */
    public function authenticateTokenWillSetUserFetchedFromUserProviderOnToken()
    {
        $jwtToken      = $this->createToken(['sub' => 'john']);
        $authenticator = new Authenticator($this->keys);
        $anonToken     = new PreAuthenticatedToken('foo', $jwtToken, 'myprovider');

        $userProvider = $this->getMockBuilder(
            'Symfony\Component\Security\Core\User\UserProviderInterface'
        )->getMockForAbstractClass();

        $userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('john')
            ->willReturn(new User('john', 'hi there'));

        $authenticator->authenticateToken($anonToken, $userProvider, 'myprovider');
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function authenticateTokenWillFailIfCredentialsAreNotJwtToken()
    {
        $authenticator = new Authenticator($this->keys);
        $anonToken     = new PreAuthenticatedToken('foo', ['sub' => 'john'], 'myprovider');

        $userProvider = $this->getMockBuilder(
            'Symfony\Component\Security\Core\User\UserProviderInterface'
        )->getMockForAbstractClass();

        $authenticator->authenticateToken($anonToken, $userProvider, 'myprovider');
    }

    /**
     * @test
     */
    public function supportsPreAuthToken()
    {
        $authenticator = new Authenticator($this->keys);

        $securityToken = new PreAuthenticatedToken('foo', 'bar', 'myprovider');
        $actual        = $authenticator->supportsToken($securityToken, 'myprovider');
        $this->assertTrue($actual);
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function willFailWhenApiKeyNotFoundInHeader()
    {
        $authenticator = new Authenticator($this->keys);
        $request       = new Request();
        $authenticator->createToken($request, 'myprovider');
    }

    /**
     * @test
     */
    public function canGetAnonTokenWithClaims()
    {
        $authenticator = new Authenticator($this->keys);
        $request       = new Request();
        $request->headers->set('Authorization', 'Bearer ' . self::TEST_TOKEN);
        $token = $authenticator->createToken($request, 'myprovider');

        $expected = new JwtToken(self::TEST_TOKEN);
        $this->assertEquals($expected, $token->getCredentials());
    }

    /**
     * @test
     */
    public function willAddRolesFromAudienceClaimsInToken()
    {
        $authenticator = new Authenticator($this->keys);
        $token         = $this->createToken(['sub' => 'john', 'aud' => 'guests']);
        $anonToken     = new PreAuthenticatedToken('foo', $token, 'myprovider');

        $user          = $this->getMockBuilder(
            'KleijnWeb\JwtBundle\User\UserInterface'
        )->getMockForAbstractClass();

        $userProvider = $this->getMockBuilder(
            'Symfony\Component\Security\Core\User\UserProviderInterface'
        )->getMockForAbstractClass();

        $userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $user->expects($this->once())
            ->method('addRole')
            ->with('ROLE_GUESTS');

        $user->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_GUESTS']);

        $authenticator->authenticateToken($anonToken, $userProvider, 'myprovider');
    }

    /**
     * @test
     */
    public function willAddMultipleRolesFromAudienceClaimsInToken()
    {
        $authenticator = new Authenticator($this->keys);
        $token         = $this->createToken(['sub' => 'john', 'aud' => ['guests', 'users']]);
        $anonToken     = new PreAuthenticatedToken('foo', $token, 'myprovider');

        $user          = $this->getMockBuilder(
            'KleijnWeb\JwtBundle\User\UserInterface'
        )->getMockForAbstractClass();

        $userProvider = $this->getMockBuilder(
            'Symfony\Component\Security\Core\User\UserProviderInterface'
        )->getMockForAbstractClass();

        $userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $user->expects($this->exactly(2))
            ->method('addRole');

        $user->expects($this->once())
            ->method('getRoles')
            ->willReturn(['guests', 'users']);

        $authenticator->authenticateToken($anonToken, $userProvider, 'myprovider');
    }

    /**
     * @param array $claims
     *
     * @return JwtToken
     */
    private function createToken(array $claims)
    {
        return new JwtToken([
            'header' => [
                'alg' => 'HS256',
                'typ' => 'JWT',
                'kid' => 'keyOne'
            ],
            'claims' => $claims,
            'secret' => 'secret'
        ]);
    }
}
