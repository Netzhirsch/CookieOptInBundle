<?php

$GLOBALS['TL_HOOKS']['getPageLayout'][] = [Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener::class, 'onGetPageLayoutListener'];

$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = [Netzhirsch\CookieOptInBundle\EventListener\ParseFrontendTemplateListener::class, 'onParseFrontendTemplate'];

$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = [Netzhirsch\CookieOptInBundle\EventListener\ReplaceInsertTag::class, 'onReplaceInsertTagsListener'];


/**
 * Add back end modules
 */
$GLOBALS['BE_MOD']['accounts']['consentDirectory']['tables'][] = 'tl_consentDirectory';
/**
 * Front end modules
 */
array_insert($GLOBALS['FE_MOD'], 4, [
	'cookieOptIn' => [
		'cookieOptInBar' => 'Netzhirsch\CookieOptInBundle\ModuleCookieOptInBar',
		'cookieOptInRevoke' => 'Netzhirsch\CookieOptInBundle\ModuleCookieOptInRevoke'
	],
]);