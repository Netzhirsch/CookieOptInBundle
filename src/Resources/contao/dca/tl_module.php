<?php

use Contao\DC_Table;
use Contao\ModuleModel;
use Contao\StringUtil;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Netzhirsch\CookieOptInBundle\Classes\Helper;
/** Revoke Modul ***********************************************/
if (TL_MODE == 'BE') {
    $GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';
    $GLOBALS['TL_JAVASCRIPT']['jquery'] = 'bundles/netzhirschcookieoptin/jquery.min.js|static';
    $GLOBALS['TL_JAVASCRIPT']['ncoi'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.js|static';
}

$GLOBALS['TL_DCA']['tl_module']['palettes']['cookieOptInRevoke'] =
	'name,
	type,
	revokeButton,
	align,
	space,
	cssID,
	templateRevoke'
;

$GLOBALS['TL_DCA']['tl_module']['fields']['revokeButton'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['revokeButton'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'mandatory' => true,
		'tl_class' => 'w50 clr',
		'maxlength' => 255,
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie_revoke.revokeButton',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableRevoke']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableRevoke']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['templateRevoke'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['templateRevoke'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => $this->getTemplateGroup('mod_cookie_opt_in_revoke'),
	'eval' => [
		'tl_class'  =>  'w50 clr',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableRevoke']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableRevoke']],
];

/** Ende Revoke Modul ******************************************/

/** Bar Modul **************************************************/

$GLOBALS['TL_DCA']['tl_module']['palettes']['cookieOptInBar']   = '
	name
	,type
	;headlineCookieOptInBar
	,questionHint
	,saveButton
	,saveAllButton
	,highlightSaveAllButton
	;infoButtonShow
	,infoButtonHide
	,infoHint
	;align
	,space
	,cssID
	;cookieGroups
	,defaultTools
	,cookieTools
	,otherScripts
	,i_frame_video
	,i_frame_maps
	,i_frame_i_frame
	,i_frame_always_load
	,i_frame_load
	;expireTime
	;privacyPolicy
	,imprint
	,excludePages
	;respectDoNotTrack
	,optOut
	;zIndex
	,blockSite
	;templateBar
	,defaultCss
	,cssTemplateStyle
	,maxWidth
	,position
	,animation
	;ipFormatSave
	;isNewCookieVersion
';

// setCookieVersion check for right modul
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setCookieVersion'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setPageTreeEntries'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setLessVariables'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setGroupsToNcoiTable'];

