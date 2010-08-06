<?php
/**
 * modActiveDirectory
 *
 * Copyright 2010 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of modActiveDirectory, which integrates Active Directory
 * authentication into MODx Revolution.
 *
 * modActiveDirectory is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * modActiveDirectory is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * modActiveDirectory; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package activedirectory
 */
/**
 * Patch broken file on Revo login controller
 *
 * @package activedirectory
 * @subpackage build
 */
$success = true;
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $modx =& $object->xpdo;

            $adPath = $modx->getOption('activedirectory.core_path',null,$modx->getOption('core_path').'components/activedirectory/');
            
            $managerPath = $modx->getOption('manager_path',null,MODX_MANAGER_PATH);
            $path = $managerPath.'controllers/default/security/login.php';

            @chmod($path,0664);

            $patchFile = $adPath.'elements/patches/login.php';
            if (file_exists($patchFile)) {
                $contents = file_get_contents($patchFile);
                $success = $modx->cacheManager->writeFile($path,$contents);
                if (!$success) {
                    $modx->log(xPDO::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not write patch file. Please see documentation for how to patch login file.');
                }
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR,'[ActiveDirectory] Could not find patch file.');
                $success = false;
            }
            break;
    }
}
return $success;