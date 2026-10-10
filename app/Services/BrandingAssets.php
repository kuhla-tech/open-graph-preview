<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandingAssets
{
    public function store(UploadedFile $file, string $kind): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $dimensions = @getimagesizefromstring($bytes);
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 20000000) {
            throw ValidationException::withMessages([$kind => __('Das Bild ist ungültig oder zu groß (maximal 20 Megapixel).')]);
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            throw ValidationException::withMessages([$kind => __('Das Bild konnte nicht verarbeitet werden.')]);
        }
        $id = Str::uuid()->toString();
        $result = [];
        $logoScale = min(800 / $dimensions[0], 320 / $dimensions[1], 1);
        $sizes = $kind === 'icon' ? ['icon_32' => [32, 32], 'icon_180' => [180, 180], 'icon_192' => [192, 192], 'icon_512' => [512, 512]] : [$kind => in_array($kind, ['social_image', 'social_image_de', 'social_image_en'], true) ? [1200, 630] : [max(1, (int) round($dimensions[0] * $logoScale)), max(1, (int) round($dimensions[1] * $logoScale))]];
        try {
            foreach ($sizes as $key => [$width, $height]) {
                $target = imagecreatetruecolor($width, $height);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
                $scale = in_array($kind, ['social_image', 'social_image_de', 'social_image_en'], true) ? max($width / $dimensions[0], $height / $dimensions[1]) : min($width / $dimensions[0], $height / $dimensions[1]);
                $scaledWidth = (int) round($dimensions[0] * $scale);
                $scaledHeight = (int) round($dimensions[1] * $scale);
                imagecopyresampled($target, $source, (int) (($width - $scaledWidth) / 2), (int) (($height - $scaledHeight) / 2), 0, 0, $scaledWidth, $scaledHeight, $dimensions[0], $dimensions[1]);
                ob_start();
                imagepng($target);
                $png = ob_get_clean();
                imagedestroy($target);
                $filename = $id.'-'.$key.'.png';
                if (! Storage::disk('public')->put('branding/'.$filename, $png)) {
                    throw ValidationException::withMessages([$kind => __('Das Bild konnte nicht gespeichert werden. Prüfe die Schreibrechte.')]);
                }
                $result['asset_'.$key] = '/media/branding/'.$filename;
                if ($key === 'icon_32') {
                    $ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png), 22).$png;
                    if (! Storage::disk('public')->put('branding/'.$id.'.ico', $ico)) {
                        throw ValidationException::withMessages([$kind => __('Das Bild konnte nicht gespeichert werden. Prüfe die Schreibrechte.')]);
                    }
                    $result['asset_favicon'] = '/media/branding/'.$id.'.ico';
                }
            }
        } finally {
            imagedestroy($source);
        }

        return $result;
    }
}