$GLOBALS['TL_DCA']['tl_module']['config']['ctable'] = [
  'tl_ncoi_cookie',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['headlineCookieOptInBar'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['headline'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['headline'],
	'exclude'   => true,
	'inputType' => 'inputUnit',
	'options' => [
		'h2',
		'h3',
		'h4',
		'h5',
	],
	'eval' => [
		'tl_class'=>'w50 clr',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableHeadlineCookieOptInBar']],
];


$GLOBALS['TL_DCA']['tl_module']['fields']['questionHint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['questionHint'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['questionHint'],
	'exclude'   => true,
	'inputType' => 'textarea',
	'eval' => [
		'tl_class'=>'long clr',
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.questionHint',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['saveButton'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['saveButton'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'w50',
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.saveButton',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['saveAllButton'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['saveAllButton'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'w50',
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.saveAllButton',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['highlightSaveAllButton'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['highlightSaveAllButton'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'w50',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoButtonShow'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonShow'],
    'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonShow'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'alwaysSave' => true,
        'tl_class'	=>	'w50 clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.infoButtonShow',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoButtonHide'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonHide'],
    'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonHide'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'alwaysSave' => true,
        'tl_class'	=>	'w50',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.infoButtonHide',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoHint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['infoHint'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoHint'],
	'exclude'   => true,
	'inputType' => 'textarea',
	'eval' => [
		'alwaysSave' => true,
		'tl_class'	=>	'long clr',
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.infoHint',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieVersion'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['cookieVersion'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'style' => 'display:none',
        'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.cookieVersion',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieGroups'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['cookieGroups'],
	'exclude'   => true,
	'inputType' => 'keyValueWizard',
	'eval' => [
		'tl_class'  =>  'long clr',
		'submitOnChange' => true,
		'doNotCopy' => true,
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.cookieGroups',
    'save_callback' => [
        ['tl_module_ncoi','setEssentialGroup'],
    ],
    'load_callback' => [
        ['tl_module_ncoi','getDefaultGroups'],
    ],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieTools'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieTools'],
	'exclude'   => true,
	'inputType' => 'fieldpalette',
	'foreignKey'   => 'tl_fieldpalette.id',
	'relation'     => ['type' => 'hasMany', 'load' => 'eager'],
	'load_callback' => [['tl_module_ncoi','getNetzhirschCookie']],
	'fieldpalette' => [
		'config' => [
			'hidePublished' => true,
			'notSortable' => false,
            'onsubmit_callback' => [['tl_module_ncoi', 'saveInNcoiTableCookieTools']],
	        'ondelete_callback' => [['tl_module_ncoi','deleteTool']],
		],
		'list'     => [
			'label' => [
				'fields' => ['cookieToolsName','cookieToolGroup'],
				'format' => '%s <span style="color:#b3b3b3;padding-left:3px">[%s]</span>',
			],
            'fields' => ['cookieToolGroup'],
            'flag' => 11,
            'panelLayout' => [
                'sort' => true,
            ],
            'mode' => 2,
		],
		'palettes' => [
			'default' =>
				'
				cookieToolsName,
				cookieToolsSelect,
				cookieToolsTechnicalName,
				cookieToolsTrackingId,
				cookieToolsTrackingServerUrl,
				cookieToolsProvider,
				cookieToolsPrivacyPolicyUrl,
				cookieToolsUse,
				cookieToolGroup,
				cookieToolExpiredTime,
				i_frame_blocked_urls,
				i_frame_blocked_text
				',
		],
		'fields' => [
			'cookieToolsName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsSelect' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'],
				'exclude'   => true,
				'inputType' => 'select',
				'options'   => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelectOptions'],
				'sql' => "varchar(32) default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsTechnicalName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsTrackingId' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
                    'mandatory' => false,
				],
			],
			'cookieToolsTrackingServerUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsProvider' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsPrivacyPolicyUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsUse' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'],
				'exclude'   => true,
				'inputType' => 'textarea',
				'sql' => "text NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolGroup' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'],
				'exclude'   => true,
				'inputType' => 'select',
				'options_callback' => ['tl_module_ncoi','getGroupKeys'],
				'sql' => "varchar(255) NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
            'cookieToolExpiredTime' => [
                'label' => 	&$GLOBALS['TL_LANG']['tl_module']['cookieToolExpiredTime'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'rgxp'=>'natural',
                    'tl_class'=>'long',
                ],
                'sql' => "int(2) NULL ",
            ],
            'i_frame_blocked_urls' => [
                'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame_blocked_urls'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => false,
                    'tl_class'=>'long',
                ],
                'sql' => "text NULL default ''",
            ],
            'i_frame_blocked_text' => [
                'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame_blocked_text'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => false,
                    'tl_class'=>'long',
                ],
                'sql' => "text NULL default ''",
            ]
		],
	],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['otherScripts'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_module']['otherScripts'],
	'exclude'   => true,
	'inputType' => 'fieldpalette',
	'foreignKey'   => 'tl_fieldpalette.id',
	'relation'     => ['type' => 'hasMany', 'load' => 'eager'],
	'fieldpalette' => [
		'config' => [
			'hidePublished' => true,
            'onsubmit_callback' => [['tl_module_ncoi', 'saveInNcoiTableOtherScripts']],
		],
		'list'     => array
		(
			'label' => array
			(
				'fields' => ['cookieToolsName','cookieToolGroup'],
				'format' => '%s <span style="color:#b3b3b3;padding-left:3px">[%s]</span>',
			),
			'flag' => 11,
		),
		'palettes' => [
			'default' =>
				'
				cookieToolsName,
				cookieToolsTechnicalName,
				cookieToolsProvider,
				cookieToolsPrivacyPolicyUrl,
				cookieToolsUse,
				cookieToolGroup,
				cookieToolExpiredTime,
				cookieToolsCode
				',
		],
		'fields' => [
			'cookieToolsProvider' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsUse' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'],
				'exclude'   => true,
				'inputType' => 'textarea',
				'sql' => "text NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsPrivacyPolicyUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsTechnicalName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolsName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
			'cookieToolGroup' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'],
				'exclude'   => true,
				'inputType' => 'select',
				'options_callback' => ['tl_module_ncoi','getGroupKeys'],
				'sql' => "varchar(255) NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr',
				],
			],
            'cookieToolExpiredTime' => [
                'label' => 	&$GLOBALS['TL_LANG']['tl_module']['cookieToolExpiredTime'],
                'exclude'   => true,
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'rgxp'=>'natural',
                    'tl_class'=>'long',
                ],
                'sql' => "int(2) NULL ",
            ],
			'cookieToolsCode' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsCode'],
				'exclude'   => true,
				'inputType' => 'textarea',
				'sql' => "text NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'allowHtml' => true,
					'rte' => 'ace',
					'preserveTags' => true,
					'tl_class'  =>  'long clr',
				],
			],
		],
	],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_video'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame']['video'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval' => [
        'mandatory' => true,
        'tl_class'=>'long clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.i_frame_video',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_maps'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame']['maps'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval' => [
        'mandatory' => true,
        'tl_class'=>'long clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.i_frame_maps',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_i_frame'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame']['i_frame'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval' => [
        'mandatory' => true,
        'tl_class'=>'long clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.i_frame_i_frame',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];


