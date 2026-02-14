<?php

namespace PrestaShop\Module\EnhancedFields\Form\Type;

use PrestaShop\Module\EnhancedFields\Service\ImageManager;
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
                'placeholder' => $this->translator->trans('Choose file(s)', domain: 'Admin.Actions'),
                'autocomplete' => 'off',
            ],
            'data_class' => null,
            'deletable' => false,
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
        $filename = $view->vars['data'] ?? null;
        $nameOriginal = $filename;

        if ($filename) {
            if (!str_contains($filename, 'temp/')) {
                $nameOriginal = ($options['dir_final'] ? $options['dir_final'] . '/' : '') . $filename;
                $placeholder = ImageManager::filenamePlaceholder($filename);
            } else {
                $placeholder = ImageManager::filenamePlaceholder(str_replace('temp/', '', $filename));
            }
            $view->vars['attr']['placeholder'] = $placeholder;
        }
        $view->vars['extra'] = [
            'fetch_url' => $this->router->generate('enhancedfields_image_fetch', [
                'location' => 'location',
                'filename' => 'filename'
            ]),
            'upload_url' => $this->router->generate($options['upload_route']),
            'filename' => $filename,
            'filename_original' => $nameOriginal,
            'deletable' => $options['deletable'],
        ];
    }
}
