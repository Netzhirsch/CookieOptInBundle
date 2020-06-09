<?php

/*************** Revoke Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['revokeButton'] = ['Button-Text','Please enter the text of the revoke button.'];

$GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'] = 'Change cookie decision';

$GLOBALS['TL_LANG']['tl_module']['templateRevoke'] =
	['Template' , 'The template name must begin with mod_cookie_opt_in_revoke.'];

/*************** Ende Revoke Modul ***************/

/*************** Bar Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['questionHint'] = ['Information in the cookie bar'];

$GLOBALS['TL_LANG']['tl_module']['saveButton'] = ['Save button','Please enter the label of the save button.'];

$GLOBALS['TL_LANG']['tl_module']['saveButtonDefault'] = 'Save';

$GLOBALS['TL_LANG']['tl_module']['saveAllButtonDefault'] = 'Save all';

$GLOBALS['TL_LANG']['tl_module']['saveAllButton'] = ['Accept all button','Please enter the label of the button that accepts all cookies. If there are only essential cookies, this button is hidden.'];

$GLOBALS['TL_LANG']['tl_module']['highlightSaveAllButton'] = ['Accept all button highlighting'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBar'] = ['Heading'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBarDefault'] = 'a:2:{s:5:"value";s:16:"Privacy settings";s:4:"unit";s:2:"h2";}';

$GLOBALS['TL_LANG']['tl_module']['questionHintDefault'] = 'We use cookies on our website. Some of them are essential, while others help us improve this website and your experience.';

$GLOBALS['TL_LANG']['tl_module']['infoHint'] = ['information','Please enter the information text by clicking on the "info" button.'];

$GLOBALS['TL_LANG']['tl_module']['infoHintDefault'] = 'In this overview you can select and deselect individual cookies of a category or entire categories. You will also receive more information about the cookies available.';

$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'] =
	[
		'Reset all cookies set','Activate in the event of a change relevant to data protection. The cookie bar is displayed again each time you visit.'
	];

$GLOBALS['TL_LANG']['tl_module']['cookieVersion'] = [''];

$GLOBALS['TL_LANG']['tl_module']['cookieGroups'] = ['Cookie groups','The key is used for internal processing and the value is displayed in the frontend.'];

$GLOBALS['TL_LANG']['tl_module']['cookieGroupsDefault'] = 'a:3:{i:0;a:2:{s:3:"key";s:1:"1";s:5:"value";s:9:"essential";}i:1;a:2:{s:3:"key";s:1:"2";s:5:"value";s:8:"analysis";}i:2;a:2:{s:3:"key";s:1:"3";s:5:"value";s:14:"external media";}}'
;

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['essential'] = 'Essential';
$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['analysis'] = 'Analysis';
$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['external_media'] = 'External media';

/*************** Fieldpalette Tools ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieTools'] = ['Tools','<a href="https://www.netzhirsch.de/contao-cookie-opt-in-bundle.html#ccoi-examples" target="_blank">Here you can find help.</a>'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'] = ['Cookie name','e.g. facebook pixel'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'] = ['Analyse template'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'] =
	['Tracking ID','z.B. UA-123456789-1 for google analytics'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'] =
    ['Tracking Server URL ','Only for e.g. https://netzhirsch.matomo.cloud/'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelectOptions'] = [
    'googleAnalytics' => 'Google Analytics',
    'facebookPixel' => 'Facebook Pixel',
    'matomo' => 'Matomo',
    'youtube' => 'YouTube',
    'vimeo' => 'Vimeo',
    'googleMaps' => 'iFrame [Google Maps]',
    'iframe' => 'iFrame [Other]',
    '-' => '-'
];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'] = ['Providers'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'] =
	['Data protection URL','z.B. https://policies.google.com/privacy'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'] = ['Use','Please indicate the purpose of the cookie.'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'] =
	['Technical name','e.g. _gat,_gtag_UA_123456789_1 Comma separated. Important to delete cookies'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'] = ['Cookie group'];

$GLOBALS['TL_LANG']['tl_module']['netzhirschCookieFieldModel']['cookieToolsUse'] = 'Used to determine which cookie was accepted or rejected.';

$GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'] = 'Serves to protect the website from cross-site request forgery
attacks. After closing the browser, the cookie is deleted again.';

$GLOBALS['TL_LANG']['tl_module']['phpSessionID']['cookieToolsUse'] = 'PHP cookie (programming language), PHP data identifier.
Contains only a reference to the current session. There is no information in the user\'s browser
saved and this cookie can only be used by the current website. This cookie is used
all used in forms to increase usability. Data entered in forms will be
e.g. B. briefly saved when there is an input error by the user and the user receives an error message
receives. Otherwise all data would have to be entered again';

/*************** End Fieldpalette Tools ***************/

