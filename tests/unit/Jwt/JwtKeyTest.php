<?php
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Tests\Jwt;

use KleijnWeb\JwtBundle\Jwt\Exception\InvalidTimeException;
use KleijnWeb\JwtBundle\Jwt\Exception\MissingClaimsException;
use KleijnWeb\JwtBundle\Jwt\JwtKey;
use KleijnWeb\JwtBundle\Jwt\JwtToken;
use KleijnWeb\JwtBundle\Jwt\SecretLoader;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\HmacValidator;
use KleijnWeb\JwtBundle\Jwt\SignatureValidator\RsaValidator;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class JwtKeyTest extends TestCase
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
        $secret = (string)rand();
        $key    = new JwtKey(['secret' => $secret]);
        $key->validateToken($this->createTokenMock($secret, $key));
    }

    /**
     * @test
     */
    public function willValidateIfAudienceIsConfiguredAndMatchedAny()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => ['author', 'reader']]);
        $key->validateClaims(['sub' => 'john', 'aud' => 'reader']);
    }

    /**
     * @test
     */
    public function willValidateIfAudienceClaimIsArrayAndMatchesAll()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => ['author', 'editor', 'reader']]);
        $key->validateClaims(['sub' => 'john', 'aud' => ['author', 'reader']]);
    }

    /**
     * @test
     */
    public function canLoadSecretFromLoader()
    {
        $secret = (string)rand();
        $token  = $this->createTokenMock($secret);

        $loaderMock = $this->getMockBuilder(SecretLoader::class)->getMock();
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
        $this->assertInstanceOf(HmacValidator::class, $actual);
    }

    /**
     * @test
     */
    public function willGetRsaSignatureValidatorWhenTypeIsRsa()
    {
        $key    = new JwtKey(['secret' => 'Buy the book', 'type' => JwtKey::TYPE_RSA]);
        $actual = $key->getSignatureValidator();
        $this->assertInstanceOf(RsaValidator::class, $actual);
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

        $this->expectException(MissingClaimsException::class);

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

        $this->expectException(MissingClaimsException::class);

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims($claims);
    }

    /**
     * @test
     */
    public function validationWillFailWhenExpiredByExp()
    {
        $this->expectException(InvalidTimeException::class);
        $this->expectExceptionMessage("Token is expired by 'exp'");

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims(['sub' => 'john', 'exp' => time() - 2]);
    }

    /**
     * @test
     */
    public function validationWillNotFailWhenExpiredByExpButWithinLeeway()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'leeway' => 3]);
        $key->validateClaims(['sub' => 'john', 'exp' => time() - 2]);
    }

    /**
     * @test
     */
    public function validationWillFailWhenExpiredByIatAndMinIssueTime()
    {
        $this->expectException(InvalidTimeException::class);
        $this->expectExceptionMessage("Server deemed your token too old");
        $key = new JwtKey(['secret' => 'Buy the book', 'minIssueTime' => time() + 2, 'leeway' => 3]);
        $key->validateClaims(['sub' => 'john', 'iat' => time()]);
    }

    /**
     * @test
     */
    public function validationWillFailWhenNotValidYet()
    {
        $this->expectException(InvalidTimeException::class);
        $this->expectExceptionMessage("Token not valid yet");

        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateClaims(['sub' => 'john', 'nbf' => time() + 2]);
    }

    /**
     * @test
     */
    public function validationWillNotFailWhenNotValidYetButWithinLeeway()
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
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => 'me']);
        $key->validateClaims(['sub' => 'john', 'aud' => 'the neighbours']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validationWillFailWhenSingleAudienceClaimFromArrayDoesNotMatch()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => 'me']);
        $key->validateClaims(['sub' => 'john', 'aud' => ['the neighbours', 'me']]);
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
        $key = new JwtKey(['secret' => 'Buy the book', 'audience' => time()]);
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
        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateHeader(['typ' => 'JWT']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenTypeIsMissing()
    {
        $key = new JwtKey(['secret' => 'Buy the book']);
        $key->validateHeader(['alg' => JwtKey::TYPE_HMAC]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenKeyAlgoDoesNotMatchTokenAlgo()
    {
        $key = new JwtKey(['secret' => 'Buy the book', 'type' => JwtKey::TYPE_RSA]);
        $key->validateHeader(['typ' => 'JWT', 'alg' => JwtKey::TYPE_HMAC]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function headerValidationWillFailWhenTypeIsNotJwt()
    {
        $key = new JwtKey(['secret' => 'Buy the book']);
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
        $token = $tokenMock = $this->getMockBuilder(JwtToken::class)->disableOriginalConstructor()->getMock();

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
