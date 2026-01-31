<?php

namespace PrestaShop\Module\EnhancedFields\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageTempType extends AbstractType
{
    public function getParent(): string
    {
        return FileType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => true,
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'file_upload';
    }
}
