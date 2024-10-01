<?php

$GLOBALS['TL_HOOKS']['getPageLayout'][] = [Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener::class, 'onGetPageLayoutListener'];

$GLOBALS['TL_HOOKS']['getContentElement'][] = [Netzhirsch\CookieOptInBundle\EventListener\ContentElementListener::class, 'onContentElement'];

$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = [Netzhirsch\CookieOptInBundle\EventListener\ReplaceInsertTag::class, 'onReplaceInsertTagsListener'];


/**
 * Add back end modules
 */
$GLOBALS['BE_MOD']['accounts']['consentDirectory']['tables'][] = 'tl_consentDirectory';
/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['cookieOptIn'] = [
    'cookieOptInBar' => 'Netzhirsch\CookieOptInBundle\Resources\contao\modules\ModuleCookieOptInBar',
    'cookieOptInRevoke' => 'Netzhirsch\CookieOptInBundle\Resources\contao\modules\ModuleCookieOptInRevoke',
];
