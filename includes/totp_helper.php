<?php
/**
 * Hwange Diocese Records Management System (RMS)
 * Lightweight TOTP (Time-based One-Time Password) Helper
 * 
 * This class handles the generation and validation of 2FA codes
 * based on the RFC 6238 standard.
 */

class TOTPHelper {
    private static $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random 32-character Secret Key
     */
    public static function generateSecret($length = 16) {
        $secret = '';
        while (strlen($secret) < $length) {
            $secret .= self::$base32chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Calculate the code for a given secret at a specific time
     */
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);

        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        
        // Extract 4 bytes
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);
        
        // Convert to integer
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;

        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted code against a secret
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a QR Code URL for setup (otpauth://)
     */
    public static function getQRUrl($username, $secret, $issuer = 'Hwange Diocese RMS') {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username) . 
               '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }

    private static function base32Decode($base32) {
        if (empty($base32)) return '';
        
        $base32 = strtoupper($base32);
        $base32Lookup = array_flip(str_split(self::$base32chars));
        
        $out = '';
        $buffer = 0;
        $bufferBits = 0;
        
        foreach (str_split($base32) as $char) {
            if (!isset($base32Lookup[$char])) continue;
            
            $buffer = ($buffer << 5) | $base32Lookup[$char];
            $bufferBits += 5;
            
            if ($bufferBits >= 8) {
                $bufferBits -= 8;
                $out .= chr(($buffer >> $bufferBits) & 0xFF);
            }
        }
        
        return $out;
    }
}
?>
