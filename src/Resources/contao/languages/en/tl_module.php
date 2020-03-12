<?php

/*************** Revoke Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['revokeButton'] = ['Button-Text','Please enter the text of the Revoke button.'];

$GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'] = 'Change cookie decision';

$GLOBALS['TL_LANG']['tl_module']['templateRevoke'] =
	['Template' , 'The template name must begin with mod_cookie_opt_in_revoke.'];

/*************** Ende Revoke Modul ***************/

/*************** Bar Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['questionHint'] = ['Information in the cookie bar'];

$GLOBALS['TL_LANG']['tl_module']['saveButton'] = ['Save button','Please enter the label of the save button.'];

$GLOBALS['TL_LANG']['tl_module']['saveButtonDefault'] = 'Save';

$GLOBALS['TL_LANG']['tl_module']['saveAllButtonDefault'] = 'Save all';

$GLOBALS['TL_LANG']['tl_module']['saveAllButton'] = ['Accept All button','Please enter the label of the button that accepts all cookies. If there are only essential cookies, this button is hidden.'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBar'] = ['Heading'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBarDefault'] = 'a:2:{s:5:"value";s:16:"Privacy settings";s:4:"unit";s:2:"h2";}';

$GLOBALS['TL_LANG']['tl_module']['questionHintDefault'] = 'We use cookies on our website. Some of them are essential, while others help us improve this website and your experience.';

$GLOBALS['TL_LANG']['tl_module']['infoHint'] = ['information','Please enter the information text by clicking on the "Info" button.'];

$GLOBALS['TL_LANG']['tl_module']['infoHintDefault'] = 'In this overview you can select and deselect individual cookies of a category or entire categories. You will also receive more information about the cookies available.';

$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'] =
	[
		'Reset all cookies set','Activate in the event of a change relevant to data protection. The cookie bar is displayed again each time you visit.'
];

$GLOBALS['TL_LANG']['tl_module']['cookieVersion'] = [''];

$GLOBALS['TL_LANG']['tl_module']['cookieGroups'] = ['Cookie groups'];

$GLOBALS['TL_LANG']['tl_module']['cookieGroupsDefault'] = [
	'essential',
	'analysis',
];
$GLOBALS['TL_LANG']['tl_module']['essential'] = 'Essential';
/*************** Fieldpalette Tools ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieTools'] = ['Tools'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'] = ['Cookie name','e.g. facebook pixel'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'] = ['Analyse template'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'] =
	['Tracking Id','z.B. UA-123456789-1 for google analytics'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'] =
	['Tracking server Url ','e.g. my-matomo-server.com for matomo'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'] = ['Providers'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'] =
	['Data protection url','z.B. https://policies.google.com/privacy'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'] = ['Use','Please indicate the purpose of the cookie.'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'] =
	['Technical name','e.g. _gat,_gtag_UA_123456789_1 Comma separated. Important to delete cookies'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'] = ['Cookie group'];

$GLOBALS['TL_LANG']['tl_module']['netzhirschCookieFieldModel']['cookieToolsUse'] = 'Used to determine which cookie was accepted or rejected.';

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup']['essential'] = 'essential';

$GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'] = 'Serves to protect the website from cross-site request forgery
Attacks. After closing the browser, the cookie is deleted again';

$GLOBALS['TL_LANG']['tl_module']['phpSessionId']['cookieToolsUse'] = 'PHP cookie (programming language), PHP data identifier.
Contains only a reference to the current session. There is no information in the user\'s browser
saved and this cookie can only be used by the current website. This cookie is used
all used in forms to increase usability. Data entered in forms will be
e.g. B. briefly saved when there is an input error by the user and the user receives an error message
receives. Otherwise all data would have to be entered again';

/*************** End Fieldpalette Tools ***************/

/*************** Fieldpalette otherScripts ***************/
$GLOBALS['TL_LANG']['tl_module']['otherScripts'] = ['Other scripts'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsCode'] = ['JavaScript code','With script tag. jQuery can be used via $'];

/*************** End Fieldpalette otherScripts ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieExpiredTime'] = ['Expiration in months','Please specify the duration of the cookie. The cookie bar is then displayed again.'];

$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'] = ['Data protection'];

$GLOBALS['TL_LANG']['tl_module']['impress'] = ['Impress'];

$GLOBALS['TL_LANG']['tl_module']['excludePages'] = ['Do not display cookie bar on the following pages'];

$GLOBALS['TL_LANG']['tl_module']['respectToNotTrack'] = ['"Do Not Track" respect browser settings \',\' If this browser setting is set, the cookie bar will not be shown.'];

$GLOBALS['TL_LANG']['tl_module']['defaultCss'] = ['Load standard CSS','Load the CSS file of the Cookie Opt In Bar module.'];

$GLOBALS['TL_LANG']['tl_module']['position'] = ['Position'];

$GLOBALS['TL_LANG']['tl_module']['topLeft'] = 'left top';
$GLOBALS['TL_LANG']['tl_module']['topCenter'] = 'left center';
$GLOBALS['TL_LANG']['tl_module']['topRight'] = 'left bottom';

$GLOBALS['TL_LANG']['tl_module']['centerLeft'] = 'center top';
$GLOBALS['TL_LANG']['tl_module']['centerCenter'] = 'center center';
$GLOBALS['TL_LANG']['tl_module']['centerRight'] = 'center bottom';

$GLOBALS['TL_LANG']['tl_module']['bottomLeft'] = 'right top';
$GLOBALS['TL_LANG']['tl_module']['bottomCenter'] = 'right center';
$GLOBALS['TL_LANG']['tl_module']['bottomRight'] = 'right bottom';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle'] = ['Template style'];

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['dark'] = 'dark';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['light'] = 'light';

$GLOBALS['TL_LANG']['tl_module']['maxWidth'] = ['Width' , 'in pixels'];

$GLOBALS['TL_LANG']['tl_module']['templateBar'] =
	['Template' , 'The template name must begin with mod_cookie_opt_in_bar.'];

$GLOBALS['TL_LANG']['tl_module']['animation'] = ['Animation', 'By clicking on the buttons in the frontend'];

$GLOBALS['TL_LANG']['tl_module']['shrink'] = 'shrink';

$GLOBALS['TL_LANG']['tl_module']['go-up'] = 'go-up';