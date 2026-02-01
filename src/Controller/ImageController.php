<?php

namespace PrestaShop\Module\EnhancedFields\Controller;

use PrestaShop\Module\EnhancedFields\Form\Type\ImageTempType;
use PrestaShop\Module\EnhancedFields\Service\ImageManager;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\ImageUploadException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\MemoryLimitException;
use PrestaShop\PrestaShop\Core\Image\Uploader\Exception\UploadedImageConstraintException;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends PrestaShopAdminController
{
    public function uploadTempAction(Request $request, ImageManager $imageManager): JsonResponse
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
                    $path = $imageManager->moveUploadedImage($uploadedFile, $uploadedFile->getClientOriginalName());
                    $name = $uploadedFile->getClientOriginalName();
                    $response = [
                        'name' => $name,
                        'path' => "temp/$name",
                        'path_full' => $path
                    ];
                } catch (ImageUploadException|MemoryLimitException|UploadedImageConstraintException $e) {
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
        $file = null;
        $dirs = $location === 'temp' ? _PS_TMP_IMG_DIR_ : _PS_ROOT_DIR_ . '/' . ltrim($location, '/');

        // if fileName comes with subdir extract it and append to dirs
        if (preg_match('~^(.+?)/[^/]+$~', $filename, $matches)) {
            $dirs .= '/' . $matches[1];
            $filename = str_replace($matches[1] . '/', '', $filename);
        }
        foreach ((new Finder())->files()->name($filename)->in($dirs) as $finderFile) {
            $file = $finderFile;
        }
        if ($file) {
            return $this->renderStream(fn() => fopen($file, 'r+'), 'image');
        } else {
            throw $this->createNotFoundException();
        }
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

    private function renderStream($callbackStream, $contentType = 'application/pdf', $dispositionAttachment = null): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($callbackStream) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $callbackStream();
            stream_copy_to_stream($fileStream, $outputStream);
        });
        if ($contentType) {
            $response->headers->set('Content-Type', $contentType);
        }
        if ($dispositionAttachment) {
            $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $dispositionAttachment);
            $response->headers->set('Content-Disposition', $disposition);
        }
        return $response;
    }
}
