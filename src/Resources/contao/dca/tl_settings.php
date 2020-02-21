<?php

$dc = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palettes
 */
$dc['palettes']['default'] = str_replace('{chmod_legend', '{ncoi_license_legend},ncoi_license_key,ncoi_license_protected;{chmod_legend', $dc['palettes']['default']);

   
/**
 * Fields
 */
$arrFields = [
    'ncoi_license_key' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_settings']['ncoi_license_key'],
        'exclude'   => true,
        'inputType' => 'text',
        'eval'      => ['maxlength'=>64, 'rgxp'=>'alnum','tl_class' => 'w50'],
        'sql'       => "varchar(64) NOT NULL default ''",
    ]
];

$dc['fields'] = array_merge($dc['fields'], $arrFields);