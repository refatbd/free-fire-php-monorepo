<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

use Refatbd\FreeFire\Exception\MediaException;

final class GdPlayerMediaRenderer implements PlayerMediaRendererInterface
{
    /** @var list<array{0:array{0:int,1:int,2:int},1:array{0:int,1:int,2:int}}> */
    private const PALETTES = [
        [[88, 19, 55], [30, 27, 75]],
        [[120, 53, 15], [69, 26, 3]],
        [[76, 29, 149], [46, 16, 101]],
        [[12, 74, 110], [8, 47, 73]],
        [[6, 78, 59], [2, 44, 34]],
    ];

    /** @var array<string,bool> */
    private array $glyphSupportCache = [];

    /** @var array<string,string|null> */
    private array $glyphSignatureCache = [];

    public function __construct(
        private readonly OfficialImageLoader $images,
        private readonly FontResolver $fonts = new FontResolver(),
        private readonly int $quality = 92,
    ) {}

    public function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    public function avatar(array $player, int $size = 512): RenderedMedia
    {
        $this->assertAvailable();
        $size = max(128, min($size, 1024));
        $basic = $this->basic($player);
        $official = false;

        try {
            $image = $this->images->load((string) ($basic['headPic'] ?? $basic['headpic'] ?? ''));
            $official = true;
        } catch (\Throwable) {
            $image = $this->avatarFallback(
                $size,
                (string) ($basic['headPic'] ?? $basic['accountId'] ?? $basic['accountid'] ?? '0'),
            );
        }

        $canvas = $this->create($size, $size, [15, 23, 42]);
        $this->cover($canvas, $image, 0, 0, $size, $size);
        if ($image !== $canvas) {
            imagedestroy($image);
        }

        $accent = imagecolorallocate($canvas, 245, 158, 11);
        $border = max(4, intdiv($size, 80));
        imagesetthickness($canvas, $border);
        $this->roundedRectangle(
            $canvas,
            intdiv($border, 2),
            intdiv($border, 2),
            $size - intdiv($border, 2) - 1,
            $size - intdiv($border, 2) - 1,
            max(12, intdiv($size, 24)),
            $accent,
        );

        return new RenderedMedia(
            $this->webp($canvas),
            'image/webp',
            $official ? 'official-free-fire-cdn' : 'local-fallback',
            false,
            $official,
        );
    }

