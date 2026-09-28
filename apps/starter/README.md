# Free Fire Info Starter

> **Generated distribution repository:** development happens in `refatbd/free-fire-php-monorepo`. Do not edit the split repository directly.

Ready-made Laravel application consuming `refatbd/laravel-free-fire` without copying protocol or credential code.

## Version

**Latest release: [`v1.1.1`](https://github.com/refatbd/free-fire-info-starter/tree/v1.1.1) (binary-safe cache & OB55).** The previous [`v1.1.0`](https://github.com/refatbd/free-fire-info-starter/tree/v1.1.0) release does not include the MySQL binary caching fix. The application version (`vX.Y.Z`) is separate from the Free Fire protocol version (`OB55`). Release tags originate in the [canonical monorepo](https://github.com/refatbd/free-fire-php-monorepo); see its [release process](https://github.com/refatbd/free-fire-php-monorepo/blob/main/docs/RELEASE_PROCESS.md).

```bash
composer create-project refatbd/free-fire-info-starter free-fire-info
cd free-fire-info
php artisan serve
```

Open:

- `/` — responsive UID/region checker;
- `/docs` — API, Laravel usage, environment and deployment guide;
- `/api/free-fire/v1/health` — safe protocol/media diagnostic.

Set the active versioned protocol with:

```dotenv
FREEFIRE_PROTOCOL=OB55
```

Run `php artisan freefire:media-check` to verify official avatar/banner support. Player information remains usable when ASTC media rendering is unavailable.
