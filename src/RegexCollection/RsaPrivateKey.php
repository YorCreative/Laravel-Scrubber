<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class RsaPrivateKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '(?<=-----BEGIN RSA PRIVATE KEY-----)(?s)(.*END RSA PRIVATE KEY)';
    }

    public function getTestableString(): string
    {
        return '-----BEGIN RSA PRIVATE KEY-----
MIICWgIBAAKBgHvacpChvJ0Z/3rmMvH8+qxXOsPGXfi7WEz3LyhJq7G50fMyEi58
C1XFLeSVyAxtpe7M68xICbUV6uGB2YQNkjSMHSdWn+TAbypMH1/KkGTf1ZGZ15Bb
lYQmnV7ihIVba8PgjP/S9pt399fXGFCQtl2rTxWiZOku8VctV2CnWzV7AgMBAAEC
gYBFLzyqAD79PyWQgIDa3mck2EFSVT/vDq//pmCoT6biS5u1DzZK0y39xnyhYO3z
y1hSshPR9De/+TNQrxlTg8U0XVIwULO1m0MDNPDktZWFJ1ryaLaWO95k8hqQ68Gv
0ZToeq9FCXcACUDzHhPyuRZ4fZdikltvj+KxgmjVJ5PJYQJBANPbS0KJegHBXOVd
oohaEJ2ancAulUf6kx03WXwl/MUzOdvPB8KJCP8DVUOgTUkmSQ5B+piy1pUiNkr1
cP9EnisCQQCVqPF9wX56kIby+BNvTOA08Xd4rT8rYF0w7OJmCU7dwJV9Vb425viP
DzvoDa5zDojY/pZfOYRfJs027F52Pm3xAkBc2cjDUaNyb3+6Wu5oGikcGe63kvME
R/MAJAkJG1EMUKY0CymYfhy+P4S4DeKxg6ETKaGeGQto80SeV7H9fuJfAkBJ0vSM
3A3P18s5vzWXCYzvkM0mMg+fDgHqSG/FdYH50S3sjYcu/fBOYW1jopwTFXBb2fnD
L1Qku7cvCJnwKguBAkA9oUUEMC2CaFrKskFRWQPjkzy82oOWdYP8pwDsHLy+33tk
6EgPsdWHTOuCih7cpgpn4EvtJPbV5k1OzNv5WAs7
-----END RSA PRIVATE KEY-----';
    }

    public function isSecret(): bool
    {
        return false;
    }
}
