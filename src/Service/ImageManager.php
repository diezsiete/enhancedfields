<?php

namespace PrestaShop\Module\EnhancedFields\Service;

use Hook;
use Symfony\Component\Filesystem\Filesystem;

class ImageManager
{
    public function __construct(
        private readonly Filesystem $filesystem
    ){}

    public static function filenamePlaceholder(string $filename): string
    {
        return preg_replace('/(.+)(-\w+)(\.\w+$)/', '$1$3', $filename);
    }

    public function moveTempImage(?string $tempPath, string $definitiveDir): false|string
    {
        if ($tempImageName = $this->getTempImageName($tempPath)) {
            $image = $this->getDifinitivePath($definitiveDir, $tempImageName);
            $this->filesystem->copy(_PS_TMP_IMG_DIR_ . $tempImageName, $image);
            Hook::exec('actionOnEnhancedImageMoved', ['image' => $image]);
        }

        return $tempImageName;
    }

    public function getTempImageName(?string $tempPath): false|string
    {
        if ($tempPath) {
            $tempPathExplode = explode('/', $tempPath);
            if ($tempPathExplode[0] === 'temp') {
                return $tempPathExplode[1];
            }
        }
        return false;
    }

    public function removeDefinitiveImage(string $definitiveDir, string $imageName): void
    {
        $image = $this->getDifinitivePath($definitiveDir, $imageName);
        if (file_exists($image)) {
            unlink($image);
            // TODO validate webp and avi cases if enabled in admin
            if (($webpPath = $this->changeNameExtension($image, 'webp')) && file_exists($webpPath)) {
                unlink($webpPath);
            }
            Hook::exec('actionOnEnhancedImageRemoved', ['image' => $image]);
        }
    }

    public function handleImageSubmision(string $definitiveDir, ?string $newImage, ?string $currentImage = null): false|null|string
    {
        if ($currentImage && ($this->getTempImageName($newImage) || !$newImage)) {
            $this->removeDefinitiveImage($definitiveDir, $currentImage);
        }
        return !$newImage && $currentImage ? null : $this->moveTempImage($newImage, $definitiveDir);
    }

    private function getDifinitivePath(string $definitiveDir, string $definitiveName): string
    {
        return $definitiveDir . '/' . ltrim($definitiveName, '/');
    }

    private function changeNameExtension(string $name, string $extension): ?string
    {
        return ($dotpos = strrpos($name, '.')) !== false ? substr($name, 0, $dotpos) . ".$extension" : null;
    }
}
