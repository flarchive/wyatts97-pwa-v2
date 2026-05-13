# Flarum Progressive Web App (2.x port)

![License](https://img.shields.io/badge/license-MIT-blue.svg)

A [Flarum](http://flarum.org) extension. Progressive Web App support for Flarum 2.x with VAPID web-push notifications. Configure a PWA for your Flarum installation from your admin dashboard.

This is a fork of [askvortsov/flarum-pwa](https://github.com/askvortsov1/flarum-pwa) updated for Flarum 2.x compatibility. Firebase/FCM support has been dropped; web-push (VAPID) is supported by all modern browsers including Chrome, Edge, Firefox, and Safari iOS 16.4+.

## Features

- Installable as a standalone Progressive Web App
- Configurable web app manifest (short name, long name, theme color, background color, force portrait, window controls overlay)
- Per-size icon uploads (48 / 72 / 96 / 144 / 196 / 256 / 512 px)
- Service worker registration with offline fallback page
- VAPID web-push notifications wired into Flarum's notification system
- Native share button integration in discussion / post / user controls
- Per-user opt-in push preferences via Settings page

## Installation

```sh
composer require wyatts97/pwa-v2
```

## Updating

```sh
composer update wyatts97/pwa-v2
php flarum cache:clear
```

## Configuration

Visit Admin → Extensions → Progressive Web App. Upload at least one icon (≥144 px) and set the short/long name. Generate VAPID keys via the "Reset VAPID keys" button. Users opt in to push notifications from their personal Settings page.

## Credits

- Original extension: [Alexander Skvortsov](https://github.com/askvortsov1)
- Original PWA work: [Billy Wilcosky](https://github.com/zerosonesfun)
- Flarum 2.x port: wyatts97

## Links

- [Source](https://github.com/wyatts97/pwa-v2)
- [Original upstream](https://github.com/askvortsov1/flarum-pwa)
