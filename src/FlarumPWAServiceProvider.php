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

use ErrorException;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Minishlink\WebPush\VAPID;
use Psr\Log\LoggerInterface;

class FlarumPWAServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = $this->container->make(SettingsRepositoryInterface::class);

        if (! $settings->get('askvortsov-pwa.vapid.public') || ! $settings->get('askvortsov-pwa.vapid.private')) {
            try {
                $keys = VAPID::createVapidKeys();

                $settings->set('askvortsov-pwa.vapid.public', $keys['publicKey']);
                $settings->set('askvortsov-pwa.vapid.private', $keys['privateKey']);
                $settings->set('askvortsov-pwa.vapid.success', true);
            } catch (ErrorException $e) {
                $settings->set('askvortsov-pwa.vapid.success', false);
                $settings->set('askvortsov-pwa.vapid.error', $e->getMessage());

                /** @var LoggerInterface $logger */
                $logger = $this->container->make(LoggerInterface::class);
                $logger->error('[PWA] Failed to generate VAPID keys: '.$e->getMessage());
            }
        }
    }
}
