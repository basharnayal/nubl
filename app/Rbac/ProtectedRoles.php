<?php

namespace App\Rbac;

final class ProtectedRoles
{
    /** @var list<string> */
    public const NAMES = ['admin', 'donor', 'recipient', 'provider'];

    public static function isProtected(string $name): bool
    {
        return in_array($name, self::NAMES, true);
    }
}
