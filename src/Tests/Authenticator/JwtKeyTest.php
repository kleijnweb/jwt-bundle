<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Authenticator;

use KleijnWeb\JwtBundle\Authenticator\JwtKey;
use KleijnWeb\JwtBundle\Authenticator\JwtToken;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructionWillFailWhenSecretNorLoaderPassed()
    {
        new JwtKey([]);
    }

    /**
     * @test
     */
    public function canOmitSecretWhenPassingLoader()
    {
        $actual   = new JwtKey(['loader' => 'foo']);
        $refl     = new \ReflectionClass($actual);
        $property = $refl->getProperty('secret');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($actual));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function cannotPassBothSecretAndLoader()
    {
        new JwtKey(['loader' => 'foo', 'secret' => 'bar']);
    }

    /**
     * @test
     */
    public function serializingWillClearSecret()
    {
        $key      = new JwtKey(['secret' => 'Buy the book']);
        $actual   = unserialize(serialize($key));
        $refl     = new \ReflectionClass($actual);
        $property = $refl->getProperty('secret');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($actual));
    }

    /**
     * @test
     */
    public function validateTokenWillCallVerifySignatureOnToken()
    {
        $secret = rand();
        $key    = new JwtKey(['secret' => $secret]);
        $key->validateToken($this->createTokenMock($secret, $key));
    }

    /**
     * @test
     */
    public function canLoadSecretFromLoader()
    {
        $secret = rand();
        $token  = $this->createTokenMock($secret);

        $loaderMock = $this->getMockBuilder('KleijnWeb\JwtBundle\Authenticator\SecretLoader')->getMock();
        $loaderMock->expects($this->once())->method('load')->with($token)->willReturn($secret);

        $key = new JwtKey(['loader' => $loaderMock]);

        $key->validateToken($token);
    }

    /**
     * @test
     */
    public function willGetRsaSignatureValidatorWhenTypeIsNotSpecified()
    {
        $key    = new JwtKey(['secret' => 'Buy the book']);
        $actual = $key->getSignatureValidator();
        $this->assertInstanceOf(
            'KleijnWeb\JwtBundle\Authenticator\SignatureValidator\HmacValidator',
            $actual
        );
    }

    /**
     * @test
     */
    public function willGetRsaSignatureValidatorWhenTypeIsRsa()
    {
        $key    = new JwtKey(['secret' => 'Buy the book', 'type' => JwtKey::TYPE_RSA]);
        $actual = $key->getSignatureValidator();
        $this->assertInstanceOf(
            'KleijnWeb\JwtBundle\Authenticator\SignatureValidator\RsaValidator',
            $actual
        );
    }

    /**
     * @test
     */
    public function validationWillFailWhenPrincipleIsMissing()
    {
        $claims = ['prn' => 'joe'];

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims($claims);

        unset($claims['prn']);

        $this->setExpectedException('\InvalidArgumentException');

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims($claims);
    }



    /**
     * @test
     */
    public function validationWillFailWhenSubjectMissing()
    {
        $claims = ['sub' => 'joe'];

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims($claims);

        unset($claims['sub']);

        $this->setExpectedException('\InvalidArgumentException');

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims($claims);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenExpiredByExp()
    {
        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims(['sub' => 'john', 'exp' => time() - 2]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillNotFailWhenExpiredByExpButWithinLeeway()
    {
        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims(['sub' => 'john', 'exp' => time() - 2]);
    }

    /**
     * @test
     */
    public function validationWillFailWhenExpiredByIatAndMinIssueTime()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'minIssueTime' => time() + 2, 'leeway' => 3]);
        $key->validateClaims(['sub' => 'john', 'iat' => time()]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenNotValidYet()
    {
        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims(['sub' => 'john', 'nbf' => time() + 2]);
    }

    /**
     * @test
     */
    public function validationWillFailNotFailWhenNotValidYetButWithinLeeway()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'leeway' => 3]);
        $key->validateClaims(['sub' => 'john', 'nbf' => time() + 2]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenIssuerDoesNotMatch()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'issuer' => 'me']);
        $key->validateClaims(['sub' => 'john', 'iss' => 'you']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenAudienceDoesNotMatch()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => ['me']]);
        $key->validateClaims(['sub' => 'john', 'aud' => 'the neighbours']);
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenIssuerIsConfiguredAndNotInClaims()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'issuer' => 'me']);
        $key->validateClaims(['sub' => 'john']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenMinIssueTimeIsConfiguredAndIatNotInClaims()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'minIssueTime' => time()]);
        $key->validateClaims(['sub' => 'john']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenAudienceIsConfiguredAndNotInClaims()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => [time()]]);
        $key->validateClaims(['sub' => 'john']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenIgnoreOtherReservedAndArbitraryClaimsAreRequiredButNotInClaims()
    {
        $key = new JwtKey(
            ['secret' => 'Buy the book', 'require' => ['jti', 'typ', 'and now for something completely different']]
        );
        $key->validateClaims(['sub' => 'john']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenAlgoIsMissing()
    {
        $key = new JwtKey(
            ['secret' => 'Buy the book']
        );
        $key->validateHeader(['typ' => 'JWT']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenTypeIsMissing()
    {
        $key = new JwtKey(
            ['secret' => 'Buy the book']
        );
        $key->validateHeader(['alg' => JwtKey::TYPE_HMAC]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenAlgorithmDoesntMatchKey()
    {
        $key = new JwtKey(
            ['secret' => 'Buy the book']
        );
        $key->validateHeader(['alg' => JwtKey::TYPE_RSA]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenTypeIsNotJwt()
    {
        $key = new JwtKey(
            ['secret' => 'Buy the book']
        );
        $key->validateHeader(['typ' => 'Something']);
    }

    /**
     * @param string      $secret
     * @param JwtKey|null $key
     *
     * @return JwtToken
     */
    private function createTokenMock($secret, JwtKey $key = null)
    {
        /** @var JwtToken $token */
        $token = $tokenMock = $this->getMockBuilder(
            'KleijnWeb\JwtBundle\Authenticator\JwtToken'
        )->disableOriginalConstructor()->getMock();

        $tokenMock->expects($this->once())
            ->method('validateSignature')
            ->with($secret, $key ? $key->getSignatureValidator() : $this->anything());

        $tokenMock->expects($this->once())
            ->method('getClaims')
            ->willReturn(['sub' => 'john']);

        $tokenMock->expects($this->once())
            ->method('getHeader')
            ->willReturn(['alg' => JwtKey::TYPE_HMAC, 'typ' => 'JWT']);

        return $token;
    }
}
