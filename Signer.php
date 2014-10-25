<?php

namespace richweber\wm\exchanger;

/**
 * Модуль аутентификации WMSigner
 *
 * @copyright Copyright &copy; Andrei Baibaratsky, https://github.com/baibaratsky
 * @link https://github.com/baibaratsky/php-webmoney/blob/master/Request/RequestSigner.php
 */
class Signer
{
    private $power;
    private $modulus;

    /**
     * Create signature for given data
     *
     * @param string $data
     *
     * @return string
     */
    public function sign($data)
    {
        // Make data hash (16 bytes)
        $base = hash('md4', $data, true);

        // Add 40 random bytes
        for ($i = 0; $i < 10; ++$i) {
            $base .= pack('V', mt_rand());
        }

        // Add length of the base as first 2 bytes
        $base = pack('v', strlen($base)) . $base;

        // Modular exponentiation
        $dec = bcpowmod($this->reverseToDecimal($base), $this->power, $this->modulus);

        // Convert result to hexadecimal
        $hex = gmp_strval($dec, 16);

        // Fill empty bytes with zeros
        $hex = str_repeat('0', 132 - strlen($hex)) . $hex;

        // Reverse byte order
        $hexReversed = '';
        for ($i = 0; $i < strlen($hex) / 4; ++$i) {
            $hexReversed = substr($hex, $i * 4, 4) . $hexReversed;
        }

        return strtolower($hexReversed);
    }

    /**
     * Encrypt key using hash of WMID and key password
     *
     * @param string $keyBuffer
     * @param string $wmid
     * @param string $keyPassword
     *
     * @return string
     */
    protected function encryptKey($keyBuffer, $wmid, $keyPassword)
    {
        $hash = hash('md4', $wmid . $keyPassword, true);

        return $this->xorStrings($keyBuffer, $hash, 6);
    }

    /**
     * XOR subject with modifier
     *
     * @param string $subject
     * @param string $modifier
     * @param int $shift
     *
     * @return string
     */
    private function xorStrings($subject, $modifier, $shift = 0)
    {
        $modifierLength = strlen($modifier);
        $i = $shift;
        $j = 0;
        while ($i < strlen($subject)) {
            $subject[$i] = chr(ord($subject[$i]) ^ ord($modifier[$j]));
            ++$i;
            if (++$j >= $modifierLength) {
                $j = 0;
            }
        }

        return $subject;
    }

    /**
     * Verify hash of the key
     *
     * @param $keyData
     *
     * @return bool
     */
    protected function verifyHash($keyData)
    {
        $verificationString = pack('v', $keyData['reserved'])
            . pack('v', 0)
            . pack('V4', 0, 0, 0, 0)
            . pack('V', $keyData['length'])
            . $keyData['buffer'];
        $hash = hash('md4', $verificationString, true);

        return strcmp($hash, $keyData['hash']) == 0;
    }

    /**
     * Initialize power and modulus to use for signing
     *
     * @param string $keyBuffer
     */
    protected function initSignVariables($keyBuffer)
    {
        $data = unpack('Vreserved/vpowerLength', $keyBuffer);
        $data = unpack('Vreserved/vpowerLength/a' . $data['powerLength'] . 'power/vmodulusLength', $keyBuffer);
        $data = unpack('Vreserved/vpowerLength/a' . $data['powerLength'] . 'power/vmodulusLength/a'
                    . $data['modulusLength'] . 'modulus', $keyBuffer);
        $this->power = $this->reverseToDecimal($data['power']);
        $this->modulus = $this->reverseToDecimal($data['modulus']);
    }

    /**
     * Reverse byte order and convert binary data to decimal string
     *
     * @param string $binaryData
     *
     * @return string
     */
    private function reverseToDecimal($binaryData)
    {
        return gmp_strval('0x' . bin2hex(strrev($binaryData)));
    }
}
