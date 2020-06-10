<?php

use Contao\DC_Table;
use Contao\ModuleModel;
use Contao\StringUtil;
use HeimrichHannot\FieldpaletteBundle\Model\FieldPaletteModel;
use Netzhirsch\CookieOptInBundle\Classes\Helper;

/** Revoke Modul ***********************************************/

$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';
$GLOBALS['TL_JAVASCRIPT']['jquery'] = 'bundles/netzhirschcookieoptin/jquery.min.js|static';
$GLOBALS['TL_JAVASCRIPT']['ncoi'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.js|static';

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
		'alwaysSave' => true
	],
	'sql' => "varchar(255) NULL default ''",
	'load_callback' => [['tl_module_ncoi','getDefaultRevokeButton']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['templateRevoke'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['templateRevoke'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => $this->getTemplateGroup('mod_cookie_opt_in_revoke'),
	'eval' => [
		'tl_class'  =>  'w50 clr',
	],
	'sql' => "varchar(64) NULL default '' ",
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
	;cookieExpiredTime
	;privacyPolicy
	,imprint
	,excludePages
	;respectToNotTrack
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
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'] = [['tl_module_ncoi','setCookieVersion'],['tl_module_ncoi','setLessVariables']];
$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'] = [['tl_module_ncoi','setCookieGroups']];


$GLOBALS['TL_DCA']['tl_module']['fields']['headlineCookieOptInBar'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['headline'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['headline'],
	'exclude'   => true,
	'inputType' => 'inputUnit',
	'options' => [
		'h2',
		'h3',
		'h4',
		'h5'
	],
	'eval' => [
		'tl_class'=>'w50 clr',
	],
	'sql' => "text NULL default ''"
];


$GLOBALS['TL_DCA']['tl_module']['fields']['questionHint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['questionHint'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['questionHint'],
	'exclude'   => true,
	'inputType' => 'textarea',
	'eval' => [
		'tl_class'=>'long clr',
		'alwaysSave' => true,
	],
	'sql' => "text NULL default ''",
	'load_callback' => [['tl_module_ncoi','getDefaultQuestionHint']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['saveButton'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['saveButton'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'w50',
		'alwaysSave' => true
	],
	'sql' => "text NULL default ''",
	'load_callback' => [['tl_module_ncoi','getDefaultSaveButton']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['saveAllButton'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['saveAllButton'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'w50',
		'alwaysSave' => true
	],
	'sql' => "text NULL default ''",
	'load_callback' => [['tl_module_ncoi','getDefaultsaveAllButton']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['highlightSaveAllButton'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['highlightSaveAllButton'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'w50'
    ],
    'sql' => "varchar(1) NOT NULL DEFAULT 1",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoButtonShow'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonShow'],
    'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonShow'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'alwaysSave' => true,
        'tl_class'	=>	'w50 clr',
    ],
    'default' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonShowDefault'],
    'sql' => "varchar(255)  NULL default ''",
    'load_callback' => [['tl_module_ncoi','getDefaultInfoButtonShow']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoButtonHide'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonHide'],
    'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonHide'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'alwaysSave' => true,
        'tl_class'	=>	'w50',
    ],
    'default' => &$GLOBALS['TL_LANG']['tl_module']['infoButtonHideDefault'],
    'sql' => "varchar(255)  NULL default ''",
    'load_callback' => [['tl_module_ncoi','getDefaultInfoButtonHide']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoHint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['infoHint'],
	'explanation' => &$GLOBALS['TL_LANG']['tl_module']['infoHint'],
	'exclude'   => true,
	'inputType' => 'textarea',
	'eval' => [
		'alwaysSave' => true,
		'tl_class'	=>	'long clr',
	],
	'default' => &$GLOBALS['TL_LANG']['tl_module']['infoHintDefault'],
	'sql' => "text  NULL default ''",
	'load_callback' => [['tl_module_ncoi','getDefaultInfoHint']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['isNewCookieVersion'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval' => [
		'tl_class'  =>  'long clr',
	],
	'default' => '0',
	'sql' => "varchar(1) NULL",
];


$GLOBALS['TL_DCA']['tl_module']['fields']['cookieVersion'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['cookieVersion'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'style' => 'display:none'
	],
	'default' => '1',
	'sql' => "int(10) NULL",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieGroups'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['cookieGroups'],
	'exclude'   => true,
	'inputType' => 'keyValueWizard',
	'eval' => [
		'tl_class'  =>  'long clr',
		'submitOnChange' => true,
		'doNotCopy' => true,
		'alwaysSave' => true
	],
	'sql' => "text NULL default '' ",
	'load_callback' => [['tl_module_ncoi','getDefaultGroups']],
	'save_callback' => [['tl_module_ncoi','setEssentialGroup']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieTools'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieTools'],
	'exclude'   => true,
	'inputType' => 'fieldpalette',
	'foreignKey'   => 'tl_fieldpalette.id',
	'relation'     => ['type' => 'hasMany', 'load' => 'eager'],
	'sql'          => "blob NULL",
	'load_callback' => [['tl_module_ncoi','getNetzhirschCookie']],
	'fieldpalette' => [
		'config' => [
			'hidePublished' => true,
			'notSortable' => false
		],
		'list'     => [
			'label' => [
				'fields' => ['cookieToolsName','cookieToolGroup'],
				'format' => '%s <span style="color:#b3b3b3;padding-left:3px">[%s]</span>',
			],
            'fields' => ['cookieToolGroup'],
            'flag' => 11,
            'panelLayout' => [
                'sort' => true
            ],
            'mode' => 2
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
				cookieToolExpiredTime
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
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsSelect' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'],
				'exclude'   => true,
				'inputType' => 'select',
				'options'   => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelectOptions'],
				'sql' => "varchar(32) default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsTechnicalName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsTrackingId' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsTrackingServerUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				]
			],
			'cookieToolsProvider' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				]
			],
			'cookieToolsPrivacyPolicyUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				]
			],
			'cookieToolsUse' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'],
				'exclude'   => true,
				'inputType' => 'textarea',
				'sql' => "text NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr'
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
					'tl_class'  =>  'long clr'
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
	'sql'          => "blob NULL",
	'fieldpalette' => [
		'config' => [
			'hidePublished' => true,
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
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsUse' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'],
				'exclude'   => true,
				'inputType' => 'textarea',
				'sql' => "text NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsPrivacyPolicyUrl' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsTechnicalName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NOT NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr'
				],
			],
			'cookieToolsName' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'],
				'exclude'   => true,
				'inputType' => 'text',
				'sql' => "varchar(255) NULL default '' ",
				'eval' => [
					'mandatory' => true,
					'tl_class'  =>  'long clr'
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
					'tl_class'  =>  'long clr'
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
					'tl_class'  =>  'long clr'
				],
			],
		],
	],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cookieExpiredTime'] = [
	'label' => 	&$GLOBALS['TL_LANG']['tl_module']['cookieExpiredTime'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'mandatory' => true,
		'rgxp'=>'natural',
		'tl_class'=>'long',
        'alwaysSave' => true
	],
	'sql' => "int(2) NULL ",
    'load_callback' => [['tl_module_ncoi','getCookieExpiredTime']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['privacyPolicy'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval' => [
		'tl_class'  =>  'w50',
	],
	'sql' => "varchar(11) NULL default '' ",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['imprint'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['imprint'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval' => [
		'tl_class'  =>  'w50',
	],
	'sql' => "varchar(11) NULL default '' ",
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
	],
	'sql' => "blob NULL default '' ",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['respectToNotTrack'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['respectToNotTrack'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval' => [
		'tl_class'  =>  'w50 clr',
	],
	'sql' => "varchar(1) NULL default '0' ",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['zIndex'] = [
    'label' => 	&$GLOBALS['TL_LANG']['tl_module']['zIndex'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'rgxp'=>'natural',
        'tl_class'=>'long clr',
    ],
    'sql' => "int(7) NULL DEFAULT '1' ",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['blockSite'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['blockSite'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'long clr'
    ],
    'sql' => "varchar(1) NOT NULL DEFAULT 0",
];


$GLOBALS['TL_DCA']['tl_module']['fields']['defaultCss'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['defaultCss'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval' => [
		'tl_class'  =>  'long clr',
	],
	'sql' => "varchar(1) NULL default '1' ",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['position'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['position'],
	'exclude'   => true,
	'inputType' => 'select',
	'default' => 'centerCenter',
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
	],
	'sql' => "varchar(64) NULL default '' ",
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
	],
	'sql' => "varchar(255) NULL default '' ",
	'save_callback' => [['tl_module_ncoi','setCssFromLess']]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['templateBar'] = [
	'label' => &$GLOBALS['TL_LANG']['tl_module']['templateBar'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => $this->getTemplateGroup('mod_cookie_opt_in_bar'),
	'eval' => [
		'tl_class'  =>  'w50 clr',
	],
	'sql' => "varchar(64) NULL default '' ",
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
		'includeBlankOption' => true
	],
	'sql' => "varchar(64) NULL default '' ",
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
	
	],
	'sql' => "varchar(64) NULL default '' ",
	'load_callback' => [['tl_module_ncoi','getDefaultMaxWidth']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['ipFormatSave'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['ipFormatSave'],
    'exclude'   => true,
    'inputType' => 'select',
    'options' => [
        'uncut' => $GLOBALS['TL_LANG']['tl_module']['uncut'],
        'pseudo' => $GLOBALS['TL_LANG']['tl_module']['pseudo'],
        'anon' => $GLOBALS['TL_LANG']['tl_module']['anon']
    ],
    'eval' => [
        'tl_class'  =>  'w50 ncoi---list--container',
        'helpwizard' => true,
    ],
    'explanation'   => 'ipFormatSaveExplanation',
    'sql' => "varchar(64) NULL default '' ",
];

class tl_module_ncoi extends tl_module {

	public function getDefaultMaxWidth($value){
		
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
        $modulId = Input::get('id');
        $strField = $dca->__get('field');
        if ($strField == 'isNewCookieVersion') {
            $cookieOptInBarMod = ModuleModel::findById($modulId);
            Helper::parseLessToCss('netzhirschCookieOptIn.less','netzhirschCookieOptIn.css',$cookieOptInBarMod->maxWidth,$cookieOptInBarMod->blockSite,$cookieOptInBarMod->zIndex);
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
		Helper::parseLessToCss($styleSheet.'.less',$styleSheet.'.css');
		return $value;
	}
	
	public function getDefaultRevokeButton($value){
		
		if (empty($value))
			$value = $GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'];
		
		return $value;
	}
	
	public function getDefaultSaveButton($value){
		
		if (empty($value))
			$value = $GLOBALS['TL_LANG']['tl_module']['saveButtonDefault'];
		
		return $value;
	}
	
	public function getDefaultsaveAllButton($value){
		
		if (empty($value))
			$value = $GLOBALS['TL_LANG']['tl_module']['saveAllButtonDefault'];
		
		return $value;
	}
	
	public function getDefaultQuestionHint($value){
		
		if (empty($value))
			$value = $GLOBALS['TL_LANG']['tl_module']['questionHintDefault'];
		
		return $value;
	}
	
	public function getDefaultInfoHint($value){
		
		if (empty($value))
			$value = $GLOBALS['TL_LANG']['tl_module']['infoHintDefault'];
		
		return $value;
	}

    public function getDefaultInfoButtonShow($value){

        if (empty($value))
            $value = $GLOBALS['TL_LANG']['tl_module']['infoButtonShowDefault'];

        return $value;
    }

    public function getDefaultInfoButtonHide($value){

        if (empty($value))
            $value = $GLOBALS['TL_LANG']['tl_module']['infoButtonHideDefault'];

        return $value;
    }

    public function setCookieGroups(DC_Table $dca) {
        /********* update cookie groups for a version < 1.3.0 *****************************************************/
        $fieldPalettes = FieldPaletteModel::findByPid($dca->__get('id'));
        foreach ($fieldPalettes as $fieldPalette) {
            $tlLangGroups = $GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames'];
            $save = false;
            switch ($fieldPalette->cookieToolGroup) {
                case $tlLangGroups['essential']:
                    $fieldPalette->cookieToolGroup = 1;
                    $save = true;
                    break;
                case $tlLangGroups['analysis']:
                case 'Statistik':
                    $fieldPalette->cookieToolGroup = 2;
                    $save = true;
                    break;
                case $tlLangGroups['external_media']:
                    $fieldPalette->cookieToolGroup = 3;
                    $save = true;
                    break;
            };
            if ($save)
                $fieldPalette->save();
        }
    }
	public function getDefaultGroups($value,DC_Table $dca){
	    if (
		    empty($value)
            || $value == 'a:3:{i:0;a:2:{s:3:"key";s:1:"E";s:5:"value";s:1:"E";}i:1;a:2:{s:3:"key";s:1:"A";s:5:"value";s:1:"A";}i:2;a:2:{s:3:"key";s:1:"E";s:5:"value";s:1:"E";}}'
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
                        'value' => $value
                    ];
                }
                $value = serialize($newValues);
            }
        }
		return $value;
	}

    public function setEssentialGroup($value)
    {
        $groups = StringUtil::deserialize($value);
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
                'value' => $GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['essential']
            ];
            if (!empty($temp)) {
                $groups[array_key_last($groups)+1] = $temp;
            }
        }
        return serialize($groups);
	}
	
	public function getGroups(DC_Table $dca)
	{
		$fieldPaletteModel = FieldPaletteModel::findById($dca->id);
		$modul = ModuleModel::findById($fieldPaletteModel->pid);
		$cookieToolGroups = $modul->cookieGroups;
		if (empty($cookieToolGroups)){
			$cookieToolGroups = $GLOBALS['TL_LANG']['tl_module']['cookieGroupsDefault'];
		}
		return $cookieToolGroups;
	}

    public function getGroupKeys(DC_Table $dca)
    {
        $groups = $this->getGroups($dca);
        $groups = StringUtil::deserialize($groups);
        $groupValues = [];
        foreach ($groups as $group) {
            $groupValues[$group['key']] = $group['value'];
        }
        return $groupValues;
    }

	public function setCookieVersion(DC_Table $dca)
	{
		$strField = $dca->__get('field');
		if ($strField == 'isNewCookieVersion') {
			$cookieOptInBarMod = ModuleModel::findOneByType('cookieOptInBar');
			if (!empty($cookieOptInBarMod->isNewCookieVersion)) {
				$cookieOptInBarMod->cookieVersion++;
				$cookieOptInBarMod->save();
			}
		}
		return $dca;
	}

	public function getNetzhirschCookie($fieldValue,DC_Table $dca)
	{
		$id = $dca->id;
		
		$fieldPalettes = FieldPaletteModel::findByPid($id);
		$netzhirschCookieFieldModel = null;
		$csrfCookieFieldModel = null;
        $csrfHttpsCookieFieldModel = null;
		$phpSessIdCookieFieldModel = null;
		if (!empty($fieldPalettes)) {
			foreach ($fieldPalettes as $fieldPalette) {
				if ($fieldPalette->cookieToolsTechnicalName == '_netzhirsch_cookie_opt_in'
                    || $fieldPalette->cookieToolsSelect == 'optInCookie'
                ) {
					$netzhirschCookieFieldModel = $fieldPalette;
				} elseif ($fieldPalette->cookieToolsTechnicalName == 'csrf_contao_csrf_token') {
					$csrfCookieFieldModel = $fieldPalette;
                } elseif ($fieldPalette->cookieToolsTechnicalName == 'csrf_https_contao_csrf_token') {
                    $csrfHttpsCookieFieldModel = $fieldPalette;
				} elseif ($fieldPalette->cookieToolsTechnicalName == 'PHPSESSID') {
					$phpSessIdCookieFieldModel = $fieldPalette;
				}
			}
		}
		if (empty($netzhirschCookieFieldModel)) {
			$netzhirschCookieFieldModel = new FieldPaletteModel();
			$netzhirschCookieFieldModel->pid = $id;
			$netzhirschCookieFieldModel->ptable = 'tl_module';
			$netzhirschCookieFieldModel->pfield = 'cookieTools';
			$netzhirschCookieFieldModel->sorting = '1';
			$netzhirschCookieFieldModel->tstamp = time();
			$netzhirschCookieFieldModel->dateAdded = time();
			$netzhirschCookieFieldModel->published = '1';
			$netzhirschCookieFieldModel->cookieToolsName = 'Netzhirsch';
			$netzhirschCookieFieldModel->cookieToolsTechnicalName = '_netzhirsch_cookie_opt_in';
			$netzhirschCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
			$netzhirschCookieFieldModel->cookieToolsProvider = '';
			$netzhirschCookieFieldModel->cookieToolExpiredTime = '30';
			$netzhirschCookieFieldModel->cookieToolsSelect = 'optInCookie';
			$netzhirschCookieFieldModel->cookieToolsUse =
                $GLOBALS['TL_LANG']['tl_module']['netzhirschCookieFieldModel']['cookieToolsUse'];
			$netzhirschCookieFieldModel->cookieToolGroup = '1';
			
            $netzhirschCookieFieldModel->save();
		} elseif (!isset($netzhirschCookieFieldModel->cookieToolExpiredTime)) {
            $netzhirschCookieFieldModel->cookieToolExpiredTime = '30';
            $netzhirschCookieFieldModel->save();
        } elseif($netzhirschCookieFieldModel->cookieToolsSelect == '-') {
            $netzhirschCookieFieldModel->cookieToolsSelect = 'optInCookie';
            $netzhirschCookieFieldModel->save();
        }

		if (empty($csrfCookieFieldModel)) {
			
			$csrfCookieFieldModel = new FieldPaletteModel();
			
			$csrfCookieFieldModel->pid = $id;
			$csrfCookieFieldModel->ptable = 'tl_module';
			$csrfCookieFieldModel->pfield = 'cookieTools';
			$csrfCookieFieldModel->sorting = '1';
			$csrfCookieFieldModel->tstamp = time();
			$csrfCookieFieldModel->dateAdded = time();
			$csrfCookieFieldModel->published = '1';
			$csrfCookieFieldModel->cookieToolsName = 'Contao CSRF Token';
			$csrfCookieFieldModel->cookieToolsTechnicalName = 'csrf_contao_csrf_token';
			$csrfCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
			$csrfCookieFieldModel->cookieToolsProvider = '';
            $csrfCookieFieldModel->cookieToolExpiredTime = '0';
			$csrfCookieFieldModel->cookieToolsSelect = '-';
			$csrfCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'];
			$csrfCookieFieldModel->cookieToolGroup = '1';
			
			$csrfCookieFieldModel->save();

		} elseif (!isset($csrfCookieFieldModel->cookieToolExpiredTime)) {
            $csrfCookieFieldModel->cookieToolExpiredTime = '0';
            $csrfCookieFieldModel->save();
        }

		if (empty($csrfHttpsCookieFieldModel)) {
            $csrfHttpsCookieFieldModel = new FieldPaletteModel();

            $csrfHttpsCookieFieldModel->pid = $id;
            $csrfHttpsCookieFieldModel->ptable = 'tl_module';
            $csrfHttpsCookieFieldModel->pfield = 'cookieTools';
            $csrfHttpsCookieFieldModel->sorting = '1';
            $csrfHttpsCookieFieldModel->tstamp = time();
            $csrfHttpsCookieFieldModel->dateAdded = time();
            $csrfHttpsCookieFieldModel->published = '1';
            $csrfHttpsCookieFieldModel->cookieToolsName = 'Contao HTTPS CSRF Token';
            $csrfHttpsCookieFieldModel->cookieToolsTechnicalName = 'csrf_https_contao_csrf_token';
            $csrfHttpsCookieFieldModel->cookieToolsPrivacyPolicyUrl = '';
            $csrfHttpsCookieFieldModel->cookieToolsProvider = '';
            $csrfHttpsCookieFieldModel->cookieToolExpiredTime = '0';
            $csrfHttpsCookieFieldModel->cookieToolsSelect = '-';
            $csrfHttpsCookieFieldModel->cookieToolsUse = $GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'];
            $csrfHttpsCookieFieldModel->cookieToolGroup = '1';

            $csrfHttpsCookieFieldModel->save();
        }
		if (empty($phpSessIdCookieFieldModel)) {

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
			
		} elseif (!isset($phpSessIdCookieFieldModel->cookieToolExpiredTime)) {
            $phpSessIdCookieFieldModel->cookieToolExpiredTime = '0';
            $phpSessIdCookieFieldModel->save();
        }
		
		if (!empty($fieldValue)) {
			$fieldValues = StringUtil::deserialize($fieldValue);
			$fieldValues[] = [
				$netzhirschCookieFieldModel->id,
				$csrfCookieFieldModel->id,
				$phpSessIdCookieFieldModel->id,
			];
			$fieldValue = serialize($fieldValues);
		} else {
			$fieldValue = $netzhirschCookieFieldModel->id;
		}
		return $fieldValue;
	}

    public function getCookieExpiredTime($value)
    {
        return (empty($value)) ? "30" : $value;
	}
}
