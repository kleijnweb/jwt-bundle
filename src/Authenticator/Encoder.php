<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\JwtBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KleijnWeb\JwtBundle\Authenticator;

class Encoder
{
    /**
     * @var array
     */
    private static $messages = [
        JSON_ERROR_NONE           => 'No error',
        JSON_ERROR_DEPTH          => 'Maximum stack depth exceeded',
        JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
        JSON_ERROR_CTRL_CHAR      => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX         => 'Syntax error',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    ];

    /**
     * @param mixed $base64Decoded
     *
     * @return string
     */
    public function encode($base64Decoded): string
    {
        return $this->base64Encode($this->jsonEncode($base64Decoded));
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function jsonEncode($data): string
    {
        $plain = json_encode($data);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException(self::$messages[json_last_error()]);
        }

        return $plain;
    }

    /**
     * @param string $base64Decoded
     *
     * @return string
     */
    public function base64Encode(string $base64Decoded): string
    {
        $base64Decoded = base64_encode($base64Decoded);
        $base64Decoded = rtrim(strtr($base64Decoded, '-_', '+/'), '=');

        return $base64Decoded;
    }
}
