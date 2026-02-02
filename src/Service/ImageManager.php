<?php

namespace PrestaShop\Module\EnhancedFields\Service;

use ImageManager as ImageManagerCore;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageUploadException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\MemoryLimitException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\UploadedImageConstraintException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tools;

class ImageManager
{
    /**
     * basedon \PrestaShop\PrestaShop\Adapter\Image\Uploader\ManufacturerImageUploader::upload
     * @throws UploadedImageConstraintException|ImageUploadException|MemoryLimitException
     */
    public function moveUploadedImage(UploadedFile $image, ?string $name = null): string
    {
        $this->checkImageIsAllowedForUpload($image);
        $tempImageName = $name ? _PS_TMP_IMG_DIR_ . $name : tempnam(_PS_TMP_IMG_DIR_, 'PS');

        if (!$tempImageName) {
            throw new ImageUploadException(
                'An error occurred while uploading the image. Check your directory permissions.'
            );
        }
        if (!move_uploaded_file($image->getPathname(), $tempImageName)) {
            throw new ImageUploadException(
                'An error occurred while uploading the image. Check your directory permissions.'
            );
        }
        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManagerCore::checkImageMemoryLimit($tempImageName)) {
            throw new MemoryLimitException(
                'Due to memory limit restrictions, this image cannot be loaded. Increase your memory_limit value.'
            );
        }
        return $tempImageName;
    }

    public function moveTempImage(?string $tempPath, string $definitiveDir, ?string $ext = null): ?string
    {
        $uniqName = null;
        if ($tempImageName = $this->getTempImageName($tempPath)) {
            // replace spaces to avoid warnings in browser: Failed parsing 'srcset' attribute value since it has an unknown descriptor
            $pathinfo = pathinfo($tempImageName);
            $uniqName = preg_replace('/\s+/', '-', trim($pathinfo['filename'])) . '-' . uniqid() . '.' . ($ext ?? $pathinfo['extension']);
            if (!file_exists($definitiveDir)) {
                mkdir($definitiveDir, recursive: true);
            }

            copy(_PS_TMP_IMG_DIR_ . $tempImageName, $this->getDifinitivePath($definitiveDir, $uniqName));
        }
        return $uniqName;
    }

    public function getTempImageName(?string $tempPath): ?string
    {
        if ($tempPath) {
            $tempPathExplode = explode('/', $tempPath);
            if ($tempPathExplode[0] === 'temp') {
                return $tempPathExplode[1];
            }
        }
        return null;
    }

    public function removeDefinitiveImage(string $definitiveDir, string $imageName): void
    {
        $imagePath = $this->getDifinitivePath($definitiveDir, $imageName);
        if (file_exists($imagePath)) {
            unlink($imagePath);
            // TODO validate webp and avi cases if enabled in admin
            if (($webpPath = $this->changeNameExtension($imagePath, 'webp')) && file_exists($webpPath)) {
                unlink($webpPath);
            }
        }
    }

    /**
     * basedon \PrestaShop\PrestaShop\Adapter\Image\Uploader\AbstractImageUploader::checkImageIsAllowedForUpload
     * Check if image is allowed to be uploaded.
     *
     * @param UploadedFile $image
     *
     * @throws UploadedImageConstraintException
     */
    private function checkImageIsAllowedForUpload(UploadedFile $image): void
    {
        $maxFileSize = Tools::getMaxUploadSize();

        if ($maxFileSize > 0 && $image->getSize() > $maxFileSize) {
            throw new UploadedImageConstraintException(sprintf('Max file size allowed is "%s" bytes. Uploaded image size is "%s".', $maxFileSize, $image->getSize()), UploadedImageConstraintException::EXCEEDED_SIZE);
        }

        if (!ImageManagerCore::isRealImage($image->getPathname(), $image->getClientMimeType())
            || !ImageManagerCore::isCorrectImageFileExt($image->getClientOriginalName())
            || str_contains($image->getClientOriginalName(), '%00') // prevent null byte injection
        ) {
            throw new UploadedImageConstraintException(sprintf('Image format "%s", not recognized, allowed formats are: .gif, .jpg, .png', $image->getClientOriginalExtension()), UploadedImageConstraintException::UNRECOGNIZED_FORMAT);
        }
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
