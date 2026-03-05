<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class EnhancedFields extends Module
{
    private array $hooks = [
        'actionOnEnhancedImageMoved' => 'After an image has been moved from temp to definitive dir by ImageManager',
        'actionOnEnhancedImageRemoved' => 'After an image has been removed by ImageManager',
    ];

    public function __construct()
    {
        $this->name = 'enhancedfields';
        $this->tab = 'administration';
        $this->version = '1.2.0';
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
            $this->createHooks() &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function uninstall(): bool
    {
        return $this->deleteHooks();
    }

    public function hookActionAdminControllerSetMedia(): void
    {
        $this->context->controller->addJs($this->getPathUri() . 'public/enhancedfields.js?' . $this->version);
    }

    public function createHooks(): bool
    {
        $ok = true;
        foreach ($this->hooks as $hookName => $title) {
            if ($ok && !Hook::getIdByName($hookName)) {
                $hook = new Hook();
                $hook->name = $hookName;
                $hook->title = $title;
                $hook->description = '';
                $hook->position = 1;
                $ok = $hook->add();
            }
        }
        return $ok;
    }

    private function deleteHooks(): bool
    {
        $ok = true;
        foreach (array_keys($this->hooks) as $hookName) {
            if ($ok && $id = Hook::getIdByName($hookName)) {
                $hook = new Hook($id);
                $ok = (bool) $hook->delete();
            }
        }
        return $ok;
    }

}
