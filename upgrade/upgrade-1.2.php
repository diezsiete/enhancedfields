<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2(EnhancedFields $module): bool
{
    return $module->createHooks();
}
