<?php

namespace App\Helpers;

use Hashids\Hashids;

class HashidsHelper
{
    private static ?Hashids $instance = null;

    public static function encode(int $id): string
    {
        return self::instance()->encode($id);
    }

    public static function decode(string $encoded): ?int
    {
        $ids = self::instance()->decode($encoded);
        return $ids[0] ?? null;
    }

    private static function instance(): Hashids
    {
        if (self::$instance === null) {
            $salt = config('app.key') ?: 'pod_patient_salt';
            self::$instance = new Hashids($salt, 12);
        }
        return self::$instance;
    }
}
