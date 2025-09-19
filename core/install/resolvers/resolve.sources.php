<?php

use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\Sources\modMediaSource;

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $tmp = explode('/', MODX_ASSETS_URL);
            $assets = $tmp[count($tmp) - 2];

            $properties = [
                'name'        => 'Tickets Files',
                'description' => 'Default media source for files of tickets',
                'class_key'   => 'sources.modFileMediaSource',
                'properties'  => [
                    'basePath' => [
                        'name'    => 'basePath',
                        'desc'    => 'prop_file.basePath_desc',
                        'type'    => 'textfield',
                        'lexicon' => 'core:source',
                        'value'   => $assets . '/images/tickets/',
                    ],
                    'baseUrl' => [
                        'name'    => 'baseUrl',
                        'desc'    => 'prop_file.baseUrl_desc',
                        'type'    => 'textfield',
                        'lexicon' => 'core:source',
                        'value'   => 'assets/images/tickets/',
                    ],
                    'imageExtensions' => [
                        'name'    => 'imageExtensions',
                        'desc'    => 'prop_file.imageExtensions_desc',
                        'type'    => 'textfield',
                        'lexicon' => 'core:source',
                        'value'   => 'webp,jpg,jpeg,png,gif,WEBP,JPG,JPEG,PNG,GIF',
                    ],
                    'allowedFileTypes' => [
                        'name'    => 'allowedFileTypes',
                        'desc'    => 'prop_file.allowedFileTypes_desc',
                        'type'    => 'textfield',
                        'lexicon' => 'core:source',
                        'value'   => 'webp,jpg,jpeg,png,gif,WEBP,JPG,JPEG,PNG,GIF',
                    ],
                    'thumbnailType' => [
                        'name'    => 'thumbnailType',
                        'desc'    => 'prop_file.thumbnailType_desc',
                        'type'    => 'list',
                        'lexicon' => 'core:source',
                        'options' => [
                            ['text' => 'Png', 'value' => 'png'],
                            ['text' => 'Jpg', 'value' => 'jpg'],
                        ],
                        'value' => 'jpg',
                    ],
                    'thumbnails' => [
                        'name'    => 'thumbnails',
                        'desc'    => 'tickets.source_thumbnails_desc',
                        'type'    => 'textarea',
                        'lexicon' => 'tickets:setting',
                        'value'   => '{"thumb":{"w":120,"h":90,"q":90,"zc":"1","bg":"000000"}}',
                    ],
                    'maxUploadWidth' => [
                        'name'    => 'maxUploadWidth',
                        'desc'    => 'tickets.source_maxUploadWidth_desc',
                        'type'    => 'numberfield',
                        'lexicon' => 'tickets:setting',
                        'value'   => 1920,
                    ],
                    'maxUploadHeight' => [
                        'name'    => 'maxUploadHeight',
                        'desc'    => 'tickets.source_maxUploadHeight_desc',
                        'type'    => 'numberfield',
                        'lexicon' => 'tickets:setting',
                        'value'   => 1080,
                    ],
                    'maxUploadSize' => [
                        'name'    => 'maxUploadSize',
                        'desc'    => 'tickets.source_maxUploadSize_desc',
                        'type'    => 'numberfield',
                        'lexicon' => 'tickets:setting',
                        'value'   => 3145728,
                    ],
                    'imageNameType' => [
                        'name'    => 'imageNameType',
                        'desc'    => 'tickets.source_imageNameType_desc',
                        'type'    => 'list',
                        'lexicon' => 'tickets:setting',
                        'options' => [
                            ['text' => 'Hash', 'value' => 'hash'],
                            ['text' => 'Friendly', 'value' => 'friendly'],
                        ],
                        'value' => 'hash',
                    ],
                ],
                'is_stream' => 1,
            ];
            /** @var modMediaSource $source */
            if (!$source = $modx->getObject(modMediaSource::class, ['name' => $properties['name']])) {
                $source = $modx->newObject(modMediaSource::class, $properties);
            } else {
                $default = $source->get('properties');
                foreach ($properties['properties'] as $k => $v) {
                    if (!array_key_exists($k, $default)) {
                        $default[$k] = $v;
                    }
                }
                $source->set('properties', $default);
            }
            $source->save();

            if ($setting = $modx->getObject(modSystemSetting::class, ['key' => 'tickets.source_default'])) {
                if (!$setting->get('value')) {
                    $setting->set('value', $source->get('id'));
                    $setting->save();
                }
            }

            @mkdir(MODX_ASSETS_PATH . 'images/');
            @mkdir(MODX_ASSETS_PATH . 'images/tickets/');
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;