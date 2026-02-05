<?php

namespace PrestaShop\Module\EnhancedFields\Controller;

use ImageManager as ImageManagerCore;
use PrestaShop\Module\EnhancedFields\Form\Type\ImageTempType;
use PrestaShop\Module\EnhancedFields\Service\ImageManager;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageUploadException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\UploadedImageConstraintException;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tools;

class ImageController extends PrestaShopAdminController
{
    public function uploadTempAction(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $response = [];
        $status = 200;
        $form = $this->createForm(ImageTempType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $error = null;
            if ($form->isValid()) {
                try {
                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $form->getData();
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $name = $slugger->slug($originalFilename). '-' . uniqid() . '.' . $uploadedFile->guessExtension();
                    $this->moveUploadedImage($uploadedFile, $name);
                    $response = [
                        'name' => ImageManager::filenamePlaceholder($name),
                        'path' => "temp/$name",
                    ];
                } catch (ImageUploadException|UploadedImageConstraintException $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = $this->getFirstErrorFromForm($form);
            }
            if ($error) {
                $response = ['error' => $error];
                $status = 400;
            }
        }

        return $this->json($response, $status);
    }

    public function fetchAction(string $location, string $filename): StreamedResponse
    {
        $file = $this->findFile($location, $filename);
        if ($file) {
            return $this->renderStream(fn() => fopen($file, 'r+'), 'image', contentLength: $file->getSize());
        } else {
            throw $this->createNotFoundException();
        }
    }

    public function fileViewer(string $filename, bool $deletable): Response
    {
        $location = strtok($filename, '/');
        $filename = substr($filename, strlen($location) + 1);
        $src = ($location === 'temp' ? _PS_TMP_IMG_ : "/$location") . "/$filename";
        $fileSize = 0;
        if ($deletable && $file = $this->findFile($location, $filename)) {
            $fileSize = sprintf('%skB', $file->getSize() / 1000);
        }

        return $this->render('@Modules/enhancedfields/views/templates/admin/file-viewer.html.twig', [
            'src' => $src,
            'deletable' => $deletable,
            'fileSize' => $fileSize,
        ]);
    }

    private function findFile(string $location, string $filename): SplFileInfo|null
    {
        $dirs = $location === 'temp' ? _PS_TMP_IMG_DIR_ : _PS_ROOT_DIR_ . '/' . ltrim($location, '/');

        // if fileName comes with subdir extract it and append to dirs
        if (preg_match('~^(.+?)/[^/]+$~', $filename, $matches)) {
            $dirs .= '/' . $matches[1];
            $filename = str_replace($matches[1] . '/', '', $filename);
        }
        foreach ((new Finder())->files()->name($filename)->in($dirs) as $finderFile) {
            return $finderFile;
        }
        return null;
    }

    private function getFirstErrorFromForm(FormInterface $form): ?string
    {
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                foreach($childForm->getErrors() as $error) {
                    return $error->getMessage();
                }
            }
        }
        foreach($form->getErrors() as $error) {
            return $error->getMessage();
        }
        return null;
    }

    private function renderStream($callbackStream, $contentType = 'application/pdf', $dispositionAttachment = null, false|int $contentLength = 0): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($callbackStream) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $callbackStream();
            stream_copy_to_stream($fileStream, $outputStream);
        });
        if ($contentType) {
            $response->headers->set('Content-Type', $contentType);
        }
        if ($contentLength) {
            $response->headers->set('Content-Length', (string)$contentLength);
        }
        if ($dispositionAttachment) {
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $dispositionAttachment);
            $response->headers->set('Content-Disposition', $disposition);
        }
        return $response;
    }

    /**
     * basedon \PrestaShop\PrestaShop\Adapter\Image\Uploader\ManufacturerImageUploader::upload
     * @throws UploadedImageConstraintException|ImageUploadException
     */
    private function moveUploadedImage(UploadedFile $image, string $name): void
    {
        $this->checkImageIsAllowedForUpload($image);
        $tempImagePath = _PS_TMP_IMG_DIR_ . $name;
        if (!move_uploaded_file($image->getPathname(), $tempImagePath)) {
            throw new ImageUploadException(
                'An error occurred while uploading the image. Check your directory permissions.'
            );
        }
    }

    /**
     * basedon \PrestaShop\PrestaShop\Adapter\Image\Uploader\AbstractImageUploader::checkImageIsAllowedForUpload
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
