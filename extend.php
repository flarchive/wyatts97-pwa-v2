<?php

/*
 * This file is part of wyatts97/pwa-v2
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA;

use Askvortsov\FlarumPWA\Api\Controller as ApiController;
use Askvortsov\FlarumPWA\FlarumPWAServiceProvider;
use Askvortsov\FlarumPWA\Forum\Controller as ForumController;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;

$metaClosure = function (Document $document) {
    /** @var UrlGenerator $urlGenerator */
    $urlGenerator = resolve(UrlGenerator::class);
    $basePath = rtrim(parse_url($urlGenerator->to('forum')->base(), PHP_URL_PATH) ?? '', '/');

    /** @var SettingsRepositoryInterface $settings */
    $settings = resolve(SettingsRepositoryInterface::class);
    $appName = $settings->get('askvortsov-pwa.shortName')
        ?: $settings->get('askvortsov-pwa.longName')
        ?: $settings->get('forum_title');

    $document->head[] = "<link rel='manifest' href='$basePath/webmanifest'>";
    $document->head[] = "<meta name='mobile-web-app-capable' content='yes'>";
    $document->head[] = "<meta name='apple-mobile-web-app-capable' content='yes'>";
    $document->head[] = "<meta id='apple-style' name='apple-mobile-web-app-status-bar-style' content='default'>";
    $document->head[] = "<meta id='apple-title' name='apple-mobile-web-app-title' content='".htmlspecialchars((string) $appName, ENT_QUOTES)."'>";

    /** @var Cloud $assets */
    $assets = resolve(Factory::class)->disk('flarum-assets');

    foreach (Util::$ICON_SIZES as $size) {
        if ($sizePath = $settings->get('askvortsov-pwa.icon_'.strval($size).'_path')) {
            $assetUrl = $assets->url($sizePath);
            $sizesAttr = $size === 48 ? '' : "sizes='{$size}x$size'";
            $document->head[] = "<link id='apple-icon-$size' rel='apple-touch-icon' $sizesAttr href='$assetUrl'>";
        }
    }
};

$pwaIconFields = function (): array {
    /** @var SettingsRepositoryInterface $settings */
    $settings = resolve(SettingsRepositoryInterface::class);
    /** @var Cloud $assets */
    $assets = resolve(Factory::class)->disk('flarum-assets');

    $fields = [];
    foreach (Util::$ICON_SIZES as $size) {
        $key = "pwa-icon-{$size}x{$size}Url";
        $settingKey = "askvortsov-pwa.icon_{$size}_path";
        $fields[] = Schema\Str::make($key)
            ->get(function () use ($settings, $assets, $settingKey) {
                $path = $settings->get($settingKey);
                return $path ? $assets->url($path) : null;
            });
    }

    return $fields;
};

return [
    (new Extend\Routes('api'))
        ->get('/pwa/settings', 'askvortsov-pwa.settings', ApiController\ShowPWASettingsController::class)
        ->delete('/pwa/logo/{size}', 'askvortsov-pwa.size_delete', ApiController\DeleteLogoController::class)
        ->post('/pwa/logo/{size}', 'askvortsov-pwa.size_upload', ApiController\UploadLogoController::class)
        ->post('/pwa/push', 'askvortsov-pwa.push.create', ApiController\AddPushSubscriptionController::class)
        ->post('/reset_vapid', 'askvortsov-pwa.reset_vapid', ApiController\ResetVAPIDKeysController::class),

    (new Extend\Routes('forum'))
        ->get('/webmanifest', 'askvortsov-pwa.webmanifest', ForumController\WebManifestController::class)
        ->get('/sw', 'askvortsov-pwa.sw', ForumController\ServiceWorkerController::class)
        ->get('/offline', 'askvortsov-pwa.offline', ForumController\OfflineController::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->content($metaClosure),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->content($metaClosure),

    (new Extend\ApiResource(ForumResource::class))
        ->fields($pwaIconFields),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Model(User::class))
        ->hasMany('pushSubscriptions', PushSubscription::class, 'user_id'),

    (new Extend\Settings())
        ->serializeToForum('vapidPublicKey', 'askvortsov-pwa.vapid.public', [Util::class, 'url_encode'])
        ->default('askvortsov-pwa.pushNotifPreferenceDefaultToEmail', true)
        ->default('askvortsov-pwa.userMaxSubscriptions', 20),

    (new Extend\Notification())
        ->driver('push', PushNotificationDriver::class),

    (new Extend\View())
        ->namespace('askvortsov-pwa', __DIR__.'/views'),

    new Extend\ServiceProvider(FlarumPWAServiceProvider::class),
];
