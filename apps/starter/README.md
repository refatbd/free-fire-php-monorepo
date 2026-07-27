# Free Fire Info Starter

> **Generated distribution repository:** development happens in `refatbd/free-fire-php-monorepo`. Do not edit the split repository directly.

Ready-made Laravel application consuming `refatbd/laravel-free-fire` without copying protocol or credential code.

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
FREEFIRE_PROTOCOL=OB54
```

Run `php artisan freefire:media-check` to verify official avatar/banner support. Player information remains usable when ASTC media rendering is unavailable.
