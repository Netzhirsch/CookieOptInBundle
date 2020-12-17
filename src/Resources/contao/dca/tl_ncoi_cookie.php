<?php
/********* config ****************************************************************************************/

$GLOBALS['TL_DCA']['tl_ncoi_cookie'] = [
    'config' => [
        'label' => 'Cookie opt in bundle table',
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
        'headlineCookieOptInBar' => [
            'sql' => "text NULL default ''"
        ],
        'questionHint' => [
            'sql' => "text NULL default ''"
        ],
        'saveButton' => [
            'sql' => "text NULL default ''"
        ],
        'saveAllButton' => [
            'sql' => "text NULL default ''"
        ],
        'highlightSaveAllButton' => [
            'sql' => "varchar(1) NULL default '1'"
        ],
        'infoButtonShow' => [
            'sql' => "varchar(255) NULL default ''"
        ],
        'infoButtonHide' => [
            'sql' => "varchar(255) NULL default ''"
        ],
        'infoHint' => [
            'sql' => "text  NULL default ''",
        ],
        'cookieGroups' => [
            'sql' => "text NULL default ''",
        ],
        'otherScripts' => [
            'sql' => "text NULL default ''",
        ],
        'i_frame_video' => [
            'sql' => "text NULL default ''",
        ],
        'i_frame_maps' => [
            'sql' => "text NULL default ''",
        ],
        'i_frame_i_frame' => [
            'sql' => "text NULL default ''",
        ],
        'i_frame_always_load' => [
            'sql' => "text NULL default ''",
        ],
        'i_frame_load' => [
            'sql' => "text NULL default ''",
        ],
        'expireTime' => [
            'sql' => "int(11) NULL default '30'",
        ],
        'cookieTools' => [
            'sql' => "text NULL default ''",
        ],
        'isNewCookieVersion' => [
            'sql' => "varchar(1) NOT NULL DEFAULT 0",
        ],
        'cookieVersion' => [
            'sql' => "int(10) NULL default '1'",
        ],
        'privacyPolicy' => [
            'sql' => "varchar(11) NULL default '' ",
        ],
        'imprint' => [
            'sql' => "varchar(11) NULL default '' ",
        ],
        'excludePages' => [
            'sql' => "blob NULL default '' ",
        ],
        'respectDoNotTrack' => [
            'sql' => "varchar(1) NULL default '0' ",
        ],
        'zIndex' => [
            'sql' => "int(7) NULL DEFAULT '1' ",
        ],
        'blockSite' => [
            'sql' => "varchar(1) NOT NULL default '0' ",
        ],
        'defaultCss' => [
            'sql' => "varchar(1) NULL default '1' ",
        ],
        'position' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
        'cssTemplateStyle' => [
            'sql' => "varchar(255) NULL default '' ",
        ],
        'templateBar' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
        'animation' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
        'maxWidth' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
        'ipFormatSave' => [
            'sql' => "varchar(64) NULL default '' ",
        ],
        'toolsDeactivate' => [
            'sql' => "varchar(255) NULL default '' ",
        ]
    ]

];