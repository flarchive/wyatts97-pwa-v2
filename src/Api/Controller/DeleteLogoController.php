<?php

/*
 * This file is part of askvortsov/flarum-pwa
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Askvortsov\FlarumPWA\Api\Controller;

use Askvortsov\FlarumPWA\Util;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteLogoController implements RequestHandlerInterface
{
    protected SettingsRepositoryInterface $settings;
    protected Filesystem $uploadDir;

    public function __construct(SettingsRepositoryInterface $settings, Factory $filesystemFactory)
    {
        $this->settings = $settings;
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * @throws PermissionDeniedException|RouteNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $size = (int) Arr::get($request->getAttribute('routeParameters'), 'size');

        if (! in_array($size, Util::$ICON_SIZES)) {
            throw new RouteNotFoundException();
        }

        $pathKey = "askvortsov-pwa.icon_{$size}_path";
        $path = $this->settings->get($pathKey);

        if ($path) {
            try {
                $this->uploadDir->delete($path);
            } catch (\Throwable $e) {
                // ignore missing file
            }
        }

        $this->settings->set($pathKey, null);

        return new EmptyResponse(204);
    }
}
