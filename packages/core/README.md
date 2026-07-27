# refatbd/free-fire-php

> **Generated distribution repository:** development happens in `refatbd/free-fire-php-monorepo`. Do not edit the split repository directly.

Framework-independent PHP engine for Free Fire player information, region credentials, core-registered selectable OB protocol profiles, token management, uint64-safe Protobuf handling and official-media rendering.

```bash
composer require refatbd/free-fire-php
```

## Player lookup

```php
use Refatbd\FreeFire\FreeFireFactory;
use Refatbd\FreeFire\Protocol\BuiltInProtocolProfiles;

$profile = BuiltInProtocolProfiles::get(getenv('FREEFIRE_PROTOCOL') ?: 'OB54');
$client = FreeFireFactory::make(
    __DIR__.'/storage/freefire-cache',
    profile: $profile,
);

$player = $client->player('4422076728', 'BD');
```

Release distributions already contain generated PHP Protobuf classes. Contributors working from the canonical monorepo regenerate them only after schema changes:

```bash
composer proto:validate
composer proto:generate
```

## Official media from plain PHP

Pass the same profile to the player and media factories so protocol and media cache namespaces remain aligned:

```php
$media = FreeFireFactory::makeMedia(
    __DIR__.'/storage/freefire-cache',
    __DIR__.'/storage/freefire-tmp',
    profile: $profile,
);

$avatar = $media->avatar($player);
file_put_contents(__DIR__.'/public/avatar.webp', $avatar->data);
```

Official ASTC rendering requires a local `astcenc` executable and PHP GD with WebP support. Player JSON remains available when media rendering is unavailable.

Maintain new OB releases with `docs/OB_UPDATE_GUIDE.md`. Older profiles remain separate so a deployment can roll back without rewriting protocol history.
## Bundled ASTC decoder

The split core package includes optional Arm `astcenc` executables for Linux and Windows. They are distributed under Apache License 2.0; see `THIRD_PARTY_NOTICES.md` and `bin/LICENSE.astcenc.txt`. System-installed `astcenc` remains supported and takes priority.

