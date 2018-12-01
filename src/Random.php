<?php
declare(strict_types=1);

namespace jahweh\random;

/**
 * Helper to create random strings.
 *
 * @author Adrian Liechti <info@jahweh.ch>
 */
class Random
{
    const CHARS_DEFAULT = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const CHARS_AN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const CHARS_A = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_AU = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_AL = 'abcdefghijklmnopqrstuvwxyz';
    const CHARS_N = '0123456789';
    const CHARS_NN = '123456789';
    const CHARS_HEX = 'abcdef0123456789';
    const CHARS_YOUTUBE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
    const BYTE_CHOICES = 256;
    const E_CHAR_OVERFLOW = 267;
    /** @var callable */
    private $randomBytesFunction;

    public function __construct(callable $randomBytesFunction = null)
    {
        $this->randomBytesFunction = $randomBytesFunction ?? 'random_bytes';
    }

    private function bytes(int $length): string
    {
        return ($this->randomBytesFunction)($length);
    }

    /**
     * Generates a random string
     *
     * @param int $length The length of the string to be generated.
     * @param string|array $chars
     * @return string
     * @throws \Exception
     */
    public function string(int $length = 6, $chars = self::CHARS_DEFAULT): string
    {
        // For the UTF-8 support
        if (is_string($chars)) {
            $charCount = mb_strlen($chars, 'UTF-8');
            $chars = preg_split('//u', $chars, -1, PREG_SPLIT_NO_EMPTY);
        } else { // Array
            $charCount = count($chars);
        }
        // More than 256 chars are not supported
        if ($charCount > static::BYTE_CHOICES) {
            throw new \Exception('More than ' . static::BYTE_CHOICES . ' chars are not supported.',
                static::E_CHAR_OVERFLOW);
        }
        $charPerByte = static::BYTE_CHOICES / $charCount;
        $bytes = $this->bytes($length);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            // In order to prevent a character being required which is above the range,
            // the ratio "charPerByte" is included here.
            $key = (int)floor(ord($bytes[$i]) / $charPerByte);
            $string .= $chars[$key];
        }
        return $string;
    }

    /**
     * Generates a hexadecimal string very quickly.
     *
     * @param int $length The length of the string to be generated.
     * @return string
     * @see http://stackoverflow.com/a/27371037
     * @throws \Exception
     */
    public function hex(int $length): string
    {
        return bin2hex($this->bytes($length / 2));
    }

    /**
     * Generates a number
     *
     * @param int $length The length of the number to be generated
     * @param bool $noZeroFirst The first number must not be zero
     * @return string The generated number as a string
     * @todo Use PHP7 random_number for noZeroFirst
     * @throws \Exception
     */
    public function number(int $length = 8, bool $noZeroFirst = true): string
    {
        $number = '';
        if ($noZeroFirst) {
            $number .= random_int(1, 9);
            $length--;
        }
        if ($length > 0) {
            $number .= $this->string($length, static::CHARS_N);
        }
        return $number;
    }

    /**
     * Generates a password with specific settings
     *
     * @param int $length The length of the password to be generated
     * @param array $blocks [chars,from,to,stick]
     * @return string
     * @todo Reworking and simplification of the function.
     * @throws \Exception
     */
    public function password(int $length = 8, array $blocks): string
    {
        $passwordArray = [];
        $remaining = $length;
        $blockCount = count($blocks);
        for ($i = 0; $i < $blockCount; $i++) {
            $chars = $blocks[$i][0];
            $min = (isset($blocks[$i][1]) ? $blocks[$i][1] : 0);
            $max = (isset($blocks[$i][2]) ? $blocks[$i][2] : $remaining);
            $stick = (isset($blocks[$i][3]) ? $blocks[$i][3] : false);
            $blockLen = (($i + 1) < $blockCount ? random_int($min, $max) : $remaining); // The last block fills up
            if ($blockLen > 0) {
                $remaining -= $blockLen;
                $blockStr = $this->string($blockLen, $chars);
                if ($stick) { // Sticky blocks stay together
                    $passwordArray[] = $blockStr;
                } else { // For non-sticky blocks, each character is stored individually in the array
                    $passwordArray = array_merge($passwordArray, str_split($blockStr));
                }
            }
        }

        shuffle($passwordArray); // Mix the array with the blocks
        return implode('', $passwordArray); // Convert the array to string
    }
}
