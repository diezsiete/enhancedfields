<?php

namespace PrestaShop\Module\EnhancedFields\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileUploadType extends AbstractType
{
    public function __construct(
        private readonly Router              $router,
        private readonly TranslatorInterface $translator,
    ){}

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'dropzoned dummy-input',
                'placeholder' => $this->translator->trans('Select File', domain: 'Modules.EnhancedFields.EnhancedFields'),
                'autocomplete' => 'off',
            ],
            'data_class' => null,
            'dir_final' => '',
            'upload_route' => 'enhancedfields_image_upload_temp',
        ]);

        $resolver->setNormalizer('dir_final', fn (Options $options, string $value) => ltrim($value, '/'));
    }

    public function getBlockPrefix(): string
    {
        return 'enhanced_file_upload';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $fileName = $view->vars['data'] ?? null;
        $nameOriginal = $fileName;

        if ($fileName) {
            if (!str_contains($fileName, 'temp/')) {
                $nameOriginal = ($options['dir_final'] ? $options['dir_final'] . '/' : '') . $fileName;
                $placeholder = preg_replace(
                    '/(.+)(-\w+)(\.\w+$)/', '$1$3', $fileName
                );
            } else {
                $placeholder = str_replace('temp/', '', $fileName);
            }
            $view->vars['attr']['placeholder'] = $placeholder;
        }
        $view->vars['extra'] = [
            'fetch_url' => $this->router->generate('enhancedfields_image_fetch', [
                'location' => 'location',
                'fileName' => 'fileName'
            ]),
            'upload_url' => $this->router->generate($options['upload_route']),
            'filename' => $fileName,
            'filename_original' => $nameOriginal,
        ];

        // $view->vars['extra'] = [
        //     'datas' => [
        //         'data-file-fetch-url' => $this->router->generate('enhancedfields_image_fetch', [
        //             'location' => 'location',
        //             'fileName' => 'fileName'
        //         ]),
        //         'data-file-upload-url' => $this->router->generate($options['upload_route']),
        //         'data-file-name' => $fileName,
        //         'data-file-name-original' => $nameOriginal,
        //     ],
        // ];
    }
}
