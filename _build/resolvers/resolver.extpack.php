<?php
/**
 * Add activedirectory package path to extension_packages setting
 *
 * @package activedirectory
 * @subpackage build
 */
$success = true;
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        /* ensure setting is correct on install and upgrade */
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $object->xpdo;
            $modelPath = $modx->getOption('activedirectory.core_path',null,$modx->getOption('core_path').'components/activedirectory/').'model/';
            //$modx->addPackage('activedirectory',$modelPath);

            $setting = $modx->getObject('modSystemSetting',array(
                'key' => 'extension_packages',
            ));
            if (empty($setting)) {
                $setting = $modx->newObject('modSystemSetting');
                $setting->set('key','extension_packages');
                $setting->set('namespace','core');
                $setting->set('xtype','textfield');
                $setting->set('area','system');
            }
            $value = $setting->get('value');
            if (empty($value)) {
                $value = 'activedirectory:'.$modelPath;
            } else {
                if (strpos($value,'activedirectory:') === false) {
                    $value .= ',activedirectory:'.$modelPath;
                }
            }
            $setting->set('value',$value);
            $setting->save();

            break;
        /* remove on uninstall */
        case xPDOTransport::ACTION_UNINSTALL:
            $modx =& $object->xpdo;
            $modelPath = $modx->getOption('activedirectory.core_path',null,$modx->getOption('core_path').'components/activedirectory/').'model/';

            $setting = $modx->getObject('modSystemSetting',array(
                'key' => 'extension_packages',
            ));
            $value = $setting->get('value');
            $value = str_replace(',activedirectory:'.$modelPath,'',$value);
            $value = str_replace('activedirectory:'.$modelPath,'',$value);
            $setting->set('value',$value);
            $setting->save();
            break;
    }
}

return $success;