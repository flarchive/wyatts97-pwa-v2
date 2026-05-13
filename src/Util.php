<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA;

class Util
{
    /** Published under public `assets/extensions/{id}/` (composer name with `/` → `-`). */
    public const EXTENSION_ASSET_ID = 'wyatts97-pwa-v2';

    public static array $ICON_SIZES = [48, 72, 96, 144, 196, 256, 512];

    public static function url_encode($data): string
    {
        if (empty($data)) {
            return '';
        }

        return rtrim(strtr($data, ['+' => '-', '/' => '_']), '=');
    }
}
