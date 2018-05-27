<?php
namespace App\Security\Authentication;

use App\Entity\User;
use Base2n;
use Doctrine\ORM\EntityManagerInterface;

class MfaService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createMfaURL(User $user): string
    {
        $mfaKey = bin2hex(random_bytes(128));
        $user->setMfaKey($mfaKey);

        $this->em->persist($user);
        $this->em->flush();

        $base32 = new Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', FALSE, TRUE, TRUE);
        $hash = $base32->encode($mfaKey);

        return 'otpauth://totp/' . $user->getUsername() . '?secret=' . $hash . '=&issuer=PasswordSafe';
    }

    public function validateOTP(User $user, string $otp)
    {
        $mfaKey = $user->getMfaKey();
        if (empty($mfaKey)) {
            return true;
        }

        $c = (int)((((int)time() * 1000) / (30 * 1000)));

        $validOTP = $this->generateOTP($c, $mfaKey);

        if ($validOTP == $otp) {
            return true;
        }
        return false;
    }

    public function generateOTP(int $input, string $key): string
    {
        $hash = hash_hmac('sha1', $this->intToBytestring($input), $key);
        foreach (str_split($hash, 2) as $hex) {
            $hmac[] = hexdec($hex);
        }
        $offset = $hmac[19] & 0xf;
        $code = ($hmac[$offset + 0] & 0x7F) << 24 |
            ($hmac[$offset + 1] & 0xFF) << 16 |
            ($hmac[$offset + 2] & 0xFF) << 8 |
            ($hmac[$offset + 3] & 0xFF);
        $otp = $code % pow(10, 6);
        return str_pad($otp, 6, '0', STR_PAD_LEFT);
    }

    private function intToBytestring(int $int)
    {
        $result = Array();
        while ($int != 0) {
            $result[] = chr($int & 0xFF);
            $int >>= 8;
        }
        return str_pad(join(array_reverse($result)), 8, "\000", STR_PAD_LEFT);
    }

}
