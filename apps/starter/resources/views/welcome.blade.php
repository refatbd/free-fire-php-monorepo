<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Free Fire Info</title>
<style>
:root{font-family:Inter,system-ui,sans-serif;color-scheme:dark}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at top,#312e81,#09090b 58%);color:#fafafa;display:grid;place-items:center;padding:24px}.card{width:min(720px,100%);padding:42px;border:1px solid #ffffff20;border-radius:28px;background:#0b0b12cc;box-shadow:0 30px 80px #0008}.eyebrow{color:#f59e0b;font-weight:800;letter-spacing:.14em;text-transform:uppercase}h1{font-size:clamp(2.4rem,8vw,5rem);line-height:.95;margin:.4em 0}.sub{color:#cbd5e1;line-height:1.7}.grid{display:grid;grid-template-columns:1fr auto;gap:12px;margin-top:30px}input,select,button{border-radius:14px;border:1px solid #ffffff24;padding:15px 16px;font:inherit}input,select{background:#18181b;color:white}button{background:#f59e0b;color:#181000;font-weight:900;cursor:pointer}.errors{background:#7f1d1d;padding:12px;border-radius:12px;margin-top:18px}.links{display:flex;gap:18px;margin-top:22px}.links a{color:#fbbf24;text-decoration:none;font-weight:800}@media(max-width:650px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<main class="card">
<div class="eyebrow">Unofficial community tool</div>
<h1>Find your Free Fire profile.</h1>
<p class="sub">Enter any Free Fire player UID to load live profile statistics across all global server regions automatically.</p>
@if($errors->any())
<div class="errors">{{ $errors->first() }}</div>
@endif
<form class="grid" method="get" action="{{ route('player.show') }}">
<input name="uid" inputmode="numeric" pattern="[0-9]{5,20}" maxlength="20" value="{{ old('uid') }}" placeholder="Enter Player UID (e.g. 4422076728)" autocomplete="off" required>
<button>Check player</button>
</form>
<div class="links"><a href="{{ route('docs') }}">Developer guide →</a></div>
</main>
</body>
</html>
