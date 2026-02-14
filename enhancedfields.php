<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EnhancedFields extends Module
{
    public function __construct()
    {
        $this->name = 'enhancedfields';
        $this->tab = 'administration';
        $this->version = '1.1.4';
        $this->author = 'diezsiete';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Enhanced Fields', [], 'Modules.EnhancedFields.EnhancedFields');
        $this->description = $this->trans('New fields for adminisration forms.', [], 'Modules.EnhancedFields.EnhancedFields');
    }

    public function install(): bool
    {
        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function hookActionAdminControllerSetMedia(): void
    {
        $this->context->controller->addJs($this->getPathUri() . 'public/enhancedfields.js?' . $this->version);
    }
}
