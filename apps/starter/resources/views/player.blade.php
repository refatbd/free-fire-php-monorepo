<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ data_get($player, 'basicInfo.nickname', 'Player '.$uid) }} · Free Fire Info</title>
<style>
:root {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
  color-scheme: dark;
}
* { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  background: #07070a;
  color: #fafafa;
  padding: 28px 20px;
}
.wrap {
  max-width: 1040px;
  margin: auto;
}
.back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #fbbf24;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 20px;
  transition: opacity 0.2s;
}
.back:hover { opacity: 0.8; }

/* Main Profile Card with Clean Banner Background */
.profile-card {
  position: relative;
  min-height: 240px;
  border-radius: 24px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: #111116;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
  margin-bottom: 24px;
}
.profile-card .banner-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: blur(8px) brightness(0.85);
  transform: scale(1.06);
}
.profile-card .gradient-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(7, 7, 10, 0.92) 0%, rgba(7, 7, 10, 0.75) 45%, rgba(7, 7, 10, 0.3) 100%);
}
.profile-content {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 28px;
  min-height: 240px;
  padding: 32px 36px;
}
.avatar-img {
  width: 130px;
  height: 130px;
  object-fit: cover;
  border-radius: 20px;
  border: 3px solid #f59e0b;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  background: #18181b;
  flex-shrink: 0;
}
.info-body {
  flex: 1;
}
.eyebrow {
  display: inline-block;
  color: #fbbf24;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.15em;
  font-weight: 900;
  background: rgba(245, 158, 11, 0.12);
  padding: 4px 12px;
  border-radius: 20px;
  border: 1px solid rgba(245, 158, 11, 0.25);
  margin-bottom: 8px;
}
h1 {
  font-size: clamp(2rem, 4.5vw, 3.2rem);
  line-height: 1.1;
  margin: 4px 0 10px 0;
  font-weight: 800;
  color: #ffffff;
  text-shadow: 0 2px 10px rgba(0,0,0,0.6);
}
.meta {
  color: #cbd5e1;
  font-weight: 700;
  font-size: 1.05rem;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.meta-dot {
  color: #f59e0b;
  font-size: 0.8rem;
}
.id-open {
  margin-top: 10px;
  font-size: 0.85rem;
  color: #94a3b8;
  font-weight: 600;
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}
.info-card {
  background: #111115;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.25);
}
.full-width-card {
  grid-column: 1 / -1;
}
.info-card h3 {
  margin: 0 0 16px 0;
  font-size: 1.15rem;
  color: #f59e0b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 8px;
}
.data-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.inline-grid-list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}
.data-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.92rem;
  border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
  padding-bottom: 6px;
}
.data-list .lbl {
  color: #94a3b8;
  font-weight: 600;
}
.data-list strong {
  color: #f8fafc;
  font-weight: 700;
  text-align: right;
}
.bio-text {
  font-style: italic;
  color: #fbbf24 !important;
  word-break: break-word;
  max-width: 60%;
}
.leader-info-block {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.leader-info-block h4 {
  margin: 0 0 6px 0;
  color: #f59e0b;
  font-size: 0.95rem;
}
.leader-info-block p {
  margin: 0;
  font-size: 0.9rem;
  color: #cbd5e1;
}

/* Section Card */
.card {
  background: #111115;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 24px;
}
.card h2 {
  margin: 0 0 16px 0;
  font-size: 1.25rem;
  color: #f1f5f9;
}
.composited-preview img {
  width: 100%;
  height: auto;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: block;
}
.json {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  max-height: 500px;
  overflow: auto;
  color: #cbd5e1;
  background: #09090d;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  font: 13px/1.55 ui-monospace, SFMono-Regular, Menlo, monospace;
  margin: 0;
}

@media (max-width: 900px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }
  .inline-grid-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 760px) {
  .profile-content {
    flex-direction: column;
    align-items: flex-start;
    padding: 24px;
    gap: 16px;
  }
  .avatar-img {
    width: 100px;
    height: 100px;
  }
}
</style>
</head>
<body>
@php
$regionNames = [
    'BD' => 'Bangladesh',
    'SG' => 'Singapore',
    'VN' => 'Vietnam',
    'IND' => 'India',
    'BR' => 'Brazil',
    'US' => 'United States',
    'NA' => 'North America',
    'SAC' => 'South America',
    'ID' => 'Indonesia',
    'RU' => 'Russia',
    'TW' => 'Taiwan',
    'TH' => 'Thailand',
    'ME' => 'Middle East',
    'PK' => 'Pakistan',
    'CIS' => 'Commonwealth',
    'EUROPE' => 'Europe',
    'EU' => 'Europe',
];
$basic = data_get($player, 'basicInfo', []);
$social = data_get($player, 'socialInfo', []);
$credit = data_get($player, 'creditScoreInfo', []);
$pet = data_get($player, 'petInfo', []);
$clan = data_get($player, 'clanBasicInfo', []);
$captain = data_get($player, 'captainBasicInfo', []);
$profile = data_get($player, 'profileInfo', []);
$diamonds = data_get($player, 'diamondCostRes', []);