$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_always_load'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame']['always_load'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'tl_class'=>'w50',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.i_frame_always_load',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_load'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['i_frame']['load'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'tl_class'=>'w50',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.i_frame_load',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['expireTime'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['expireTime'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'rgxp'=>'natural',
        'tl_class'=>'long clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.expireTime',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableDebug']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['privacyPolicy'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval' => [
		'tl_class'  =>  'w50',
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.privacyPolicy',
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTablePrivacyPolicy']],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTablePageTree']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['imprint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['imprint'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval' => [
		'tl_class'  =>  'w50',
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.imprint',
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableImpress']],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTablePageTree']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['excludePages'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['excludePages'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval' => [
		'tl_class'  =>  'long clr',
		'alwaysSave' => false,
		'fieldType'=>'checkbox',
		'multiple'=>true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.excludePages',
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableExcludePages']],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTablePageTree']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['respectDoNotTrack'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['respectDoNotTrack'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval' => [
		'tl_class'  =>  'w50 clr',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['optOut'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['optOut'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'w50 clr',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['zIndex'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['zIndex'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'rgxp'=>'natural',
        'tl_class'=>'long clr',
        'doNotSaveEmpty' => true,
    ],
    'foreignKey' => 'tl_ncoi_cookie.zIndex',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['blockSite'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['blockSite'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'long clr',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];


$GLOBALS['TL_DCA']['tl_module']['fields']['defaultCss'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['defaultCss'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval' => [
		'tl_class'  =>  'long clr',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['position'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['position'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'leftTop' => $GLOBALS['TL_LANG']['tl_module']['leftTop'],
		'leftCenter' => $GLOBALS['TL_LANG']['tl_module']['leftCenter'],
		'leftBottom' => $GLOBALS['TL_LANG']['tl_module']['leftBottom'],
		'centerTop' => $GLOBALS['TL_LANG']['tl_module']['centerTop'],
		'centerCenter' => $GLOBALS['TL_LANG']['tl_module']['centerCenter'],
		'centerBottom' => $GLOBALS['TL_LANG']['tl_module']['centerBottom'],
		'rightTop' => $GLOBALS['TL_LANG']['tl_module']['rightTop'],
		'rightCenter' => $GLOBALS['TL_LANG']['tl_module']['rightCenter'],
		'rightBottom' => $GLOBALS['TL_LANG']['tl_module']['rightBottom'],
	],
	'eval' => [
		'tl_class'  =>  'w50',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cssTemplateStyle'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'dark' => &$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['dark'],
		'light' => &$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['light'],
	],
	'eval' => [
		'tl_class'  =>  'w50',
		'alwaysSave' => false,
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [
        ['tl_module_ncoi', 'saveInNcoiTable']
        ,['tl_module_ncoi','setCssFromLess'],
    ],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['templateBar'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['templateBar'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => $this->getTemplateGroup('mod_cookie_opt_in_bar'),
	'eval' => [
		'tl_class'  =>  'w50 clr',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['animation'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['animation'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'go-up' => $GLOBALS['TL_LANG']['tl_module']['go-up'],
		'shrink' => $GLOBALS['TL_LANG']['tl_module']['shrink'],
        'shrink-and-rotate' => $GLOBALS['TL_LANG']['tl_module']['shrink-and-rotate'],
        'hinge' => $GLOBALS['TL_LANG']['tl_module']['hinge'],
	],
	'eval' => [
		'tl_class'  =>  'w50',
		'includeBlankOption' => true,
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['maxWidth'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['maxWidth'],
	'exclude'   => true,
	'inputType' => 'inputUnit',
	'options' => [
		'px',
		'%',
		'em',
		'rem',
		'vw',
		'vh',
		'vmin',
		'vmax',
		'ex',
		'pt',
		'pc',
		'in',
		'cm',
		'mm',
	],
	'eval' => [
		'tl_class'  =>  'w50',
		'rgxp' => 'natural',
		'alwaysSave' => false,
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'getDefaultMaxWidth']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ipFormatSave'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['ipFormatSave'],
    'exclude'   => true,
    'inputType' => 'select',
    'options' => [
        'uncut' => $GLOBALS['TL_LANG']['tl_module']['uncut'],
        'pseudo' => $GLOBALS['TL_LANG']['tl_module']['pseudo'],
        'anon' => $GLOBALS['TL_LANG']['tl_module']['anon'],
    ],
    'eval' => [
        'tl_class'  =>  'w50 ncoi---list--container',
        'helpwizard' => true,
        'doNotSaveEmpty' => true,
    ],
    'explanation'   => 'ipFormatSaveExplanation',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['isNewCookieVersion'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'long clr',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

class tl_module_ncoi extends tl_module {

	public function getDefaultMaxWidth($value,DC_Table $dca){

        $value = $this->loadFromNcoiTable($value,$dca);
	    if (empty($value) || $value == 'a:2:{s:5:"value";s:0:"";s:4:"unit";s:2:"px";}')
			$value = 'a:2:{s:5:"value";s:3:"400";s:4:"unit";s:2:"px";}';
		
		return $value;
	}
	
	/**
	 * @param $value
	 *
	 * @return mixed
	 * @throws Less_Exception_Parser
	 */
	public function setLessVariables(DC_Table $dca){
        if ($this->checkRightModule($dca->__get('field'))) {
            $conn = $dca->Database;
            $sql = "SELECT maxWidth,blockSite,zIndex FROM tl_ncoi_cookie WHERE pid=?";
            $stmt = $conn->prepare($sql);
            $data = $stmt->execute(Input::get('id'));
            $data = $data->fetchAssoc();
            Helper::parseLessToCss
                (
                    'netzhirschCookieOptIn.less',
                    'netzhirschCookieOptIn.css',
                    $data['maxWidth'],
                    $data['blockSite'],
                    $data['zIndex'],
                    true
                )
            ;
        }
		return $dca;
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 * @throws Less_Exception_Parser
	 */
	public function setCssFromLess($value) {
		$styleSheet = 'netzhirschCookieOptIn';
		if ($value == 'dark') {
			$styleSheet .= 'DarkVersion';
		}else {
			$styleSheet .= 'LightVersion';
		}
		Helper::parseLessToCss($styleSheet.'.less',$styleSheet.'.css',null,null,null,true);
		return '';
	}

	public function getDefaultGroups($value,DC_Table $dca){
        $value = $this->loadFromNcoiTable($value,$dca,null,'cookieGroups');
	    if (
		    empty($value)
        ) {

	        $value = $this->getGroups($dca);
        } else {
            /********* update groups for a version < 1.3.0 ************************************************************/
	        $valueArray = StringUtil::deserialize($value);
            if (!is_array($valueArray[0])) {
                $newValues = [];
                $key = 1;
                foreach ($valueArray as $value) {
                    $newValues[] = [
                        'key' => $key++,
                        'value' => $value,
                    ];
                }
                $value = serialize($newValues);
            }
        }
		return $value;
	}

    public function setEssentialGroup($value,DC_Table $dca)
    {
        $groups = StringUtil::deserialize($value);
        if (!is_array($groups[0])) {
            $isExist = false;
            foreach ($groups as $group) {
                if ($group['key'] == '1') {
                    $isExist = true;
                }
            }
            if (!$isExist){
                $temp = $groups[0];
                $groups[0] = [
                    'key' => '1',
                    'value' => $GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['essential'],
                ];
                if (!empty($temp)) {
                    $groups[array_key_last($groups)+1] = $temp;
                }
            }
            $value = serialize($groups);
        }
        $this->saveInNcoiTable($value,$dca);
        return null;
	}

	public function getGroups(DC_Table $dca,$id = null)
	{
        if (empty($id)) {
	        $fieldPaletteModel = FieldPaletteModel::findByPid($dca->id)[0];
        } else {
            $fieldPaletteModel = FieldPaletteModel::findById($id);
        }

        $cookieToolGroups = '';
        if (!empty($fieldPaletteModel)) {

            $modul = ModuleModel::findById($fieldPaletteModel->pid);
            if (isset($modul->cookieGroups))
                $cookieToolGroups = $modul->cookieGroups;
            else
                $cookieToolGroups = $this->loadFromNcoiTable(null,$dca,$modul->id,'cookieGroups');
        }
        return $cookieToolGroups;
	}

    public function getGroupKeys(DC_Table $dca)
    {
        $groups = $this->getGroups($dca,$dca->__get('id'));
        $groups = $this->getDefaultGroups($groups,$dca);
        $groups = StringUtil::deserialize($groups);
        $groupValues = [];
        foreach ($groups as $group) {
            $groupValues[$group['key']] = $group['value'];
        }
        return $groupValues;
    }

	public function setCookieVersion(DC_Table $dca)
	{
	    if ($this->checkRightModule($dca->__get('field'))) {
		    $isNewCookieVersion = $this->loadFromNcoiTable('',$dca,null,'isNewCookieVersion');
            $cookieVersion = $this->loadFromNcoiTable('',$dca,null,'cookieVersion');
            if (!empty($isNewCookieVersion) || empty($cookieVersion)) {
                if ($cookieVersion === true) {
                    $cookieVersion = 1;
                }
                $this->saveInNcoiTable(++$cookieVersion,$dca,'','cookieVersion');
            }
		}
	}

	public function getNetzhirschCookie($fieldValue,DC_Table $dca)
	{
		$id = $dca->id;
		
		$fieldPalettes = FieldPaletteModel::findByPid($id);
		$csrfCookieFieldModel = null;
        $csrfHttpsCookieFieldModel = null;
		$phpSessIdCookieFieldModel = null;
        $feUserAuthCookieFieldModel = null;
		if (!empty($fieldPalettes)) {
		    /** @var FieldPaletteModel $fieldPalette */
            foreach ($fieldPalettes as $fieldPalette) {
				if ($fieldPalette->cookieToolsTechnicalName == '_netzhirsch_cookie_opt_in'
                    || $fieldPalette->cookieToolsSelect == 'optInCookie'
                ) {
					$fieldPalette->delete();
				} elseif ($fieldPalette->cookieToolsTechnicalName == 'csrf_contao_csrf_token') {
					$csrfCookieFieldModel = $fieldPalette;
                } elseif ($fieldPalette->cookieToolsTechnicalName == 'csrf_https-contao_csrf_token') {
                    $csrfHttpsCookieFieldModel = $fieldPalette;
				} elseif ($fieldPalette->cookieToolsTechnicalName == 'PHPSESSID') {
					$phpSessIdCookieFieldModel = $fieldPalette;
				} elseif ($fieldPalette->cookieToolsTechnicalName == 'FE_USER_AUTH') {
                    $feUserAuthCookieFieldModel = $fieldPalette;
                }
			}
		}
        $toolsDeactivate = $this->loadFromNcoiTable('',$dca,$id,'toolsDeactivate');
        if (!empty($toolsDeactivate))
            $toolsDeactivate = StringUtil::deserialize($toolsDeactivate);

		if (empty($csrfCookieFieldModel)) {
		    $cookieToolsTechnicalName = 'csrf_contao_csrf_token';
			if (
			    !empty($toolsDeactivate)
                && !in_array($cookieToolsTechnicalName,$toolsDeactivate)
                || empty($toolsDeactivate)
            ) {
                $csrfCookieFieldModel = new FieldPaletteModel();

                $csrfCookieFieldModel->pid = $id;
                $csrfCookieFieldModel->ptable = 'tl_module';
                $csrfCookieFieldModel->pfield = 'cookieTools';
                $csrfCookieFieldModel->sorting = '1';
                $csrfCookieFieldModel->tstamp = time();
                $csrfCookieFieldModel->dateAdded = time();
                $csrfCookieFieldModel->published = '1';
                $csrfCookieFieldModel->cookieToolsName = 'Contao CSRF Token';
                $csrfCookieFieldModel->cookieToolsTechnicalName = $cookieToolsTechnicalName;
                $csrfCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
                $csrfCookieFieldModel->cookieToolsProvider = '';
                $csrfCookieFieldModel->cookieToolExpiredTime = '0';
                $csrfCookieFieldModel->cookieToolsSelect = '-';
                $csrfCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'];
                $csrfCookieFieldModel->cookieToolGroup = '1';

                $csrfCookieFieldModel->save();
            }

		} elseif (!isset($csrfCookieFieldModel->cookieToolExpiredTime)) {
            $csrfCookieFieldModel->cookieToolExpiredTime = '0';
            $csrfCookieFieldModel->save();
        }

		if (empty($csrfHttpsCookieFieldModel)) {
            $cookieToolsTechnicalName = 'csrf_https-contao_csrf_token';
            if (
                !empty($toolsDeactivate)
                && !in_array($cookieToolsTechnicalName,$toolsDeactivate)
                || empty($toolsDeactivate)
            ) {
                $csrfHttpsCookieFieldModel = new FieldPaletteModel();

                $csrfHttpsCookieFieldModel->pid = $id;
                $csrfHttpsCookieFieldModel->ptable = 'tl_module';
                $csrfHttpsCookieFieldModel->pfield = 'cookieTools';
                $csrfHttpsCookieFieldModel->sorting = '1';
                $csrfHttpsCookieFieldModel->tstamp = time();
                $csrfHttpsCookieFieldModel->dateAdded = time();
                $csrfHttpsCookieFieldModel->published = '1';
                $csrfHttpsCookieFieldModel->cookieToolsName = 'Contao HTTPS CSRF Token';
                $csrfHttpsCookieFieldModel->cookieToolsTechnicalName = $cookieToolsTechnicalName;
                $csrfHttpsCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
                $csrfHttpsCookieFieldModel->cookieToolsProvider = '';
                $csrfHttpsCookieFieldModel->cookieToolExpiredTime = '0';
                $csrfHttpsCookieFieldModel->cookieToolsSelect = '-';
                $csrfHttpsCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['contaoCsrfHttpsToken']['cookieToolsUse'];
                $csrfHttpsCookieFieldModel->cookieToolGroup = '1';

                $csrfHttpsCookieFieldModel->save();
            }
        }
		if (empty($phpSessIdCookieFieldModel)) {
            $cookieToolsTechnicalName = 'PHPSESSID';
            if (
                !empty($toolsDeactivate)
                && !in_array($cookieToolsTechnicalName,$toolsDeactivate)
                || empty($toolsDeactivate)
            ) {
                $phpSessIdCookieFieldModel = new FieldPaletteModel();

                $phpSessIdCookieFieldModel->pid = $id;
                $phpSessIdCookieFieldModel->ptable = 'tl_module';
                $phpSessIdCookieFieldModel->pfield = 'cookieTools';
                $phpSessIdCookieFieldModel->sorting = '1';
                $phpSessIdCookieFieldModel->tstamp = time();
                $phpSessIdCookieFieldModel->dateAdded = time();
                $phpSessIdCookieFieldModel->published = '1';
                $phpSessIdCookieFieldModel->cookieToolsName = 'PHP SESSION ID';
                $phpSessIdCookieFieldModel->cookieToolsTechnicalName = 'PHPSESSID';
                $phpSessIdCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
                $phpSessIdCookieFieldModel->cookieToolsProvider = '';
                $phpSessIdCookieFieldModel->cookieToolExpiredTime = '0';
                $phpSessIdCookieFieldModel->cookieToolsSelect = '-';
                $phpSessIdCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['phpSessionID']['cookieToolsUse'];
                $phpSessIdCookieFieldModel->cookieToolGroup = '1';

                $phpSessIdCookieFieldModel->save();
            }

		} elseif (!isset($phpSessIdCookieFieldModel->cookieToolExpiredTime)) {
            $phpSessIdCookieFieldModel->cookieToolExpiredTime = '0';
            $phpSessIdCookieFieldModel->save();
        }
        if (empty($feUserAuthCookieFieldModel)) {
            $cookieToolsTechnicalName = 'FE_USER_AUTH';
            if (
                !empty($toolsDeactivate)
                && !in_array($cookieToolsTechnicalName,$toolsDeactivate)
                || empty($toolsDeactivate)
            ) {
                $feUserAuthCookieFieldModel = new FieldPaletteModel();
                $feUserAuthCookieFieldModel->pid = $id;
                $feUserAuthCookieFieldModel->ptable = 'tl_module';
                $feUserAuthCookieFieldModel->pfield = 'cookieTools';
                $feUserAuthCookieFieldModel->sorting = '4';
                $feUserAuthCookieFieldModel->tstamp = time();
                $feUserAuthCookieFieldModel->dateAdded = time();
                $feUserAuthCookieFieldModel->published = '1';
                $feUserAuthCookieFieldModel->cookieToolsName = 'FE USER AUTH';
                $feUserAuthCookieFieldModel->cookieToolsTechnicalName = $cookieToolsTechnicalName;
                $feUserAuthCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
                $feUserAuthCookieFieldModel->cookieToolsProvider = '';
                $feUserAuthCookieFieldModel->cookieToolExpiredTime = '0';
                $feUserAuthCookieFieldModel->cookieToolsSelect = '-';
                $feUserAuthCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['FE_USER_AUTH']['cookieToolsUse'];
                $feUserAuthCookieFieldModel->cookieToolGroup = '1';
                $feUserAuthCookieFieldModel->save();
            }
        }
		
		if (!empty($fieldValue)) {
			$fieldValues = StringUtil::deserialize($fieldValue);
			$fieldValues[] = [
                $feUserAuthCookieFieldModel->id,
				$csrfCookieFieldModel->id,
				$phpSessIdCookieFieldModel->id,
			];
			$fieldValue = serialize($fieldValues);
		}

		return $fieldValue;
	}

    public function loadFromNcoiTableImpress($value,DC_Table $dca)
    {
        return $this->loadFromNcoiTable($value,$dca,'','imprint');
    }

    public function loadFromNcoiTablePrivacyPolicy($value,DC_Table $dca)
    {
        return $this->loadFromNcoiTable($value,$dca,'','privacyPolicy');
    }

    public function loadFromNcoiTableExcludePages($value,DC_Table $dca)
    {
        return $this->loadFromNcoiTable($value,$dca,'','excludePages');
    }

    public function loadFromNcoiTableHeadlineCookieOptInBar($value,DC_Table $dca)
    {
        if ($value == 'a:2:{s:4:"unit";s:2:"h2";s:5:"value";s:0:"";}'){
            $value = null;
        }
        return $this->loadFromNcoiTable($value,$dca);
    }

    public function loadFromNcoiTable($oldValue,DC_Table $dca,$pid = null,$field = null)
    {
        if (empty($field))
            $field = $dca->__get('field');
        if (empty($pid))
            $pid = $dca->__get('id');
        $conn = $dca->Database;
        $sql = "SELECT ".$field." FROM tl_ncoi_cookie WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        if ($data->count() > 0) {
            $valueNew = $data->fetchAssoc();
            $value = $valueNew[$field];
        }
        if (empty($value)) {
            $value = $oldValue;
        }
        if (empty($value)) {
            if (isset($GLOBALS['TL_LANG']['tl_module'][$field.'Default'])) {
                $value = $GLOBALS['TL_LANG']['tl_module'][$field.'Default'];
            }
        }
        /********* checkboxes ****************************************************************************************/
        if ($value == "1")
            $value = true;
        return $value;
    }

    public function loadFromNcoiTableDebug($oldValue,DC_Table $dca,$pid = null,$field = null)
    {
        if (empty($field))
            $field = $dca->__get('field');
        if (empty($pid))
            $pid = $dca->__get('id');
        $conn = $dca->Database;
        $sql = "SELECT ".$field." FROM tl_ncoi_cookie WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        if ($data->count() > 0) {
            $valueNew = $data->fetchAssoc();
            $value = $valueNew[$field];
        }
        if (empty($value)) {
            $value = $oldValue;
        }
        if (empty($value)) {
            if (isset($GLOBALS['TL_LANG']['tl_module'][$field.'Default'])) {
                $value = $GLOBALS['TL_LANG']['tl_module'][$field.'Default'];
            }
        }
        /********* checkboxes ****************************************************************************************/
        if ($value == "1")
            $value = true;
        return $value;
    }

    public function loadFromNcoiTableRevoke($oldValue,DC_Table $dca)
    {
        $field = $dca->__get('field');
        $pid = $dca->__get('id');
        $conn = $dca->Database;
        $sql = "SELECT ".$field." FROM tl_ncoi_cookie_revoke WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        if ($data->count() > 0) {
            $valueNew = $data->fetchAssoc();
            $value = $valueNew[$field];
        }
        if (empty($value)) {
            $value = $oldValue;
        }
        if (empty($value)) {
            if (isset($GLOBALS['TL_LANG']['tl_module'][$field.'Default'])) {
                $value = $GLOBALS['TL_LANG']['tl_module'][$field.'Default'];
            }
        }
        return $value;
    }

    public function loadFromNcoiTableCheckbox($value,DC_Table $dca)
    {
        $valueNew = $this->loadFromNcoiTable($value,$dca);
        $field = $dca->__get('field');
        return ['opt_'.$field.'_0' => $valueNew];
    }

    public function setPageTreeEntries(DC_Table $dca)
    {
        if ($this->checkRightModule($dca->__get('field'))) {
            $activeRecord = $dca->__get('activeRecord');
            $privacyPolicyOld = $activeRecord->__get('privacyPolicy');
            $privacyPolicyNew = $this->loadFromNcoiTable('',$dca,null,'privacyPolicy');
            if (!empty($privacyPolicyOld) && empty($privacyPolicyNew)) {
                $this->saveInNcoiTable($privacyPolicyNew,$dca,'','privacyPolicy');
            }
            $imprintOLd = $activeRecord->__get('imprint');
            $imprintNew = $this->loadFromNcoiTable('',$dca,null,'imprint');
            if (!empty($imprintOLd) && empty($imprintNew)) {
                $this->saveInNcoiTable($imprintNew,$dca,'','imprint');
            }
            $excludePagesOld = $activeRecord->__get('excludePages');
            $excludePagesNew = $this->loadFromNcoiTable('',$dca,null,'excludePages');
            if (!empty($excludePagesOld) && empty($excludePagesNew)) {
                $this->saveInNcoiTable($excludePagesNew,$dca,'','excludePages');
            }
            $activeRecord->__set('privacyPolicy',null);
            $activeRecord->__set('imprint',null);
            $activeRecord->__set('excludePages',null);
        }
    }
    public function saveInNcoiTableCheckbox($value,DC_Table $dca)
    {
        $value = $this->saveInNcoiTable($value,$dca);
        if ($value === null) {
            return '';
        }
        $field = $dca->__get('field');
        return ['opt_'.$field.'_0' => $value];
    }

    public function saveInNcoiTableCookieTools(DC_Table $dca)
    {
        $this->saveInNcoiTableCookies($dca,'cookieTools');
    }

    public function saveInNcoiTableOtherScripts(DC_Table $dca)
    {
        $this->saveInNcoiTableCookies($dca,'otherScripts');
    }

    public function saveInNcoiTableRevoke($value,DC_Table $dca)
    {
        $conn = $dca->Database;
        $pid = $dca->__get('id');
        $sql = "SELECT id FROM tl_ncoi_cookie_revoke WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        $id = $data->fetchAssoc();
        $field = $dca->__get('field');
        $set = [$field => $value];
        if (!empty($id)) {
            $sql = "UPDATE tl_ncoi_cookie_revoke %s WHERE pid =?";
        } else {
            $sql = "INSERT tl_ncoi_cookie_revoke %s";
            $set['pid'] = $pid;
        }
        $stmt = $conn->prepare($sql);
        $stmt->set($set);
        $stmt->execute($pid);
        return '';
    }

    public function saveInNcoiTablePageTree($value,DC_Table $dca)
    {
        $this->saveInNcoiTable($value,$dca);
        return '';
    }

    public function saveInNcoiTableCookies(DC_Table $dca,$field)
    {
        $id = $dca->__get('id');
        $conn = $dca->Database;
        $activeRecord = $dca->__get('activeRecord');
        $pid = $activeRecord->__get('pid');
        $sql = "SELECT ".$field." FROM tl_ncoi_cookie WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        $cookieGroups = $data->fetchAssoc();
        $cookieGroups = StringUtil::deserialize($cookieGroups[$field]);
        if (empty($cookieGroups)) {
            $cookieGroups[] = $id;
        } else {
            if (!in_array($id,$cookieGroups)) {
                $cookieGroups[] = $id;
            }
        }
        $cookieGroups = serialize($cookieGroups);
        $this->saveInNcoiTable($cookieGroups,$dca,$pid,$field);
    }

    public function setGroupsToNcoiTable(DC_Table $dca)
    {
        if ($this->checkRightModule($dca->__get('field'))) {
            $modulId = Input::get('id');
            $cookieOptInBarMod = ModuleModel::findById($modulId);
            if (isset($cookieOptInBarMod->otherScripts))
                $this->saveInNcoiTable($cookieOptInBarMod->otherScripts,$dca,$modulId,'otherScripts');
            if (isset($cookieOptInBarMod->cookieTools))
                $this->saveInNcoiTable($cookieOptInBarMod->cookieTools,$dca,$modulId,'cookieTools');
        }
    }

    public function saveInNcoiTable($value,DC_Table $dca,$pid = null,$field = null){
        $conn = $dca->Database;
        if (empty($pid))
            $pid = $dca->__get('id');
        $sql = "SELECT id FROM tl_ncoi_cookie WHERE pid=?";
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($pid);
        $id = $data->fetchAssoc();
        if (empty($field))
            $field = $dca->__get('field');
        $set = [$field => $value];
        if (!empty($id)) {
            $sql = "UPDATE tl_ncoi_cookie %s WHERE pid =?";
        } else {
            $sql = "INSERT tl_ncoi_cookie %s";
            $set['pid'] = $pid;
        }
        $stmt = $conn->prepare($sql);
        $stmt->set($set);
        $stmt->execute($pid);
    }

    public function deleteTool(DC_Table $dca)
    {
        $id = Input::get('id');
        $sql = "SELECT pid,cookieToolsTechnicalName FROM tl_fieldpalette WHERE id=?";
        $conn = $dca->Database;
        $stmt = $conn->prepare($sql);
        $data = $stmt->execute($id);
        $return = $data->fetchAssoc();
        if (!empty($return['pid'])) {

            $pid = $return['pid'];
            $sql = "SELECT toolsDeactivate FROM tl_ncoi_cookie WHERE pid=?";
            $conn = $dca->Database;
            $stmt = $conn->prepare($sql);
            $data = $stmt->execute($pid);
            $toolsDeactivateDB = $data->fetchAssoc();
            $toolsDeactivate = [];
            if (!empty($toolsDeactivateDB['toolsDeactivate']))
                $toolsDeactivate = StringUtil::deserialize($toolsDeactivateDB['toolsDeactivate']);
            $toolsDeactivate[]= $return['cookieToolsTechnicalName'];
            $sql = "UPDATE tl_ncoi_cookie %s WHERE pid=?";
            $conn = $dca->Database;
            $stmt = $conn->prepare($sql);
            $toolsDeactivate = serialize($toolsDeactivate);
            $stmt->set(['toolsDeactivate' => $toolsDeactivate]);
            $stmt->execute($pid);
        }
    }
    public function checkRightModule($field)
    {
        if ($field == 'isNewCookieVersion')
            return true;
        else
            return false;
    }
}
