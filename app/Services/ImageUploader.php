<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reemplaza a la función global uploadImage() del sistema original.
 * Guarda en el disco público (storage/app/public/{folder}) en vez de
 * mover el archivo a mano — Laravel gestiona la validación y el nombre.
 * Requiere haber corrido `php artisan storage:link` una vez.
 */
class ImageUploader
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const EXT_MAP = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    public static function upload(?UploadedFile $file, string $folder, int $maxBytes = 5242880): string|false
    {
        if (!$file || !$file->isValid()) return false;

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED, true)) return false;
        if ($file->getSize() > $maxBytes) return false;

        $ext = self::EXT_MAP[$mime] ?? 'jpg';
        $name = 'img_' . Str::random(20) . '.' . $ext;

        Storage::disk('public')->putFileAs($folder, $file, $name);

        return "{$folder}/{$name}";
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
