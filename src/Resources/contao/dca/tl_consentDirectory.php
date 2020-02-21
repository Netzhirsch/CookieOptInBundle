<?php

use Netzhirsch\CookieOptInBundle\Controller\ConsentController;

$GLOBALS['TL_DCA']['tl_consentDirectory'] = [
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'notCopyable' => true,
		'notEditable' => true,
		'notCreatable' => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
			)
		)
	),
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 2,
			'fields'                  => array('date'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => [
				'ip',
				'date',
				'cookieToolsName',
				'cookieToolsTechnicalName',
			],
			'headerFields' => [
				'ip',
				'date',
				'cookieToolsName',
				'cookieToolsTechnicalName',
			],
			'format' => "%s, %s, %s, %s",
			'showColumns'             => true,
		),
		'global_operations' => [
			'download' => [
				'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['download'],
				'href' => 'act=downloadConsent',
				'button_callback' => [ConsentController::class, 'renderLink'],
			],
		],
		'operations' => array
		(
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
		)
	),
	'palettes' => array
	(
	'default'                     => 'ip,cookieToolsName,cookieToolsTechnicalName,date'
	),
	'fields' => [
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'date' => array
		(
			'exclude'                 => true,
			'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['date'],
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'ip' => array
		(
			'exclude'                 => true,
			'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['ip'],
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'cookieToolsName' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_consentDirectory']['cookieToolsName'],
			'exclude'   => true,
			'inputType' => 'text',
			'sql' => "text NULL default '' ",
		],
		'cookieToolsTechnicalName' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_consentDirectory']['cookieToolsTechnicalName'],
			'exclude'   => true,
			'inputType' => 'text',
			'sql' => "text NOT NULL default '' ",
		],
	],
];
