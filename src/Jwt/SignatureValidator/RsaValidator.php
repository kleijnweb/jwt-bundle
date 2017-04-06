<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\JwtBundle\Jwt\SignatureValidator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class RsaValidator implements SignatureValidator
{
    const SHA256 = OPENSSL_ALGO_SHA256;
    const SHA512 = OPENSSL_ALGO_SHA512;

    /**
     * @var int
     */
    private $hashAlgorithm;

    /**
     * @param int $hashAlgorithm
     */
    public function __construct($hashAlgorithm = self::SHA256)
    {
        $this->hashAlgorithm = $hashAlgorithm;
    }

    /**
     * @param string $payload
     * @param string $publicKey
     * @param string $signature
     *
     * @return bool
     */
    public function isValid($payload, $publicKey, $signature)
    {
        return openssl_verify($payload, $signature, $publicKey, $this->hashAlgorithm) === 1;
    }
}
