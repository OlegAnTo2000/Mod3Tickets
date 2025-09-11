<?php

use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modX;
use MODX\Revolution\modSystemSetting;

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            $modx->removeCollection(modSystemSetting::class, [
                'namespace' => 'tickets',
            ]);
            break;
    }
}
return true;