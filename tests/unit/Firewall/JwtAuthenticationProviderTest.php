<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Jwt;

use KleijnWeb\JwtBundle\Authentication\JwtAuthenticationProvider;
use KleijnWeb\JwtBundle\Authentication\JwtAuthenticationToken;
use KleijnWeb\JwtBundle\Jwt\JwtKey;
use KleijnWeb\JwtBundle\Jwt\JwtToken;
use KleijnWeb\JwtBundle\User\JwtUserProvider;
use KleijnWeb\JwtBundle\User\UnsafeGroupsUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    const ISSUER = 'http://api.server1.com/oauth2/token';
    const SECRET = 'A Pre-Shared Key';

    // @codingStandardsIgnoreStart
    /**
     * Created using jwt.io
     */
    const TEST_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImtleU9uZSJ9.eyJwcm4iOiJqb2huIiwiaXNzIjoiaHR0cDovL2FwaS5zZXJ2ZXIxLmNvbS9vYXV0aDIvdG9rZW4ifQ._jXjAWMzwwG1v5N3ZOEUoLGSINtmwLsvQdfYkYAcWiY';
    // @codingStandardsIgnoreEnd

    /**
     * @var array
     */
    private static $keyConfig = [
        'keyOne' =>
            [
                'issuer' => self::ISSUER,
                'secret' => self::SECRET,
                'type'   => 'HS256',
            ],
        'keyTwo' =>
            [
                'issuer' => self::ISSUER,
                'type'   => 'RS256',
                'secret' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCqGKukO1De7zhZj6+H0qtjTkVxwTCpvKe4eCZ0F',
            ],
    ];

    /**
     * @var JwtKey[]
     */
    private $keys = [];

    /**
     * @var UserProviderInterface
     */
    private $standardUserProviderMock;

    protected function setUp()
    {
        foreach (self::$keyConfig as $keyId => $config) {
            $config['kid']      = $keyId;
            $this->keys[$keyId] = new JwtKey($config);
        }

        $this->standardUserProviderMock = $this->getMockForAbstractClass(UserProviderInterface::class);
    }

    /**
     * @test
     */
    public function getGetKeysUsingIndexesInConfig()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);

        $this->assertInstanceOf(JwtKey::class, $jwtAuthenticationProvider->getKeyById('keyOne'));
        $this->assertInstanceOf(JwtKey::class, $jwtAuthenticationProvider->getKeyById('keyTwo'));
    }

    /**
     * @test
     */
    public function willGetSingleKeyWhenKeyIdIsNull()
    {
        $config = $this->keys;
        unset($config['keyTwo']);

        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $config);

        $this->assertInstanceOf(JwtKey::class, $jwtAuthenticationProvider->getKeyById(null));
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function willFailWhenTryingToGetKeyWithoutIdWhenThereAreMoreThanOne()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);

        $this->assertInstanceOf(JwtKey::class, $jwtAuthenticationProvider->getKeyById(null));
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function willFailWhenTryingToGetUnknownKey()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);

        $this->assertInstanceOf(JwtKey::class, $jwtAuthenticationProvider->getKeyById('blah'));
    }

    /**
     * @test
     */
    public function supportsJwtAuthenticationToken()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);

        $this->assertTrue($jwtAuthenticationProvider->supports(new JwtAuthenticationToken()));
    }

    /**
     * @test
     */
    public function doesNotSupportAnonToken()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);

        $this->assertFalse($jwtAuthenticationProvider->supports(new AnonymousToken('secret', 'john')));
    }

    /**
     * @test
     */
    public function authenticateTokenWillThrowExceptionWhenTokenUnsupportedType()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $anonToken                 = new PreAuthenticatedToken('foo', '', 'myprovider');

        $this->expectException(\LogicException::class);

        $jwtAuthenticationProvider->authenticate($anonToken);
    }

    /**
     * @test
     */
    public function authenticateTokenWillSetUserFetchedFromUserProviderOnToken()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], self::TEST_TOKEN);

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->standardUserProviderMock;
        $mock
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('john')
            ->willReturn(new User('john', 'hi there'));

        $jwtAuthenticationProvider->authenticate($authToken);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function authenticateTokenWillFailWhenTokenStringInvalid()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], 'invalid');

        $this->expectException(BadCredentialsException::class);

        $jwtAuthenticationProvider->authenticate($authToken);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function authenticateTokenWillWillNotCallUserProviderWhenTokenStringInvalid()
    {
        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], 'invalid');

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->standardUserProviderMock;
        $mock
            ->expects($this->never())
            ->method('loadUserByUsername')
            ->with('john')
            ->willReturn(new User('john', 'hi there'));

        $this->expectException(BadCredentialsException::class);

        $jwtAuthenticationProvider->authenticate($authToken);
    }

    /**
     * @test
     */
    public function willSetClaimsOnJwtUserProvider()
    {
        $userProvider = $this->getMockBuilder(JwtUserProvider::class)->disableOriginalConstructor()->getMock();

        $jwtAuthenticationProvider = new JwtAuthenticationProvider($userProvider, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], self::TEST_TOKEN);

        $user = $this->getMockBuilder(UnsafeGroupsUserInterface::class)->getMockForAbstractClass();

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $userProvider
            ->expects($this->once())
            ->method('setClaimsUsingToken')
            ->with($this->isInstanceOf(JwtToken::class));

        $user->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_GUESTS']);

        $jwtAuthenticationProvider->authenticate($authToken);
    }

    /**
     * @deprecated
     * @test
     */
    public function willAddRolesFromAudienceClaimsInToken()
    {
        $token = $this->createToken(['sub' => 'john', 'aud' => 'guests', 'iss' => self::ISSUER]);

        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], $token->getTokenString());

        $user = $this->getMockBuilder(UnsafeGroupsUserInterface::class)->getMockForAbstractClass();

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->standardUserProviderMock;
        $mock
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $user
            ->expects($this->once())
            ->method('addRole');

        $user->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_GUESTS']);

        $jwtAuthenticationProvider->authenticate($authToken);
    }

    /**
     * @deprecated
     * @test
     */
    public function willAddMultipleRolesFromAudienceClaimsInToken()
    {
        $token = $this->createToken(['sub' => 'john', 'aud' => ['guests', 'users'], 'iss' => self::ISSUER]);

        $jwtAuthenticationProvider = new JwtAuthenticationProvider($this->standardUserProviderMock, $this->keys);
        $authToken                 = new JwtAuthenticationToken([], $token->getTokenString());

        $user = $this->getMockBuilder(UnsafeGroupsUserInterface::class)->getMockForAbstractClass();

        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->standardUserProviderMock;
        $mock
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $user->expects($this->exactly(2))
            ->method('addRole');

        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn(['ROLE_GUESTS', 'ROLE_USERS']);

        $jwtAuthenticationProvider->authenticate($authToken);
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
            'secret' => self::SECRET
        ]);
    }
}
