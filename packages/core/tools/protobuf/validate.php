<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$protocolRoot = $root.'/protocol';
$directories = glob($protocolRoot.'/ob*', GLOB_ONLYDIR) ?: [];
natsort($directories);
$failed = false;

if ($directories === []) {
    fwrite(STDERR, "No versioned protocol directories found.\n");
    exit(1);
}

foreach ($directories as $directory) {
    $folder = basename($directory);
    if (!preg_match('/^ob(\d{1,4})$/i', $folder, $match)) {
        fwrite(STDERR, "Invalid protocol directory name: {$folder}\n");
        $failed = true;
        continue;
    }
    $namespaceVersion = 'Ob'.$match[1];
    $obVersion = 'OB'.$match[1];
    $profileFile = $root.'/src/Protocol/Profiles/'.$namespaceVersion.'ProtocolProfile.php';
    if (!is_file($profileFile)) {
        fwrite(STDERR, "Missing protocol profile for {$obVersion}: {$profileFile}\n");
        $failed = true;
    } else {
        $profileSource = (string) file_get_contents($profileFile);
        if (!str_contains($profileSource, "return '{$obVersion}';")) {
            fwrite(STDERR, "Protocol profile {$namespaceVersion} must return {$obVersion}.\n");
            $failed = true;
        }
        if (!str_contains($profileSource, 'Generated\\\\'.$namespaceVersion.'\\\\')) {
            fwrite(STDERR, "Protocol profile {$namespaceVersion} must select its versioned generated response class.\n");
            $failed = true;
        }
    }
    $builtIns = (string) file_get_contents($root.'/src/Protocol/BuiltInProtocolProfiles.php');
    if (!str_contains($builtIns, $namespaceVersion.'ProtocolProfile::class')) {
        fwrite(STDERR, "{$obVersion} is not registered in BuiltInProtocolProfiles.\n");
        $failed = true;
    }
    $files = glob($directory.'/*.proto') ?: [];
    if ($files === []) {
        fwrite(STDERR, "No protocol sources found in {$folder}.\n");
        $failed = true;
        continue;
    }

    foreach ($files as $file) {
        $source = (string) file_get_contents($file);
        if ($source === '') {
            fwrite(STDERR, "Empty protocol source: {$file}\n");
            $failed = true;
            continue;
        }
        $package = '';
        if (preg_match('/^package\s+([A-Za-z0-9_.]+)\s*;/mi', $source, $packageMatch)) {
            $package = strtolower($packageMatch[1]);
        }
        if (!in_array(strtolower($namespaceVersion), explode('.', $package), true)) {
            fwrite(STDERR, basename($file)." must use an OB-versioned Protobuf package containing ".strtolower($namespaceVersion).".\n");
            $failed = true;
        }
        $namespaceNeedle = 'Generated\\\\'.$namespaceVersion.'\\\\';
        if (!str_contains($source, $namespaceNeedle)) {
            fwrite(STDERR, basename($file)." must use an OB-versioned PHP namespace containing {$namespaceVersion}.\n");
            $failed = true;
        }
        $metadataNeedle = 'Generated\\\\'.$namespaceVersion.'\\\\Metadata';
        if (!str_contains($source, $metadataNeedle)) {
            fwrite(STDERR, basename($file)." must use the {$namespaceVersion} metadata namespace.\n");
            $failed = true;
        }
    }
}

$required = [
    'protocol/ob54/AccountPersonalShow.proto',
    'protocol/ob54/LegacyLogin.proto',
    'protocol/ob54/PlayerRequest.proto',
];
foreach ($required as $relative) {
    $file = $root.'/'.$relative;
    if (!is_file($file) || filesize($file) === 0) {
        fwrite(STDERR, "Missing {$relative}\n");
        $failed = true;
    }
}

$player = (string) file_get_contents($root.'/protocol/ob54/AccountPersonalShow.proto');
$playerChecks = [
    'package freefire.ob54;',
    'Generated\\\\Ob54\\\\AccountPersonalShow',
    'message AccountPersonalShowInfo',
    'optional AccountInfoBasic basic_info = 1;',
    'optional ClanInfoBasic clan_basic_info = 6;',
    'optional SocialBasicInfo social_info = 9;',
    'repeated EquipAchInfo equipped_ach = 13;',
    'string clan_name = 8;',
    'repeated uint32 equiped_skills = 5;',
    'SocialHighLightsWithSocialBasicInfo social_high_lights_with_basic_info = 61;',
];
foreach ($playerChecks as $needle) {
    if (!str_contains($player, $needle)) {
        fwrite(STDERR, "Player schema mismatch: {$needle}\n");
        $failed = true;
    }
}

$login = (string) file_get_contents($root.'/protocol/ob54/LegacyLogin.proto');
foreach ([
    'Generated\\\\Ob54\\\\LegacyLogin',
    'string open_id = 22;',
    'string login_token = 29;',
    'string orign_platform_type = 99;',
    'string server_url = 10;',
] as $needle) {
    if (!str_contains($login, $needle)) {
        fwrite(STDERR, "Login schema mismatch: {$needle}\n");
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}
echo 'Protocol source validation passed for '.count($directories)." OB profile(s).\n";
