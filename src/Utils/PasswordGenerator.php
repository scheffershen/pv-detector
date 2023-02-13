<?php

declare(strict_types=1);

namespace App\Utils;

final class PasswordGenerator
{
    public function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds'): string
    {
        $sets = [];
        if (false !== strpos($available_sets, 'l')) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (false !== strpos($available_sets, 'u')) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (false !== strpos($available_sets, 'd')) {
            $sets[] = '123456789';
        }
        if (false !== strpos($available_sets, 's')) {
            $sets[] = '!@#$%&*?.';
        }

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - \count($sets); ++$i) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);

        if (!$add_dashes) {
            return $password;
        }

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (\strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len).'-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;

        return $dash_str;
    }
}
