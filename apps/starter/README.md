# Free Fire Info Starter

> **Generated distribution repository:** development happens in `refatbd/free-fire-php-monorepo`. Do not edit the split repository directly.

Ready-made Laravel application consuming `refatbd/laravel-free-fire` without copying protocol or credential code.

## Version

The latest tag before the OB55 recovery is [`v1.0.1`](https://github.com/refatbd/free-fire-info-starter/tree/v1.0.1). The OB55 starter defaults on `main` target **`v1.1.0`**, which must be tagged before versioned installs can use them. The application version (`vX.Y.Z`) is separate from the Free Fire protocol version (`OB55`). Release tags originate in the [canonical monorepo](https://github.com/refatbd/free-fire-php-monorepo); see its [release process](https://github.com/refatbd/free-fire-php-monorepo/blob/main/docs/RELEASE_PROCESS.md).

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
