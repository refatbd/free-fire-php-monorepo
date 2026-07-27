<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
use Refatbd\FreeFire\Exception\MediaException;

final class OfficialImageLoader
{
    public function __construct(
        private readonly OfficialAssetDownloader $downloader,
        private readonly AstcDecoderInterface $decoder,
        private readonly string $temporaryDirectory,
    ) {}

    /** @return \GdImage */
    public function load(string|int $itemId): \GdImage
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
            throw new MediaException('GD with PNG support is unavailable.');
        }
        if (!$this->decoder->available()) throw new MediaException('ASTC decoder is unavailable.');
        if (!is_dir($this->temporaryDirectory) && !mkdir($this->temporaryDirectory, 0700, true) && !is_dir($this->temporaryDirectory)) {
            throw new MediaException('Cannot create media temporary directory.');
        }
        $download = $this->downloader->download($itemId);
        $id = bin2hex(random_bytes(12));
        $input = rtrim($this->temporaryDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$id.'.astc';
        $output = rtrim($this->temporaryDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$id.'.png';
        try {
            if (file_put_contents($input, $download['data'], LOCK_EX) === false) throw new MediaException('Could not write temporary ASTC file.');
            @chmod($input, 0600);
            $this->decoder->decodeFile($input, $output);
            $image = @imagecreatefrompng($output);
            if (!$image instanceof \GdImage) throw new MediaException('Decoded PNG could not be opened.');
            imagealphablending($image, true); imagesavealpha($image, true);
            $rotated = imagerotate($image, 180, imagecolorallocatealpha($image, 0, 0, 0, 127));
            if ($rotated instanceof \GdImage) { imagedestroy($image); $image = $rotated; imagesavealpha($image, true); }
            return $image;
        } finally { @unlink($input); @unlink($output); }
    }
}
