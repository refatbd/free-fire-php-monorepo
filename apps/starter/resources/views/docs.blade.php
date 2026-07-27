<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Developer guide · Free Fire Info</title>
<style>
:root{font-family:Inter,ui-sans-serif,system-ui,sans-serif;color-scheme:dark}*{box-sizing:border-box}body{margin:0;background:#08080b;color:#fafafa;line-height:1.65}.wrap{width:min(1080px,calc(100% - 36px));margin:auto;padding:32px 0 72px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:34px}.brand{font-weight:900;letter-spacing:-.03em}.nav{display:flex;gap:12px;flex-wrap:wrap}.nav a{color:#fbbf24;text-decoration:none;font-weight:800}.hero,.card{border:1px solid #ffffff16;background:#111116;border-radius:24px}.hero{padding:clamp(28px,6vw,58px);background:radial-gradient(circle at top right,#312e81,#111116 55%)}.eyebrow{text-transform:uppercase;color:#fbbf24;font-weight:900;letter-spacing:.14em;font-size:.8rem}h1{font-size:clamp(2.3rem,7vw,4.8rem);line-height:1;margin:.2em 0}.lead{max-width:760px;color:#cbd5e1}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:22px}.card{padding:24px}.card h2{margin-top:0}.endpoint{padding:14px 16px;border-radius:14px;background:#07070a;border:1px solid #ffffff14;margin:10px 0;overflow:auto}.method{color:#86efac;font-weight:900;margin-right:9px}code,pre{font:13px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace}pre{white-space:pre-wrap;background:#07070a;padding:18px;border-radius:15px;border:1px solid #ffffff14;overflow:auto}.note{color:#fcd34d}.muted{color:#94a3b8}ul{padding-left:20px}@media(max-width:760px){.grid{grid-template-columns:1fr}.top{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<main class="wrap">
<header class="top"><div class="brand">Free Fire Info Starter</div><nav class="nav"><a href="{{ route('home') }}">Player checker</a><a href="https://github.com/refatbd/FreeFireInfoSite" rel="noreferrer">Original project</a></nav></header>
<section class="hero"><div class="eyebrow">Developer guide</div><h1>Build your own checker.</h1><p class="lead">This starter consumes the Laravel bridge, which consumes the framework-independent PHP core. Core protocol, credentials, Protobuf and media code is never copied into this application.</p></section>
<section class="grid">
<article class="card"><h2>JSON API</h2><div class="endpoint"><span class="method">GET</span><code>/api/free-fire/v1/players/{uid}?region=BD</code></div><div class="endpoint"><span class="method">GET</span><code>/api/free-fire/v1/players/{uid}/avatar?region=BD</code></div><div class="endpoint"><span class="method">GET</span><code>/api/free-fire/v1/players/{uid}/banner?region=BD</code></div><div class="endpoint"><span class="method">GET</span><code>/api/free-fire/v1/health</code></div><p class="muted">Compatibility routes are also available when <code>FREEFIRE_COMPATIBILITY_ROUTES=true</code>.</p></article>
<article class="card"><h2>Laravel usage</h2><pre><code>use Refatbd\LaravelFreeFire\Facades\FreeFire;

$player = FreeFire::player(
    '4422076728',
    'BD',
);</code></pre><p>The returned array contains normalized basic, clan, captain, social and media information.</p></article>
<article class="card"><h2>Important environment options</h2><pre><code>FREEFIRE_PROTOCOL=OB54
FREEFIRE_DEFAULT_REGION=BD
FREEFIRE_ROUTES_ENABLED=true
FREEFIRE_RATE_LIMIT_PER_MINUTE=30
FREEFIRE_MEDIA_ENABLED=true
FREEFIRE_ASTCENC_BINARY=astcenc</code></pre><p class="note">Do not expose upstream bearer tokens or bundled credential values through your own API, logs or debug pages.</p></article>
<article class="card"><h2>Official avatar and banner</h2><ul><li>Install PHP GD with WebP support.</li><li>Install or configure the local <code>astcenc</code> executable.</li><li>Run <code>php artisan freefire:media-check</code>.</li><li>Player JSON continues working when media decoding is unavailable.</li></ul></article>
<article class="card"><h2>Protocol updates</h2><p>New OB releases are added as versioned core profiles and versioned Protobuf schemas. Select the active profile through <code>FREEFIRE_PROTOCOL</code>. Never overwrite an older profile or manually edit generated Protobuf PHP files.</p></article>
<article class="card"><h2>Production checklist</h2><ul><li>Disable debug mode.</li><li>Keep route throttling enabled.</li><li>Use Redis or a shared Laravel cache for multiple servers.</li><li>Keep media temporary/cache directories private and writable.</li><li>Run the health and media diagnostics after every deployment.</li></ul></article>
</section>
</main>
</body>
</html>
