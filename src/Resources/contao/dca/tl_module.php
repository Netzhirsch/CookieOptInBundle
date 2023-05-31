<?php

use Contao\DC_Table;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Netzhirsch\CookieOptInBundle\Resources\contao\Classes\Helper;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\Entity\CookieToolContainer;
use Netzhirsch\CookieOptInBundle\Entity\OtherScriptContainer;
use Netzhirsch\CookieOptInBundle\Repository\Repository;
use Symfony\Component\HttpFoundation\RequestStack;
use Contao\System;

/** @var RequestStack $requestStack */
$scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');
$requestStack = System::getContainer()->get('request_stack');
$request = $requestStack->getCurrentRequest();
if (!empty($request) && $scopeMatcher->isBackendRequest($request)) {
    $GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';
    if (!isset($GLOBALS['TL_JAVASCRIPT']))
        $GLOBALS['TL_JAVASCRIPT'] = [];
    $GLOBALS['TL_JAVASCRIPT']['jquery'] = 'assets/jquery/js/jquery.min.js|static';
    $GLOBALS['TL_JAVASCRIPT']['ncoi'] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.js|static';
    // Loading missing Language-File 'tl_layout'
    System::loadLanguageFile('tl_layout');
}
/** Revoke Modul ***********************************************/

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
	'label' => ['Button-Text','Bitte geben Sie den Text des Revoke-Buttons ein.'],
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
	'label' => ['Template' , 'Der Template-Name muss mit mod_cookie_opt_in_revoke beginnen.'],
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
	;saveButton
	,saveAllButton
	,highlightSaveAllButton
	,rejectAllButton
	;infoButtonShow
	,infoButtonHide
	,infoHint
	;align
	,space
	,cssID
	;cookieGroups
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
	;languageSwitch
';

// setCookieVersion check for right modul
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setCookieVersion'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setPageTreeEntries'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setLessVariables'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = ['tl_module_ncoi','setGroupsToNcoiTable'];
$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = ['tl_module_ncoi','getNetzhirschCookie'];

