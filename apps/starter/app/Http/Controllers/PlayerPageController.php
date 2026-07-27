<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Media\MediaVersion;

final class PlayerPageController
{
    public function show(Request $request, FreeFireClient $client): View|RedirectResponse
    {
        $data = $request->validate([
            'uid' => ['required', 'regex:/^\d{5,20}$/'],
            'region' => ['nullable', 'string', 'max:10'],
        ]);
        $uid = (string) $data['uid'];
        $regionInput = !empty($data['region']) ? strtoupper((string) $data['region']) : null;

        try {
            $player = $client->player($uid, $regionInput);
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors([
                'uid' => 'Player information could not be loaded. ' . $e->getMessage(),
            ]);
        }

        $region = (string) ($player['basicInfo']['region'] ?? $regionInput ?? 'BD');

        $avatarVersion = MediaVersion::avatar($player);
        $bannerVersion = MediaVersion::banner($player);

        return view('player', [
            'player' => $player,
            'uid' => $uid,
            'region' => $region,
            'avatarUrl' => route('freefire.avatar', [
                'uid' => $uid,
                'region' => $region,
                'v' => $avatarVersion,
            ]),
            'bannerUrl' => route('freefire.banner', [
                'uid' => $uid,
                'region' => $region,
                'raw' => '1',
                'v' => $bannerVersion.'_raw',
            ]),
            'compositedBannerUrl' => route('freefire.banner', [
                'uid' => $uid,
                'region' => $region,
                'v' => $bannerVersion.'_clean_v2',
            ]),
        ]);
    }
}
