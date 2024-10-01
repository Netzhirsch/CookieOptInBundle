<?php

/**
 * Extend default palette
 */
//Contao 4.9 need rootfallback and root

// on root page
if (empty($GLOBALS['_GET']))
	$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackend.css|static';
else
	$GLOBALS['TL_CSS'][] = 'bundles/netzhirschcookieoptin/netzhirschCookieOptInBackendPage.css|static';
	
$root = 'rootfallback';

$replace = '{ncoi_license_legend},bar_disabled,imprint,privacyPolicy;{global_legend';
$search = '{global_legend';

$GLOBALS['TL_DCA']['tl_page']['palettes'][$root] = str_replace($search, $replace, $GLOBALS['TL_DCA']['tl_page']['palettes'][$root]);

//Contao 4.4 need root, rootfallback will be ignored
$root = 'root';
if (isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$root]))
    $GLOBALS['TL_DCA']['tl_page']['palettes'][$root] = str_replace($search, $replace, $GLOBALS['TL_DCA']['tl_page']['palettes'][$root]);

/**
 * Add fields to tl_page
 */
$arrFields = [
        'bar_disabled' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['bar_disabled'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'long clr',
            ],
            'sql' => "tinyint(1) NULL",
        ],
        'imprint' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['imprint'],
            'exclude'   => true,
            'inputType' => 'pageTree',
            'eval' => [
                'tl_class'  =>  'w50'
            ],
            'sql' => "varchar(255) NULL",
        ],
        'privacyPolicy' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['privacyPolicy'],
            'exclude'   => true,
            'inputType' => 'pageTree',
            'eval' => [
                'tl_class'  =>  'w50'
            ],
            'sql' => "varchar(255) NULL",
        ]
];

$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_page']['fields'], $arrFields);
