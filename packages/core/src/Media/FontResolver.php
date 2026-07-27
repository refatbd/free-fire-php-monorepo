<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Media;

final class FontResolver
{
    /** @param list<string> $candidates */
    public function __construct(private readonly array $candidates = []) {}

    public function resolve(): ?string
    {
        return $this->all()[0] ?? null;
    }

    /** @return list<string> */
    public function all(): array
    {
        $packageFonts = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'fonts';
        $paths = array_merge($this->candidates, [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\segoeuib.ttf',
            'C:\\Windows\\Fonts\\impact.ttf',
            'C:\\Windows\\Fonts\\trebucbd.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSans-Bold.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSansCherokee-Bold.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSansSC-Bold.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSansMath-Regular.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSansSymbols-Regular.ttf',
            $packageFonts.DIRECTORY_SEPARATOR.'NotoSansSymbols2-Regular.ttf',
            '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Bold.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            'C:\\Windows\\Fonts\\himalaya.ttf',
            'C:\\Windows\\Fonts\\seguisym.ttf',
            'C:\\Windows\\Fonts\\seguihis.ttf',
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\msyh.ttc',
            'C:\\Windows\\Fonts\\arial.ttf',
        ]);

        $available = [];
        foreach (array_unique(array_filter($paths)) as $path) {
            if (is_file($path) && is_readable($path)) {
                $available[] = $path;
            }
        }
        return $available;
    }
}
