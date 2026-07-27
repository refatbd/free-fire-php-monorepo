# Official media and ASTC

The core validates the 16-byte ASTC header, magic, block dimensions, 2D depth, maximum dimensions and minimum payload implied by the texture blocks. It also validates numeric item IDs, HTTPS scheme, exact official host allowlists and maximum streamed download size before decoding.

Recommended driver: Arm `astcenc` through `AstcencProcessDecoder`. The process receives an argument array without a shell, has a timeout, and never receives an arbitrary user-controlled URL. Official downloads are cached by OB version, configured source set and item ID.

The current compositor uses PHP GD with WebP support. It creates official or fallback avatars and banners while preserving player-data availability when media capability is absent.

Laravel diagnostic:

```bash
php artisan freefire:media-check
```

Required for official media:

- writable private temporary/cache directories;
- PHP GD with WebP;
- an executable local `astcenc` path;
- outbound HTTPS access to the configured official CDN bases.

An unavailable decoder/GD stack must not crash token or player-data retrieval. The media endpoint should return a controlled unavailable response or fallback, while JSON lookup remains functional.
