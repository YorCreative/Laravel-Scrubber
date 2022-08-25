<?php

namespace YorCreative\Scrubber\SecretManager;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Config;

class Secret
{
    protected string $key;

    protected string $variable;

    public function __construct(string $key, string $variable)
    {
        $this->key = $key;
        $this->variable = self::getEncrypter()->encryptString($variable);
    }

    /**
     * @return Encrypter
     */
    private static function getEncrypter(): Encrypter
    {
        return new Encrypter(
            Config::get('scrubber.secret_manager.key'),
            Config::get('scrubber.secret_manager.cipher')
        );
    }

    /**
     * @param  string  $encryptedSecret
     * @return string
     */
    public static function decrypt(string $encryptedSecret): string
    {
        return self::getEncrypter()->decryptString($encryptedSecret);
    }

    /**
     * @return string
     */
    public function getVariable(): string
    {
        return $this->variable;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
