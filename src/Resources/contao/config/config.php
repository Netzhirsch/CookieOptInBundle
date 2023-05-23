<?php


$GLOBALS['TL_HOOKS']['getSystemMessages'][] = [Netzhirsch\CookieOptInBundle\EventListener\GetSystemMessagesListener::class, 'onGetSystemMessages'];

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
$GLOBALS['FE_MOD']['cookieOptIn'] = [
    'cookieOptInBar' => 'Netzhirsch\CookieOptInBundle\ModuleCookieOptInBar',
    'cookieOptInRevoke' => 'Netzhirsch\CookieOptInBundle\ModuleCookieOptInRevoke'
];