$regCode = strtoupper((string) ($basic['region'] ?? $region));
$regCountry = $regionNames[$regCode] ?? $regCode;
$fullRegionStr = $regCountry !== $regCode ? "{$regCountry} ({$regCode})" : $regCode;

$formatDate = function ($ts) {
    if (!$ts || $ts === '0' || $ts === '--') return 'N/A';
    return date('F j, Y \a\t g:i A', (int) $ts);
};
$formatDateOnly = function ($ts) {
    if (!$ts || $ts === '0' || $ts === '--') return 'N/A';
    return date('F j, Y', (int) $ts);
};
$formatEnum = function ($val) {
    if (!$val) return 'N/A';
    $clean = preg_replace('/^(Gender_|Language_|ModePrefer_|RankShow_)/', '', (string)$val);
    return ucwords(str_replace('_', ' ', $clean));
};
@endphp
<main class="wrap">
<a class="back" href="{{ route('home') }}">← New Search</a>

<!-- Main Profile Card -->
<section class="profile-card">
  <img class="banner-bg" src="{{ $bannerUrl }}" alt="Clean Banner Background" onerror="this.style.display='none'">
  <div class="gradient-overlay"></div>
  <div class="profile-content">
    <img class="avatar-img" src="{{ $avatarUrl }}" alt="Player Avatar" onerror="this.style.visibility='hidden'">
    <div class="info-body">
      <div class="eyebrow">OFFICIAL PROFILE</div>
      <h1>{{ $basic['nickname'] ?? 'Player '.$uid }}</h1>
      <div class="meta">
        <span>Level <strong>{{ $basic['level'] ?? '—' }}</strong></span>
        <span class="meta-dot">•</span>
        <span>Likes <strong>{{ number_format((int) ($basic['liked'] ?? 0)) }}</strong></span>
        <span class="meta-dot">•</span>
        <span>Region <strong>{{ $fullRegionStr }}</strong></span>
      </div>
      <div class="id-open">
        Id open: {{ $formatDateOnly($basic['createAt'] ?? null) }}
      </div>
    </div>
  </div>
</section>