/*************** Fieldpalette otherScripts ***************/
$GLOBALS['TL_LANG']['tl_module']['otherScripts'] = ['Other scripts'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsCode'] = ['JavaScript code','With script tag. jQuery can be used via $.'];

/*************** End Fieldpalette otherScripts ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieExpiredTime'] = ['Expiration in days','Please specify the duration of the cookie. The cookie bar is then displayed again.'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolExpiredTime'] = ['Expiration in days','Please specify the duration of the cookie.'];


$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'] = ['Data protection'];

$GLOBALS['TL_LANG']['tl_module']['imprint'] = ['Imprint'];

$GLOBALS['TL_LANG']['tl_module']['excludePages'] = ['Do not display cookie bar on the following pages.'];

$GLOBALS['TL_LANG']['tl_module']['respectToNotTrack'] = ['"Do Not Track" respect browser settings \',\' If this browser setting is set, the cookie bar will not be shown.'];

$GLOBALS['TL_LANG']['tl_module']['blockSite'] = ['Disable use of the site','Elements of the page can only be clicked on after cookies have been accepted or rejected.'];

$GLOBALS['TL_LANG']['tl_module']['zIndex'] = ['z-index settings','Increase this value if the cookie banner is covered by other elements.'];

$GLOBALS['TL_LANG']['tl_module']['defaultCss'] = ['Load standard CSS','Load the CSS file of the Cookie Opt In Bar module.'];

$GLOBALS['TL_LANG']['tl_module']['position'] = ['Position'];

$GLOBALS['TL_LANG']['tl_module']['leftTop'] = 'left top';
$GLOBALS['TL_LANG']['tl_module']['leftCenter'] = 'left center';
$GLOBALS['TL_LANG']['tl_module']['leftBottom'] = 'left bottom';

$GLOBALS['TL_LANG']['tl_module']['centerTop'] = 'center top';
$GLOBALS['TL_LANG']['tl_module']['centerCenter'] = 'center center';
$GLOBALS['TL_LANG']['tl_module']['centerBottom'] = 'center bottom';

$GLOBALS['TL_LANG']['tl_module']['rightTop'] = 'right top';
$GLOBALS['TL_LANG']['tl_module']['rightCenter'] = 'right center';
$GLOBALS['TL_LANG']['tl_module']['rightBottom'] = 'right bottom';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle'] = ['Template style'];

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['dark'] = 'dark';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['light'] = 'light';

$GLOBALS['TL_LANG']['tl_module']['maxWidth'] = ['Width' , 'in pixels'];

$GLOBALS['TL_LANG']['tl_module']['templateBar'] =
	['Template' , 'The template name must begin with mod_cookie_opt_in_bar.'];

$GLOBALS['TL_LANG']['tl_module']['animation'] = ['Animation', 'By clicking on the buttons in the frontend.'];

$GLOBALS['TL_LANG']['tl_module']['go-up'] = 'go-up';
$GLOBALS['TL_LANG']['tl_module']['shrink'] = 'shrink';
$GLOBALS['TL_LANG']['tl_module']['shrink-and-rotate'] = 'shrink and rotate';
$GLOBALS['TL_LANG']['tl_module']['hinge'] = 'hinge';

$GLOBALS['TL_LANG']['tl_module']['ipFormatSave'] = ['IP storage','Select the type of logging of the IP addresses.'];

$GLOBALS['TL_LANG']['tl_module']['uncut'] = 'uncut';
$GLOBALS['TL_LANG']['tl_module']['pseudo'] = 'pseudonymisiert';
$GLOBALS['TL_LANG']['tl_module']['anon'] = 'anonymized';
