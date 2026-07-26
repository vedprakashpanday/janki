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
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'heic'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
        $pdfExtensions = ['pdf'];

        if (in_array($extension, $imageExtensions)) {
            return $this->processImage($file, $originalName);
        } elseif (in_array($extension, $videoExtensions)) {
            return $this->processVideo($file, $originalName);
        } elseif (in_array($extension, $pdfExtensions)) {
            return $this->processPdf($file, $originalName);
        }

        // Any other raw file (Word, Excel)
        return $this->processRawFile($file, $originalName, $extension);
    }

    private function processImage($file, $originalName)
    {
        $fileName = time() . '_' . uniqid() . '.webp';
        $subFolder = 'uploads/images';
        $uploadPath = public_path($subFolder);

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true);
        }

        // Image Processing to WebP with Heavy Compression
        $img = Image::make($file->getRealPath());
        
        // Agar image bohot badi hai, toh width max 1000px tak scale down karein (aspect ratio maintain karke)
        if ($img->width() > 1000) {
            $img->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Save as WebP with 50-60% quality to bring size between 100kb-200kb
        $img->encode('webp', 60)->save($uploadPath . '/' . $fileName);

        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'image', 'webp', filesize($uploadPath . '/' . $fileName));
    }

    private function processPdf($file, $originalName)
    {
        $fileName = time() . '_' . uniqid() . '.pdf';
        $subFolder = 'uploads/pdfs';
        $uploadPath = public_path($subFolder);
        $tempPath = $file->getRealPath();

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true);
        }

        $destination = $uploadPath . '/' . $fileName;

        // 🔥 Ghostscript for ultra PDF compression (Targets 50kb-100kb) 🔥
        // Quality levels: /screen (lowest, good for viewing), /ebook (medium), /printer (high)
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($destination) . " " . escapeshellarg($tempPath) . " 2>&1";
        shell_exec($command);

        // Fallback agar ghostscript server me na ho
        if (!file_exists($destination) || filesize($destination) == 0) {
            move_uploaded_file($tempPath, $destination);
        }

        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'pdf', 'pdf', filesize($destination));
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

        // FFMPEG Command for Heavy Compression (Max 3MB target)
        $command = "ffmpeg -i " . escapeshellarg($tempPath) . " -vcodec libx264 -crf 30 -preset fast -acodec aac $destination 2>&1";
        shell_exec($command);

        if (!file_exists($destination) || filesize($destination) == 0) {
            move_uploaded_file($tempPath, $destination);
        }

        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'video', 'mp4', filesize($destination));
    }

    private function processRawFile($file, $originalName, $extension)
    {
        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $subFolder = 'uploads/documents';
        $uploadPath = public_path($subFolder);

        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true);
        }

        $file->move($uploadPath, $fileName);
        return $this->saveToDb($originalName, $subFolder . '/' . $fileName, 'document', $extension, filesize($uploadPath . '/' . $fileName));
    }

    private function saveToDb($name, $path, $type, $ext, $size)
    {
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