<!-- 5 Details Cards Grid -->
<div class="cards-grid">

  <!-- 1. ACCOUNT INFO -->
  <div class="info-card">
    <h3>Account Info</h3>
    <ul class="data-list">
      <li><span class="lbl">UID:</span> <strong>{{ $basic['accountId'] ?? $uid }}</strong></li>
      <li><span class="lbl">Name:</span> <strong>{{ $basic['nickname'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Level:</span> <strong>{{ $basic['level'] ?? '0' }}</strong></li>
      <li><span class="lbl">EXP:</span> <strong>{{ isset($basic['exp']) ? number_format((int)$basic['exp']) : '0' }}</strong></li>
      <li><span class="lbl">Region:</span> <strong>{{ $fullRegionStr }}</strong></li>
      <li><span class="lbl">Likes:</span> <strong>{{ number_format((int) ($basic['liked'] ?? 0)) }}</strong></li>
      <li><span class="lbl">Gender:</span> <strong>{{ $formatEnum($social['gender'] ?? null) }}</strong></li>
      <li><span class="lbl">Language:</span> <strong>{{ $formatEnum($social['language'] ?? null) }}</strong></li>
      <li><span class="lbl">Preferred Mode:</span> <strong>{{ $formatEnum($social['modePrefer'] ?? null) }}</strong></li>
      <li><span class="lbl">Season ID:</span> <strong>{{ $basic['seasonId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Credit Score:</span> <strong>{{ $credit['creditScore'] ?? '100' }}</strong></li>
      <li><span class="lbl">Pin ID:</span> <strong>{{ $basic['pinId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Title:</span> <strong>{{ $basic['title'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Bio:</span> <strong class="bio-text">{{ $social['signature'] ?? 'N/A' }}</strong></li>
    </ul>
  </div>

  <!-- 2. ACCOUNT ACTIVITY -->
  <div class="info-card">
    <h3>Account Activity</h3>
    <ul class="data-list">
      <li><span class="lbl">Release Version:</span> <strong>{{ $basic['releaseVersion'] ?? 'OB54' }}</strong></li>
      <li><span class="lbl">BR Rank Points:</span> <strong>{{ number_format((int) ($basic['rankingPoints'] ?? 0)) }}</strong></li>
      <li><span class="lbl">BR Max Rank:</span> <strong>{{ $basic['maxRank'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">CS Rank Points:</span> <strong>{{ number_format((int) ($basic['csRankingPoints'] ?? 0)) }}</strong></li>
      <li><span class="lbl">CS Rank:</span> <strong>{{ $basic['csRank'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">CS Max Rank:</span> <strong>{{ $basic['csMaxRank'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Diamond Spent:</span> <strong>{{ isset($diamonds['diamondCost']) ? number_format((int)$diamonds['diamondCost']) . ' Diamonds' : 'N/A' }}</strong></li>
      <li><span class="lbl">Rank Show Pref:</span> <strong>{{ $formatEnum($social['rankShow'] ?? null) }}</strong></li>
      <li><span class="lbl">Created At:</span> <strong>{{ $formatDate($basic['createAt'] ?? null) }}</strong></li>
      <li><span class="lbl">Last Login:</span> <strong>{{ $formatDate($basic['lastLoginAt'] ?? null) }}</strong></li>
    </ul>
  </div>

  <!-- 3. ACCOUNT & CHARACTER OVERVIEW -->
  <div class="info-card">
    <h3>Account Overview</h3>
    <ul class="data-list">
      <li><span class="lbl">Avatar (Head Pic):</span> <strong>{{ $basic['headPic'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Banner ID:</span> <strong>{{ $basic['bannerId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Character Model ID:</span> <strong>{{ $profile['avatarId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Awaken Selected:</span> <strong>{{ !empty($profile['isSelectedAwaken']) ? 'Yes' : 'No' }}</strong></li>
      <li><span class="lbl">Equipped Clothes:</span> <strong>{{ is_array($profile['clothes'] ?? null) ? count($profile['clothes']) . ' items' : 'N/A' }}</strong></li>
      <li><span class="lbl">Equipped Skills:</span> <strong>{{ is_array($profile['equipedSkills'] ?? null) ? floor(count($profile['equipedSkills']) / 4) . ' equipped' : 'N/A' }}</strong></li>
      <li><span class="lbl">BP Badges:</span> <strong>{{ $basic['badgeCnt'] ?? 0 }}</strong></li>
      <li><span class="lbl">BP ID:</span> <strong>{{ $basic['badgeId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Account Type:</span> <strong>{{ $basic['accountType'] ?? 1 }}</strong></li>
      <li><span class="lbl">Show BR Rank:</span> <strong>{{ !empty($basic['showBrRank']) ? 'Yes' : 'No' }}</strong></li>
      <li><span class="lbl">Show CS Rank:</span> <strong>{{ !empty($basic['showCsRank']) ? 'Yes' : 'No' }}</strong></li>
    </ul>
  </div>

  <!-- 4. PET DETAILS -->
  <div class="info-card">
    <h3>Pet Details</h3>
    <ul class="data-list">
      <li><span class="lbl">Pet ID:</span> <strong>{{ $pet['id'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Pet Level:</span> <strong>{{ $pet['level'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Pet Exp:</span> <strong>{{ isset($pet['exp']) ? number_format((int)$pet['exp']) : 'N/A' }}</strong></li>
      <li><span class="lbl">Pet Selected:</span> <strong>{{ !empty($pet['isSelected']) ? 'Yes' : 'No' }}</strong></li>
      <li><span class="lbl">Pet Skill ID:</span> <strong>{{ $pet['selectedSkillId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Pet Skin ID:</span> <strong>{{ $pet['skinId'] ?? 'N/A' }}</strong></li>
    </ul>
  </div>

  <!-- 5. GUILD INFO -->
  <div class="info-card full-width-card">
    <h3>Guild Info</h3>
    <ul class="data-list inline-grid-list">
      <li><span class="lbl">Guild Name:</span> <strong>{{ $clan['clanName'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Guild ID:</span> <strong>{{ $clan['clanId'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Guild Level:</span> <strong>{{ $clan['clanLevel'] ?? 'N/A' }}</strong></li>
      <li><span class="lbl">Guild Members:</span> <strong>{{ isset($clan['capacity']) ? ($clan['memberNum'] ?? 0).'/'.$clan['capacity'] : 'N/A' }}</strong></li>
    </ul>
    
    <div class="leader-info-block">
      <h4>Leader Info:</h4>
      @if(!empty($captain['nickname']))
        <p>
          Name: <strong>{{ $captain['nickname'] }}</strong> | 
          UID: <strong>{{ $captain['accountId'] ?? 'N/A' }}</strong> | 
          Level: <strong>{{ $captain['level'] ?? 'N/A' }}</strong> | 
          Likes: <strong>{{ number_format((int)($captain['liked'] ?? 0)) }}</strong> | 
          Created At: <strong>{{ $formatDate($captain['createAt'] ?? null) }}</strong> | 
          Last Login: <strong>{{ $formatDate($captain['lastLoginAt'] ?? null) }}</strong>
        </p>
      @else
        <p>No Guild Leader Information</p>
      @endif
    </div>
  </div>

</div>

<!-- Mode 2: Composited In-Game Graphic Banner -->
<section class="card composited-preview">
  <h2>In-Game Banner Render</h2>
  <img src="{{ $compositedBannerUrl }}" alt="Composited Banner Render">
</section>

<!-- Raw JSON Data -->
<section class="card">
  <h2>Player Data (API JSON)</h2>
  <pre class="json">{{ json_encode($player, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</pre>
</section>
</main>
</body>
</html>
