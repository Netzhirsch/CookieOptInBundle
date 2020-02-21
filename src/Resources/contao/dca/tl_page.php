<?php

/**
 * Extend default palette
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace('{global_legend', '{ncoi_license_legend},ncoi_license_key,ncoi_license_protected;{global_legend', $GLOBALS['TL_DCA']['tl_page']['palettes']['root']);


/**
 * Add fields to tl_page
 */
$arrFields = [
		'ncoi_license_key' => [
				'label'     => &$GLOBALS['TL_LANG']['tl_page']['ncoi_license_key'],
				'exclude'   => true,
				'inputType' => 'text',
				'eval'      => ['maxlength'=>64, 'rgxp'=>'alnum','tl_class' => 'w50'],
				'sql'       => "varchar(64) NOT NULL default ''",
		]
];

$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_page']['fields'], $arrFields);