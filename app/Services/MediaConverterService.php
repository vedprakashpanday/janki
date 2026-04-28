<?php
namespace App\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use App\Models\Media;

class MediaConverterService
{
    public function uploadAndConvert($file)
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'bmp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

        if (in_array($extension, $imageExtensions)) {
            return $this->processImage($file, $originalName);
        } elseif (in_array($extension, $videoExtensions)) {
            return $this->processVideo($file, $originalName);
        }

        return null;
    }

    private function processImage($file, $originalName)
    {
        $fileName = time() . '_' . uniqid() . '.webp';
        $subFolder = 'uploads/images';
        $uploadPath = public_path($subFolder);

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true);
        }

        // Image Processing to WebP
        $img = Image::make($file->getRealPath())->encode('webp', 80);

        // Max 1MB Logic
        if (strlen($img) > 1024 * 1024) {
            $img->encode('webp', 60);
        }

        $img->save($uploadPath . '/' . $fileName);

        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'image', 'webp', filesize($uploadPath . '/' . $fileName));
    }

    private function processVideo($file, $originalName)
    {
        $fileName = time() . '_' . uniqid() . '.mp4';
        $subFolder = 'uploads/videos';
        $uploadPath = public_path($subFolder);
        $tempPath = $file->getRealPath();

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true);
        }

        $destination = $uploadPath . '/' . $fileName;

        // FFMPEG Command for Compression (Max 3MB target)
        // Note: Ensure FFMPEG is installed on your server/system
        $command = "ffmpeg -i $tempPath -vcodec h264 -crf 28 -acodec mp3 $destination 2>&1";
        shell_exec($command);

        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'video', 'mp4', filesize($destination));
    }

    private function saveToDb($name, $path, $type, $ext, $size)
    {
        // Convert size to readable format
        $readableSize = ($size >= 1048576) ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 2) . ' KB';

        return Media::create([
            'original_name' => $name,
            'file_path' => $path,
            'file_type' => $type,
            'extension' => $ext,
            'file_size' => $readableSize,
        ]);
    }
}