    public function banner(array $player, int $width = 1000, int $height = 250, bool $raw = false): RenderedMedia
    {
        $this->assertAvailable();
        $width = max(800, min($width, 1600));
        $height = max(200, min($height, 400));
        $basic = $this->basic($player);
        $clan = $this->clan($player);
        $officialBanner = false;
        $officialAvatar = false;

        try {
            $background = $this->images->load((string) ($basic['bannerId'] ?? $basic['bannerid'] ?? ''));
            $officialBanner = true;
        } catch (\Throwable) {
            $background = $this->gradient(
                $width,
                $height,
                (string) ($basic['bannerId'] ?? $basic['accountId'] ?? $basic['accountid'] ?? '0'),
            );
        }

        $canvas = $this->create($width, $height, [10, 15, 28]);
        $this->cover($canvas, $background, 0, 0, $width, $height);
        if ($background !== $canvas) {
            imagedestroy($background);
        }
        if ($officialBanner) {
            $this->enhanceOfficialBackground($canvas);
        }

        if ($raw) {
            return new RenderedMedia(
                $this->webp($canvas, min($this->quality, 91)),
                'image/webp',
                $officialBanner ? 'official-free-fire-cdn' : 'local-fallback',
                $officialBanner,
                false,
            );
        }

        $this->drawReadableOverlay($canvas, $width, $height);

        $avatarSize = (int) ($height * 0.72);
        $avatarX = (int) ($height * 0.12);
        $avatarY = intdiv($height - $avatarSize, 2);
        try {
            $avatar = $this->images->load((string) ($basic['headPic'] ?? $basic['headpic'] ?? ''));
            $officialAvatar = true;
        } catch (\Throwable) {
            $avatar = $this->avatarFallback(
                $avatarSize,
                (string) ($basic['headPic'] ?? $basic['accountId'] ?? $basic['accountid'] ?? '0'),
            );
        }

        $this->cover($canvas, $avatar, $avatarX, $avatarY, $avatarSize, $avatarSize);
        imagedestroy($avatar);
        $border = imagecolorallocate($canvas, 255, 255, 255);
        imagesetthickness($canvas, 3);
        imagerectangle(
            $canvas,
            $avatarX,
            $avatarY,
            $avatarX + $avatarSize - 1,
            $avatarY + $avatarSize - 1,
            $border,
        );

        $textX = $avatarX + $avatarSize + 28;
        $nickname = (string) ($basic['nickname'] ?? 'Unknown Player');
        $rawClan = (string) ($clan['clanName'] ?? $clan['clanname'] ?? '');
        $clanName = str_replace(["\u{3164}", "\u{2800}"], '  ', $rawClan);
        $level = (string) ($basic['level'] ?? '--');

        $titleSize = max(38, (int) ($height * 0.18));
        $clanSize = max(30, (int) ($height * 0.14));
        $levelSize = max(36, (int) ($height * 0.17));
        $textRight = $width - max(20, (int) ($height * 0.08));

        $nickname = $this->fitText($nickname, $titleSize, max(80, $textRight - $textX));
        $this->textTop(
            $canvas,
            $nickname,
            $textX,
            (int) ($height * 0.15),
            $titleSize,
            [255, 255, 255],
            3,
        );

        if ($clanName !== '') {
            $clanName = $this->fitText($clanName, $clanSize, max(80, $textRight - $textX));
            $this->textTop(
                $canvas,
                $clanName,
                $textX,
                (int) ($height * 0.52),
                $clanSize,
                [255, 255, 255],
                3,
            );
        }

        $levelLabel = 'Lvl. '.$level;
        $fonts = $this->fonts->all();
        $fontFile = $fonts[0] ?? '';
        $levelBox = @imagettfbbox($levelSize, 0, $fontFile, $levelLabel);
        $levelWidth = is_array($levelBox) ? abs($levelBox[2] - $levelBox[0]) : (int)($levelSize * 3.5);
        $levelX = max($textX, $width - $levelWidth - 28);
        $this->textTop(
            $canvas,
            $levelLabel,
            $levelX,
            (int) ($height * 0.55),
            $levelSize,
            [255, 255, 255],
            3,
        );

        $source = match (true) {
            $officialBanner && $officialAvatar => 'official-free-fire-cdn',
            $officialBanner || $officialAvatar => 'official-free-fire-cdn+local-fallback',
            default => 'local-fallback',
        };

        return new RenderedMedia(
            $this->webp($canvas, min($this->quality, 91)),
            'image/webp',
            $source,
            $officialBanner,
            $officialAvatar,
        );
    }

    /** @return array<string,mixed> */
    private function basic(array $player): array
    {
        return is_array($player['basicInfo'] ?? null)
            ? $player['basicInfo']
            : (is_array($player['basicinfo'] ?? null) ? $player['basicinfo'] : []);
    }

    /** @return array<string,mixed> */
    private function clan(array $player): array
    {
        return is_array($player['clanBasicInfo'] ?? null)
            ? $player['clanBasicInfo']
            : (is_array($player['clanbasicinfo'] ?? null) ? $player['clanbasicinfo'] : []);
    }

