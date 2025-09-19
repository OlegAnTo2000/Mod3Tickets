<?php

use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modActionField;

$actionFields = array(
    array(
        'name'   => 'tickets-box-publishing-information',
        'tab'    => 'modx-resource-main-right',
        'fields' => array(
            'publishedon',
            'pub_date',
            'unpub_date',
            'template',
            'modx-resource-createdby',
            'tickets-combo-section',
            'alias',
        ),
    ),
    array(
        'name'   => 'tickets-box-options',
        'tab'    => 'modx-resource-main-right',
        'fields' => array(
            'searchable',
            'cacheable',
            'properties[process_tags]',
            'published',
            'private',
            'privateweb',
            'richtext',
            'hidemenu',
            'isfolder',
            'show_in_tree',
        ),
    ),
    array(
        'name' => 'modx-tickets-comments',
        'tab' => '',
        'fields' => array(),
    ),
    array(
        'name' => 'modx-tickets-subscribes',
        'tab' => '',
        'fields' => array(),
    ),
);

$resourceActions = array('resource/create', 'resource/update');

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            /** @var modActionField $action */
            if ($modx->getCount(modActionField::class, ['name' => 'publishedon', 'other' => 'tickets']) > 1) {
                $modx->removeCollection(modActionField::class, ['other' => 'tickets']);
            }

            foreach ($resourceActions as $actionId) {
                $c = $modx->newQuery(modActionField::class, ['type' => 'tab', 'action' => $actionId]);
                $c->select('max(`rank`)');
                $tabIdx = 0;
                if ($c->prepare() && $c->stmt->execute()) {
                    $tabIdx = $c->stmt->fetchColumn();
                    $tabIdx += 1;
                }

                foreach ($actionFields as $tab) {
                    /** @var modActionField $tabObj */
                    $tabObj = $modx->getObject(modActionField::class, ['action' => $actionId, 'name' => $tab['name'], 'other' => 'tickets']);
                    if (!$tabObj) {
                        $tabObj = $modx->newObject(modActionField::class);
                    }
                    $tabObj->fromArray(array_merge($tab, [
                        'action' => $actionId,
                        'form'   => 'modx-panel-resource',
                        'type'   => 'tab',
                        'other'  => 'tickets',
                        'rank'   => $tabIdx,
                    ]), '', true, true);
                    $success = $tabObj->save();

                    $tabIdx++;
                    $idx = 0;
                    foreach ($tab['fields'] as $field) {
                        $fieldObj = $modx->getObject(modActionField::class, [
                            'action' => $actionId, 
                            'name' => $field, 
                            'tab' => $tab['name'], 
                            'other' => 'tickets']
                        );
                        if (!$fieldObj) {
                            $fieldObj = $modx->newObject(modActionField::class);
                        }
                        $fieldObj->fromArray([
                            'action' => $actionId,
                            'name'   => $field,
                            'tab'    => $tab['name'],
                            'form'   => 'modx-panel-resource',
                            'type'   => 'field',
                            'other'  => 'tickets',
                            'rank'   => $idx,
                        ], '', true, true);
                        $success = $fieldObj->save();
                        $idx++;
                    }
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            $modx->removeCollection(modActionField::class, ['other' => 'tickets']);
            break;
    }
}

return true;
