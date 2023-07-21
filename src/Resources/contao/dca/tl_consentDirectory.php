<?php

use Contao\DC_Table;
use Netzhirsch\CookieOptInBundle\Controller\ConsentController;

$deleteConfirm = 'Soll das Element ID %s wirklich gelöscht werden?';
if (isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']))
    $deleteConfirm = $GLOBALS['TL_LANG']['MSC']['deleteConfirm'];
$GLOBALS['TL_DCA']['tl_consentDirectory'] = [
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
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
				'id',
			    'pid',
			    'date',
				'domain',
				'url',
				'ip',
				'cookieToolsName',
				'cookieToolsTechnicalName',
			],
			'headerFields' => [
				'date',
				'domain',
				'ip',
				'cookieToolsName',
				'cookieToolsTechnicalName',
			],
			'format' => "%s, %s, %s, %s, %s",
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
				'attributes'          => 'onclick="if(!confirm(\'' . $deleteConfirm . '\'))return false;Backend.getScrollOffset()"'
			),
		)
	),
	'palettes' => array
	(
	'default'                     => 'id,pid,date,domain,url,ip,cookieToolsName,cookieToolsTechnicalName,'
	),
	'fields' => [
		'date' => array
		(
			'exclude'                 => true,
			'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['date'],
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'domain' => [
			'exclude'                 => true,
			'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['domain'],
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		],
		'url' => [
			'exclude'                 => true,
			'label' => &$GLOBALS['TL_LANG']['tl_consentDirectory']['url'],
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		],
		'id' => array
		(
            'exclude'                 => true,
            'label'                   => 'ID',
            'inputType'               => 'text',
            'search'                  => true,
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
		    'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
            'exclude'                 => true,
            'label'                   => 'PID',
            'inputType'               => 'text',
            'search'                  => true,
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
		    'sql'                     => "int(10) unsigned NULL"
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