    private function assertAvailable(): void
    {
        if (!$this->available()) {
            throw new MediaException('GD WebP rendering is unavailable.');
        }
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function create(int $width, int $height, array $rgb): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if (!$image instanceof \GdImage) {
            throw new MediaException('Could not create image canvas.');
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        return $image;
    }

    private function gradient(int $width, int $height, string $seed): \GdImage
    {
        $hash = hash('sha256', $seed, true);
        [$first, $second] = self::PALETTES[ord($hash[0]) % count(self::PALETTES)];

        // Build a small diagonal gradient and scale it up. This preserves the
        // Pillow layout without allocating hundreds of thousands of GD colors.
        $small = $this->create(64, 64, $first);
        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $ratio = min(1.0, ($x / 63) * 0.82 + ($y / 63) * 0.18);
                $color = imagecolorallocate(
                    $small,
                    (int) ($first[0] * (1 - $ratio) + $second[0] * $ratio),
                    (int) ($first[1] * (1 - $ratio) + $second[1] * $ratio),
                    (int) ($first[2] * (1 - $ratio) + $second[2] * $ratio),
                );
                imagesetpixel($small, $x, $y, $color);
            }
        }
        $result = $this->create($width, $height, $first);
        imagecopyresampled($result, $small, 0, 0, 0, 0, $width, $height, 64, 64);
        imagedestroy($small);
        return $result;
    }

    private function avatarFallback(int $size, string $seed): \GdImage
    {
        $avatar = $this->gradient($size, $size, $seed);
        $hash = hash('sha256', $seed, true);
        [$first, $second] = self::PALETTES[ord($hash[0]) % count(self::PALETTES)];
        $headColor = imagecolorallocate($avatar, ...$second);
        $bodyColor = imagecolorallocate($avatar, ...$first);
        $center = intdiv($size, 2);
        $radius = (int) ($size * 0.18);
        imagefilledellipse(
            $avatar,
            $center,
            (int) ($size * 0.37),
            $radius * 2,
            (int) ($size * 0.36),
            $headColor,
        );
        imagefilledellipse(
            $avatar,
            $center,
            (int) ($size * 0.80),
            (int) ($size * 0.58),
            (int) ($size * 0.56),
            $bodyColor,
        );
        return $avatar;
    }

    private function cover(\GdImage $destination, \GdImage $source, int $x, int $y, int $width, int $height): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            throw new MediaException('Source image has invalid dimensions.');
        }
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = max(1, (int) round($width / $scale));
        $cropHeight = max(1, (int) round($height / $scale));
        $sourceX = max(0, intdiv($sourceWidth - $cropWidth, 2));
        $sourceY = max(0, intdiv($sourceHeight - $cropHeight, 2));
        imagecopyresampled(
            $destination,
            $source,
            $x,
            $y,
            $sourceX,
            $sourceY,
            $width,
            $height,
            $cropWidth,
            $cropHeight,
        );
    }

    private function enhanceOfficialBackground(\GdImage $image): void
    {
        if (defined('IMG_FILTER_SMOOTH')) {
            @imagefilter($image, IMG_FILTER_SMOOTH, 2);
        }
    }

    private function drawReadableOverlay(\GdImage $image, int $width, int $height): void
    {
        $limit = max(1, (int) ($width * 0.65));
        for ($x = 0; $x < $limit; $x++) {
            $opacity = 0.55 * (1.0 - ($x / $limit));
            $alpha = max(0, min(127, (int) round(127 * (1.0 - $opacity))));
            $color = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
            imageline($image, $x, 0, $x, $height, $color);
        }
    }

    /** @param array{0:int,1:int,2:int} $rgb */
    private function textTop(
        \GdImage $image,
        string $text,
        int $x,
        int $top,
        int $size,
        array $rgb,
        int $strokeWidth = 0,
    ): void {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $fonts = $this->fonts->all();
        $color = imagecolorallocate($image, ...$rgb);
        if ($fonts === [] || !function_exists('imagettftext')) {
            $ascii = preg_replace('/[^\x20-\x7E]/', '?', $text) ?: '';
            imagestring(
                $image,
                min(5, max(1, intdiv($size, 6))),
                $x,
                max(0, $top),
                $ascii,
                $color,
            );
            return;
        }

        $cursor = $x;
        $baseline = $top + $size;
        $stroke = $strokeWidth > 0
            ? imagecolorallocate($image, 0, 0, 0)
            : null;

        foreach ($this->characters($text) as $character) {
            $font = $this->fontForCharacter($fonts, $character, $size) ?? $fonts[0];
            if ($strokeWidth > 0 && is_int($stroke)) {
                for ($offsetX = -$strokeWidth; $offsetX <= $strokeWidth; $offsetX++) {
                    for ($offsetY = -$strokeWidth; $offsetY <= $strokeWidth; $offsetY++) {
                        if ($offsetX === 0 && $offsetY === 0) {
                            continue;
                        }
                        @imagettftext(
                            $image,
                            $size,
                            0,
                            $cursor + $offsetX,
                            $baseline + $offsetY,
                            $stroke,
                            $font,
                            $character,
                        );
                    }
                }
            }
            @imagettftext($image, $size, 0, $cursor, $baseline, $color, $font, $character);
            $cursor += $this->characterAdvance($font, $character, $size);
        }
    }

    private function fitText(string $text, int $size, int $maxWidth): string
    {
        $fonts = $this->fonts->all();
        if ($fonts === [] || !function_exists('imagettfbbox') || $maxWidth < 1) {
            return $text;
        }

        $characters = $this->characters($text);
        if ($this->textWidth($characters, $fonts, $size) <= $maxWidth) {
            return $text;
        }

        while (count($characters) > 2) {
            array_pop($characters);
            array_pop($characters);
            $candidate = array_merge($characters, ['…']);
            if ($this->textWidth($candidate, $fonts, $size) <= $maxWidth) {
                return implode('', $candidate);
            }
            $characters = $candidate;
            array_pop($characters);
        }

        return implode('', $characters);
    }

    /** @param list<string> $characters @param list<string> $fonts */
    private function textWidth(array $characters, array $fonts, int $size): int
    {
        $width = 0;
        foreach ($characters as $character) {
            $font = $this->fontForCharacter($fonts, $character, $size) ?? $fonts[0];
            $width += $this->characterAdvance($font, $character, $size);
        }
        return $width;
    }

    /** @param list<string> $fonts */
    private function fontForCharacter(array $fonts, string $character, int $size): ?string
    {
        foreach ($fonts as $font) {
            if ($this->fontSupportsCharacter($font, $character, $size)) {
                return $font;
            }
        }
        return $fonts[0] ?? null;
    }

    private function fontSupportsCharacter(string $font, string $character, int $size): bool
    {
        if (preg_match('/^\s$/u', $character) === 1) {
            return true;
        }
        $key = $font."\0".$size."\0".$character;
        if (array_key_exists($key, $this->glyphSupportCache)) {
            return $this->glyphSupportCache[$key];
        }

        $signature = $this->glyphSignature($font, $character, $size);
        if ($signature === null) {
            return $this->glyphSupportCache[$key] = false;
        }
        if ($character === "\u{FFFD}" || $character === "\u{FFFF}") {
            return $this->glyphSupportCache[$key] = true;
        }

        $missing = $this->glyphSignature($font, "\u{FFFF}", $size);
        $replacement = $this->glyphSignature($font, "\u{FFFD}", $size);
        return $this->glyphSupportCache[$key] = $signature !== $missing && $signature !== $replacement;
    }

    private function glyphSignature(string $font, string $character, int $size): ?string
    {
        $key = $font."\0".$size."\0".$character;
        if (array_key_exists($key, $this->glyphSignatureCache)) {
            return $this->glyphSignatureCache[$key];
        }
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
            return $this->glyphSignatureCache[$key] = null;
        }

        $dimension = max(64, $size * 3);
        $image = imagecreatetruecolor($dimension, $dimension);
        if (!$image instanceof \GdImage) {
            return $this->glyphSignatureCache[$key] = null;
        }
        $background = imagecolorallocate($image, 0, 0, 0);
        $foreground = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $background);
        $result = @imagettftext($image, $size, 0, 4, $size * 2, $foreground, $font, $character);
        if (!is_array($result)) {
            imagedestroy($image);
            return $this->glyphSignatureCache[$key] = null;
        }

        $pixels = '';
        $ink = false;
        for ($y = 0; $y < $dimension; $y++) {
            for ($x = 0; $x < $dimension; $x++) {
                $set = (imagecolorat($image, $x, $y) & 0xFFFFFF) !== 0;
                $ink = $ink || $set;
                $pixels .= $set ? "1" : "0";
            }
        }
        imagedestroy($image);

        return $this->glyphSignatureCache[$key] = $ink ? hash('sha256', $pixels) : null;
    }

    private function characterAdvance(string $font, string $character, int $size): int
    {
        $box = @imagettfbbox($size, 0, $font, $character);
        if (is_array($box)) {
            $xs = [$box[0], $box[2], $box[4], $box[6]];
            return max(1, max($xs) - min($xs));
        }
        return max(1, (int) round($size * 0.38));
    }

    /** @return list<string> */
    private function characters(string $text): array
    {
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($characters) ? array_values($characters) : str_split($text);
    }

    private function roundedRectangle(
        \GdImage $image,
        int $left,
        int $top,
        int $right,
        int $bottom,
        int $radius,
        int $color,
    ): void {
        $radius = max(1, min($radius, intdiv(max(2, $right - $left), 2), intdiv(max(2, $bottom - $top), 2)));
        imageline($image, $left + $radius, $top, $right - $radius, $top, $color);
        imageline($image, $left + $radius, $bottom, $right - $radius, $bottom, $color);
        imageline($image, $left, $top + $radius, $left, $bottom - $radius, $color);
        imageline($image, $right, $top + $radius, $right, $bottom - $radius, $color);
        imagearc($image, $left + $radius, $top + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($image, $right - $radius, $top + $radius, $radius * 2, $radius * 2, 270, 360, $color);
        imagearc($image, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 90, $color);
        imagearc($image, $left + $radius, $bottom - $radius, $radius * 2, $radius * 2, 90, 180, $color);
    }

    private function webp(\GdImage $image, ?int $quality = null): string
    {
        ob_start();
        $ok = imagewebp($image, null, max(1, min($quality ?? $this->quality, 100)));
        $data = ob_get_clean();
        imagedestroy($image);
        if (!$ok || !is_string($data) || $data === '') {
            throw new MediaException('Could not encode WebP image.');
        }
        return $data;
    }
}
