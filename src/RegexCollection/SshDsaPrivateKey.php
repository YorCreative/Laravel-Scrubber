<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;

class SshDsaPrivateKey implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return '(?<=-----BEGIN PRIVATE KEY-----)(?s)(.*END PRIVATE KEY)';
    }

    public function getTestableString(): string
    {
        return '-----BEGIN PRIVATE KEY-----
MIIBTAIBADCCASwGByqGSM44BAEwggEfAoGBAJ4vZpJ9H6iJR/UU1gJbHTR6in8o
a4vX1Vdvj/V53Q1U2lS0VdkAZyZQiWfO9QTO5oM0Y4S7DtTX3UIiuSuKVWMD55pi
WuTgDemf4ZsVAdxcQ6RKCYSwiO0o3O+7RwX2aEzb/KaMqphoHtwRPWhxp5Mbz9kz
DD9T+xQAzsfsuhGVAhUA1kA8zoR9/NuIDs07OdP76UX3UnkCgYEAmB2kVCBqooud
n/zU0dFeXY8RD2OoobKbvdnFeyl8qG3BskLp+1qzHEVT9zI8+6DmJnSxcxyjuT+/
ZO1JnUSX9GNPfWwA4khntera6cLe8qm3fJiWRdsen5XZFFYqvj8A6e5x6qdVCehL
Gc1ZLn0ewTtLDYYpTM/QqFYI7XxKDaEEFwIVAJI+/0a+OZzLkhusqYQatAuGgAvH
-----END PRIVATE KEY-----';
    }

    public function isSecret(): bool
    {
        return false;
    }

    public function getReplacementValue(): ?string
    {
        return null;
    }
}
