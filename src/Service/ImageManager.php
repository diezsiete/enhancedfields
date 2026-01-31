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
}
