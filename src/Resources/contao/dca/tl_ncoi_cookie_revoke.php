<?php
/********* config ****************************************************************************************/
$GLOBALS['TL_DCA']['tl_ncoi_cookie_revoke'] = [
    'config' => [
        'label' => 'Cookie opt in revoke bundle table',
        'dataContainer' => 'Table',
        'ptable' => 'tl_module',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'revokeButton' => [
            'sql' => "varchar(255) NULL default ''",
        ],
        'templateRevoke' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
    ]

];
//$GLOBALS['TL_DCA']['tl_ncoi_cookie'] = [
//    'config' => [],
//    'list' => […],
//    'fields' => […],
//    'palettes' => […],
//];