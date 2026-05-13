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
use Flarum\Foundation\ValidationException;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadLogoController implements RequestHandlerInterface
{
    protected SettingsRepositoryInterface $settings;
    protected Filesystem $uploadDir;

    public function __construct(SettingsRepositoryInterface $settings, Factory $filesystemFactory)
    {
        $this->settings = $settings;
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * @throws PermissionDeniedException|RouteNotFoundException|ValidationException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $size = (int) Arr::get($request->getAttribute('routeParameters'), 'size');

        if (! in_array($size, Util::$ICON_SIZES)) {
            throw new RouteNotFoundException();
        }

        $files = $request->getUploadedFiles();
        /** @var UploadedFileInterface|null $file */
        $file = Arr::get($files, 'pwa-icon-'.$size.'x'.$size) ?? reset($files) ?: null;

        if (! $file instanceof UploadedFileInterface) {
            throw new ValidationException(['upload' => 'No file uploaded.']);
        }

        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['upload' => 'File upload error: '.$file->getError()]);
        }

        $manager = new ImageManager(new GdDriver());
        $image = $manager->read($file->getStream()->getContents());
        $image->scale($size, $size);
        $encoded = (string) $image->toPng();

        $filename = "pwa-icon-{$size}x{$size}.png";

        $settingKey = "askvortsov-pwa.icon_{$size}_path";

        // Delete any previous file at the recorded path
        $previousPath = $this->settings->get($settingKey);
        if ($previousPath && $previousPath !== $filename) {
            try {
                $this->uploadDir->delete($previousPath);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $this->uploadDir->put($filename, (string) $encoded);
        $this->settings->set($settingKey, $filename);

        return new JsonResponse([
            'data' => [
                'type' => 'pwa-logos',
                'id' => (string) $size,
                'attributes' => [
                    'size' => $size,
                    'path' => $filename,
                    'url' => $this->uploadDir->url($filename),
                ],
            ],
        ]);
    }
}