$GLOBALS['TL_DCA']['tl_module']['fields']['headlineCookieOptInBar'] = [
	'label' => ['Überschrift'],
	'explanation' => ['Überschrift'],
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
	'label' => ['Hinweistext in der Cookie Bar'],
	'explanation' => ['Hinweistext in der Cookie Bar'],
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
	'label' => ['Speichern-Button','Bitte geben Sie die Beschriftung des Speichern-Buttons ein.'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'long',
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.saveButton',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['saveAllButton'] = [
	'label' => ['Alle annehmen-Button','Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookies annimmt. Falls es nur essenzielle Cookies gibt, wird dieser Button ausgeblendet.'],
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
    'label' => ['Alle annehmen-Button hervorheben'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'w50 clr',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['rejectAllButton'] = [
	'label' => ['Alle ablehnen-Button','Bitte geben Sie die Beschriftung des Buttons ein, der alle nicht essenziell Cookies ablehnt. Falls es nur essenzielle Cookies gibt, wird dieser Button ausgeblendet.'],
	'exclude'   => true,
	'inputType' => 'text',
	'eval' => [
		'tl_class' => 'long clr',
		'alwaysSave' => true,
        'doNotSaveEmpty' => true,
	],
    'foreignKey' => 'tl_ncoi_cookie.rejectAllButton',
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['infoButtonShow'] = [
    'label' => ['Info-anzeigen-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen anzeigt.'],
    'explanation' => ['Info-anzeigen-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen anzeigt.'],
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
    'label' => ['Info-ausblenden-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen ausblendet.'],
    'explanation' => ['Info-ausblenden-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen ausblendet.'],
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
	'label' => ['Informationen','Bitte geben Sie den Informationstext ein, der beim Blick auf den Info-anzeigen-Button erscheinen soll.'],
	'explanation' => ['Informationen','Bitte geben Sie den Informationstext ein, der beim Blick auf den Info-anzeigen-Button erscheinen soll.'],
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
	'label' => ['Alle gesetzten Cookies zurücksetzen','Sie sollten diese Option bei datenschutzrelevanten Änderungen aktivieren. Jeder Besucher bekommt anschließend erneut die Cookie Bar angezeigt.'],
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
	'label' => ['Cookie Gruppen','Der Schlüssel dient der internen Verarbeitung, der Wert wird im Frontend angezeigt.'],
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
    'label'     => ['Tools','<a href="https://www.netzhirsch.de/contao-cookie-opt-in-bundle.html#ccoi-examples" target="_blank">Klicken Sie hier für eine Hilfestellung.</a>'],
    'exclude'   => true,
    'inputType' => 'group',
    'storage' => 'entity',
    'entity' => CookieToolContainer::class,
    'order' => true,
    'palette' => [
        'cookieToolsName',
        'cookieToolsSelect',
        'cookieToolsTechnicalName',
        'cookieToolsTrackingId',
        'cookieToolsTrackingServerUrl',
        'cookieToolsProvider',
        'cookieToolsPrivacyPolicyUrl',
        'cookieToolsUse',
        'cookieToolGroup',
        'cookieToolExpiredTime',
        'i_frame_blocked_urls',
        'i_frame_blocked_text'
    ],
    'fields' => [
        'cookieToolsName' => [
            'label'     => ['Cookie Name','z.B. Google Analytics'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsSelect' => [
            'label'     => ['Type'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => [
                'googleAnalytics' => 'Google Analytics',
                'googleTagManager' => 'Google Tag Manager',
                'facebookPixel' => 'Facebook Pixel',
                'matomo' => 'Matomo',
                'youtube' => 'YouTube',
                'vimeo' => 'Vimeo',
                'googleMaps' => 'iFrame [Google Maps]',
                'iframe' => 'iFrame [Andere]',
                'script' => 'HTML-Element [script]',
                '-' => '-'
            ],
            'eval' => [
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsTechnicalName' => [
            'label'     => ['Technischer Name','z.B. _gat,_gtag_UA_123456789_1 Komma getrennt. Wichtig zum Löschen der Cookies'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsTrackingId' => [
            'label'     => ['Tracking ID','z.B. UA-123456789-1 für Google Analytics'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'w50',
                'mandatory' => false,
            ],
        ],
        'cookieToolsTrackingServerUrl' => [
            'label'     => ['Tracking Server URL ','Nur für Matomo z.B. https://netzhirsch.matomo.cloud/'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsProvider' => [
            'label'     => ['Anbieter'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsPrivacyPolicyUrl' => [
            'label'     => ['Datenschutzerklärung URL','z.B. https://policies.google.com/privacy'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolsUse' => [
            'label'     => ['Zweck','Bitte geben Sie den Zweck des Cookies an.'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolGroup' => [
            'label'     => ['Cookie Gruppe'],
            'exclude'   => true,
            'inputType' => 'select',
            'options_callback' => ['tl_module_ncoi','getGroupKeys'],
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'w50',
            ],
        ],
        'cookieToolExpiredTime' => [
            'label' => 	['Ablauf in Tagen','Bitte geben Sie die Laufzeit des Cookies an.'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'rgxp'=>'natural',
                'tl_class'=>'w50',
            ],
        ],
        'i_frame_blocked_urls' => [
            'label' => 	['Blockierte URL','Bitte geben Sie hier die URL des IFrames ein. Sollten Sie keine angeben wird die entsprechende URL des IFrame Typen verwendet. Mehrere URLS bitte mit Komma getrennt.'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => false,
                'tl_class'=>'w50',
            ],
        ],
        'i_frame_blocked_text' => [
            'label' => 	['Blockierter Text','Bitte geben Sie hier den Text ein, der für dieses blockiert IFrame verwendet werden soll. Sollten Sie keinen angeben wird der entsprechende Text des IFrame Typen verwendet. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => false,
                'tl_class'=>'w50',
            ],
        ]
    ],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['otherScripts'] = [
	'label'     => ['Andere Skripte'],
	'exclude'   => true,
	'inputType' => 'group',
    'storage' => 'entity',
    'entity' => OtherScriptContainer::class,
    'order' => true,
    'palettes' => [
        'cookieToolsName',
        'cookieToolsTechnicalName',
        'cookieToolsProvider',
        'cookieToolsPrivacyPolicyUrl',
        'cookieToolsUse',
        'cookieToolGroup',
        'cookieToolExpiredTime',
        'cookieToolsCode'
    ],
    'fields' => [
        'cookieToolsName' => [
            'label'     => ['Cookie Name','z.B. Facebook Pixel'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolsTechnicalName' => [
            'label'     => ['Technischer Name','z.B. _gat,_gtag_UA_123456789_1 Komma getrennt. Wichtig zum Löschen der Cookies'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolsProvider' => [
            'label'     =>['Anbieter'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolsPrivacyPolicyUrl' => [
            'label'     => ['Datenschutzerklärung URL','z.B. https://policies.google.com/privacy'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolsUse' => [
            'label'     => ['Zweck','Bitte geben Sie den Zweck des Cookies an.'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolGroup' => [
            'label'     => ['Cookie Gruppe'],
            'exclude'   => true,
            'inputType' => 'select',
            'options_callback' => ['tl_module_ncoi','getGroupKeys'],
            'eval' => [
                'mandatory' => true,
                'tl_class'  =>  'long clr',
            ],
        ],
        'cookieToolExpiredTime' => [
            'label' => 	['Ablauf in Tagen','Bitte geben Sie die Laufzeit des Cookies an.'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'rgxp'=>'natural',
                'tl_class'=>'long',
            ],
        ],
        'cookieToolsCode' => [
            'label'     => ['JavaScript Code','Mit script-Tag. jQuery kann über $ genutzt werden.'],
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval' => [
                'mandatory' => true,
                'allowHtml' => true,
                'rte' => 'ace',
                'preserveTags' => true,
                'tl_class'  =>  'long clr',
            ],
        ],
    ],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['i_frame_video'] = [
    'label' => 	['Blockierte Videos','Bitte geben Sie hier den Text ein, der für blockiert Videos verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
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
    'label' => 	['Blockierte Karten','Bitte geben Sie hier den Text ein, der für blockiert Karten verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
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
    'label' => 	['Blockierte iFrames','Bitte geben Sie hier den Text ein, der für blockiert iFrames verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
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
    'label' => 	['Immer-laden-Checkbock', 'Bitte geben Sie die Beschriftung der Checkbox ein, die die Entscheidung für diesen Typen ändert.'],
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
    'label' => 	['Laden-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle blockierten Inhalte eines Typs lädt.'],
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
    'label' => 	['Ablauf in Tagen'
        ,'Bitte geben Sie die Laufzeit der Einwilligung an. Danach wird die Cookie Bar erneut eingeblendet.'],
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
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['privacyPolicy'] = [
	'label' => ['Datenschutzerklärung'],
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
	'label' => ['Impressum'],
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
	'label' => ['Cookie Bar auf folgenden Seiten nicht anzeigen.'],
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
	'label' => ['"Do Not Track" Browser-Einstellung respektieren','Wenn diese Browser-Einstellung gesetzt ist, wird die Cookie Bar nicht eingeblendet.'],
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
    'label' => ['Opt Out','Cookies setzen und bei Ablehnung löschen, nur in einigen Länder erlaubt. Nicht in Deutschland.'],
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
    'label' => 	['z-index-Einstellung','Erhöhen Sie diesen Wert, wenn das Cookie-Banner von anderen Elementen überdeckt wird.'],
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
    'label' => ['Nutzung der Seite unterbinden','Elemente der Seite können erst angeklickt werden, wenn Cookies aktzeptiert oder abgelehnt wurden.'],
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
	'label' => ['Standard-CSS laden','Die CSS-Datei des Cookie Opt In Bar-Moduls laden.'],
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
	'label' => ['Position'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'leftTop' => 'Links Oben',
		'leftCenter' => 'Links Mitte',
		'leftBottom' => 'Links Unten',
		'centerTop' => 'Mitte Oben',
		'centerCenter' => 'Mitte Mitte',
		'centerBottom' => 'Mitte Unten',
		'rightTop' => 'Rechts Oben',
		'rightCenter' => 'Rechts Mitte',
		'rightBottom' => 'Rechts Unten',
	],
	'eval' => [
		'tl_class'  =>  'w50',
        'doNotSaveEmpty' => true,
	],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTable']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTable']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cssTemplateStyle'] = [
	'label' => ['Template Style'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'dark' => 'dunkel',
		'light' => 'hell',
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
	'label' => ['Template' , 'Der Template-Name muss mit mod_cookie_opt_in_bar beginnen. Achtung auch das Template mod_cookie_opt_in_table muss überschrieben werden.'],
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
	'label' => ['Animation', 'Bei Klick auf die Buttons im Frontend'],
	'exclude'   => true,
	'inputType' => 'select',
	'options' => [
		'go-up' => 'rein-/rauszoomen',
		'shrink' => 'ein-/ausfahren',
        'shrink-and-rotate' => 'schrumpfen und drehen',
        'hinge' => 'Scharnier',
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
	'label' => ['Breite','Bitte geben Sie die Breite der Cookie Bar an.'],
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
    'label' => ['IP-Speicherung','Wählen Sie die Art des Loggings der IP-Adressen.'],
    'exclude'   => true,
    'inputType' => 'select',
    'options' => [
        'uncut' => 'ungekürzt',
        'pseudo' => 'pseudonymisiert',
        'anon' => 'anonymisiert',
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
    'label' => ['Alle gesetzten Cookies zurücksetzen','Sie sollten diese Option bei datenschutzrelevanten Änderungen aktivieren. Jeder Besucher bekommt anschließend erneut die Cookie Bar angezeigt.'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class'  =>  'long clr',
        'doNotSaveEmpty' => true,
    ],
    'save_callback' => [['tl_module_ncoi', 'saveInNcoiTableCheckbox']],
    'load_callback' => [['tl_module_ncoi', 'loadFromNcoiTableCheckbox']],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['languageSwitch'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['languageSwitch'],
    'inputType' => 'moduleWizard',
    'mandatory' => false,
    'sql' => "blob NULL"
];

ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);

class tl_module_ncoi extends tl_module {
    public function getDefaultMaxWidth($value,DC_Table $dca){

        $value = $this->loadFromNcoiTable($value,$dca);
	    if (empty($value) || $value == 'a:2:{s:5:"value";s:0:"";s:4:"unit";s:2:"px";}')
			$value = 'a:2:{s:5:"value";s:3:"500";s:4:"unit";s:2:"px";}';

		return $value;
	}

    /**
     * @param DC_Table $dca
     * @return DC_Table
     * @throws Less_Exception_Parser
     */
	public function setLessVariables(DC_Table $dca){
        if ($this->checkRightModule($dca->__get('field'))) {
            $repo = new Repository($dca->Database);
            $strQuery = "SELECT maxWidth,blockSite,zIndex FROM tl_ncoi_cookie WHERE pid = ?";
            $data = $repo->findRow($strQuery,[], [Input::get('id')]);
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
            //TODO*luhmann 2020-12-14: hier muss noch die richtige ID gefunden werden
//	        $fieldPaletteModel = FieldPaletteModel::findByPid($dca->id)[0];
        } else {
//            $fieldPaletteModel = FieldPaletteModel::findById($id);
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

	public function getNetzhirschCookie(DC_Table $dca)
	{
        $em = $dca->getContainer()->get('doctrine.orm.entity_manager');
        if (Input::post('type') != 'cookieOptInBar')
            return;

        $repoCookieToolContainer = $em->getRepository(CookieToolContainer::class);
        $cookieToolContainer = $repoCookieToolContainer->findOneBy([
            'sourceId' => $dca->id,
        ]);
        if (empty($cookieToolContainer)) {
            $cookieToolContainer = new CookieToolContainer();
        } else {
            $repoCookieTool = $em->getRepository(CookieTool::class);
            $csrfToken = $repoCookieTool->findOneBy([
                'cookieToolsTechnicalName' => 'csrf_contao_csrf_token',
                'parent' => $cookieToolContainer->getId(),
            ]);
            $csrfTokenHttps = $repoCookieTool->findOneBy([
                'cookieToolsTechnicalName' => 'csrf_https_contao_csrf_token',
                'parent' => $cookieToolContainer->getId(),
            ]);
            $PHPSESSID = $repoCookieTool->findOneBy([
                'cookieToolsTechnicalName' => 'PHPSESSID',
                'parent' => $cookieToolContainer->getId(),
            ]);
            $FE_USER_AUTH = $repoCookieTool->findOneBy([
                'cookieToolsTechnicalName' => 'FE_USER_AUTH',
                'parent' => $cookieToolContainer->getId(),
            ]);
        }

        if (empty($csrfToken)) {
            $csrfToken = CookieTool::createDefault(
                $cookieToolContainer,
                'Contao CSRF Token',
                'csrf_contao_csrf_token',
                $GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'],
                1
            );
            $em->persist($csrfToken);
        }

        if (empty($csrfTokenHttps)) {
            $csrfTokenHttps = CookieTool::createDefault(
                $cookieToolContainer,
                'Contao HTTPS CSRF Token',
                'csrf_https_contao_csrf_token',
                $GLOBALS['TL_LANG']['tl_module']['contaoCsrfHttpsToken']['cookieToolsUse'],
                2
            );
            $em->persist($csrfTokenHttps);
        }

        if (empty($PHPSESSID)) {
            $PHPSESSID = CookieTool::createDefault(
                $cookieToolContainer,
                'PHP SESSION ID',
                'PHPSESSID',
                $GLOBALS['TL_LANG']['tl_module']['phpSessionID']['cookieToolsUse'],
                3
            );
            $em->persist($PHPSESSID);
        }

        if (empty($FE_USER_AUTH)) {
            $FE_USER_AUTH = CookieTool::createDefault(
                $cookieToolContainer,
                'FE USER AUTH',
                'FE_USER_AUTH',
                $GLOBALS['TL_LANG']['tl_module']['FE_USER_AUTH']['cookieToolsUse'],
                4
            );
            $em->persist($FE_USER_AUTH);
        }

        $em->flush();
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
        if (!empty($oldValue))
            return $oldValue;
        if (empty($field))
            $field = $dca->__get('field');
        if (empty($pid))
            $pid = $dca->__get('id');

        $conn = $dca->Database;
        $repo = new Repository($conn);
        $strQuery = "SELECT ".$field." FROM tl_ncoi_cookie WHERE pid = ?";
        $data = $repo->findRow($strQuery,[], [$pid]);
        $valueFromNetzhirschTable = null;
        if (isset($data[$field]))
            $valueFromNetzhirschTable = $data[$field];

        if ($valueFromNetzhirschTable === null) {
            if (isset($GLOBALS['TL_LANG']['tl_module'][$field.'Default'])) {
                return $GLOBALS['TL_LANG']['tl_module'][$field.'Default'];
            }
        } elseif($valueFromNetzhirschTable == "0") {
            return false;
        }
        /********* checkboxes ****************************************************************************************/
        if ($valueFromNetzhirschTable == "1")
            return true;
        return $valueFromNetzhirschTable;
    }

    public function loadFromNcoiTableRevoke($oldValue,DC_Table $dca)
    {
        $field = $dca->__get('field');
        $pid = $dca->__get('id');
        $conn = $dca->Database;
        $repo = new Repository($conn);
        $strQuery = "SELECT ".$field." FROM tl_ncoi_cookie_revoke WHERE pid=?";
        $result = $repo->findRow($strQuery,[], [$pid]);
        if ($field == 'templateRevoke') {
            return $oldValue;
        }
        if (empty($result) || empty($result[$field])) {
            return $GLOBALS['TL_LANG']['tl_module'][$field.'Default'];
        }
        return $result[$field];

    }

    public function loadFromNcoiTableCheckbox($value,DC_Table $dca): array
    {
        $valueNew = $this->loadFromNcoiTable($value,$dca);
        $field = $dca->__get('field');
        return ['opt_'.$field.'_0' => $valueNew];
    }

    public function setPageTreeEntries(DC_Table $dca): void
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

    public function saveInNcoiTableCheckbox($value,DC_Table $dca): array|string
    {
        $value = $this->saveInNcoiTable($value,$dca);
        if ($value === null) {
            return '';
        }
        $field = $dca->__get('field');
        return ['opt_'.$field.'_0' => $value];
    }

    public function saveInNcoiTableCookieTools(DC_Table $dca): void
    {
        $this->saveInNcoiTableCookies($dca,'cookieTools');
    }

    public function saveInNcoiTableOtherScripts(DC_Table $dca): void
    {
        $this->saveInNcoiTableCookies($dca,'otherScripts');
    }

    public function saveInNcoiTableRevoke($value,DC_Table $dca)
    {
        $conn = $dca->Database;
        $repo = new Repository($conn);
        return $repo->updateOrInsert($dca, 'tl_ncoi_cookie_revoke', $value);
    }

    public function saveInNcoiTablePageTree($value,DC_Table $dca): string
    {
        $this->saveInNcoiTable($value,$dca);
        return '';
    }

    public function saveInNcoiTableCookies(DC_Table $dca,$field): void
    {
        $id = $dca->__get('id');
        $conn = $dca->Database;
        $activeRecord = $dca->__get('activeRecord');
        $pid = $activeRecord->__get('pid');
        $repo = new Repository($conn);
        $strQuery = "SELECT ".$field." FROM tl_ncoi_cookie WHERE pid=?";
        $data = $repo->findRow($strQuery,[],[$pid]);
        $cookieGroups = null;
        if (isset($data[$field])) {
            $cookieGroups = $data[$field];
            $cookieGroups = StringUtil::deserialize($cookieGroups);
        }
        if (empty($cookieGroups)) {
            $cookieGroups = [];
            $cookieGroups[] = $id;
        } else {
            if (!in_array($id,$cookieGroups)) {
                $cookieGroups[] = $id;
            }
        }
        $cookieGroups = serialize($cookieGroups);
        $this->saveInNcoiTable($cookieGroups,$dca,$pid,$field);
    }

    public function setGroupsToNcoiTable(DC_Table $dca): void
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
        $repo = new Repository($conn);
        $repo->updateOrInsert($dca, 'tl_ncoi_cookie', $value,$pid,$field);
        return $field;
    }

    public function checkRightModule($field): bool
    {
        if ($field == 'isNewCookieVersion')
            return true;
        else
            return false;
    }
}
