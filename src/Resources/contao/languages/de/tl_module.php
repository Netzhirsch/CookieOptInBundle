<?php

/*************** Revoke Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['revokeButton'] = ['Button-Text','Bitte geben Sie den Text des Revoke-Buttons ein.'];

$GLOBALS['TL_LANG']['tl_module']['revokeButtonDefault'] = 'Cookie-Entscheidung ändern';

$GLOBALS['TL_LANG']['tl_module']['templateRevoke'] =
	['Template' , 'Der Template-Name muss mit mod_cookie_opt_in_revoke beginnen.'];

/*************** Ende Revoke Modul ***************/

/*************** Bar Modul ***************/

$GLOBALS['TL_LANG']['tl_module']['questionHint'] = ['Hinweistext in der Cookie Bar'];

$GLOBALS['TL_LANG']['tl_module']['saveButton'] = ['Speichern-Button','Bitte geben Sie die Beschriftung des Speichern-Buttons ein.'];

$GLOBALS['TL_LANG']['tl_module']['saveButtonDefault'] = 'Speichern';

$GLOBALS['TL_LANG']['tl_module']['saveAllButtonDefault'] = 'Alle annehmen';

$GLOBALS['TL_LANG']['tl_module']['saveAllButton'] = ['Alle Annehmen-Button','Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookies annimmt. Falls es nur essenzielle Cookies gibt, wird dieser Button ausgeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBar'] = ['Überschrift'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBarDefault'] = 'a:2:{s:5:"value";s:24:"Datenschutzeinstellungen";s:4:"unit";s:2:"h2";}';

$GLOBALS['TL_LANG']['tl_module']['questionHintDefault'] = 'Wir nutzen Cookies auf unserer Website. Einige von ihnen sind essenziell, während andere uns helfen, diese Website und Ihre Erfahrung zu verbessern.';

$GLOBALS['TL_LANG']['tl_module']['infoHint'] = ['Informationen','Bitte geben Sie den Informationstext beim Klick auf den Button "Infos" ein.'];

$GLOBALS['TL_LANG']['tl_module']['infoHintDefault'] = 'In dieser Übersicht können Sie, einzelne Cookies einer Kategorie oder ganze Kategorien an- und abwählen. Ausserdem erhalten Sie weitere Informationen zu den verfügbaren Cookies.';

$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'] =
	[
		'Alle gesetzten Cookies zurücksetzen','Bei datenschutzrelevanten Änderung aktivieren. Jeder Besuch bekommt anschließend erneut die Cookie Bar angezeigt.'
];

$GLOBALS['TL_LANG']['tl_module']['cookieVersion'] = [''];

$GLOBALS['TL_LANG']['tl_module']['cookieGroups'] = ['Cookie Gruppen'];

$GLOBALS['TL_LANG']['tl_module']['cookieGroupsDefault'] = [
	'Essenziell',
	'Analyse',
];
/*************** Fieldpalette Tools ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieTools'] = ['Tools'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'] = ['Cookie Name','z.B. Facebook Pixel'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'] = ['Analyse Template'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'] =
	['Tracking Id','z.B. UA-123456789-1 für Google Analytics'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'] =
	['Tracking Server Url ','z.B. mein-matomo-server.de für Matomo'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'] = ['Anbieter'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'] =
	['Datenschutzerklärung Url','z.B. https://policies.google.com/privacy'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'] = ['Zweck','Bitte geben Sie den Zweck des Cookies an.'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'] =
	['Technischer Name','z.B. _gat,_gtag_UA_123456789_1 Komma getrennt. Wichtig zum löschen der Cookies'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'] = ['Cookie Gruppe'];

$GLOBALS['TL_LANG']['tl_module']['essential'] = 'Essenziell';

/*************** End Fieldpalette Tools ***************/

/*************** Fieldpalette otherScripts ***************/
$GLOBALS['TL_LANG']['tl_module']['otherScripts'] = ['Andere Skripte'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsCode'] = ['JavaScript Code','Mit script-Tag. jQuery kann über $ genutzt werden'];

/*************** End Fieldpalette otherScripts ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieExpiredTime'] = ['Ablauf in Monaten','Bitte geben Sie die Laufzeit des Cookies an. Danach wird die Cookie Bar erneut eingeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'] = ['Datenschutzerklärung'];

$GLOBALS['TL_LANG']['tl_module']['impress'] = ['Impressum'];

$GLOBALS['TL_LANG']['tl_module']['excludePages'] = ['Cookie Bar auf folgenden Seiten nicht anzeigen'];

$GLOBALS['TL_LANG']['tl_module']['respectToNotTrack'] = ['"Do Not Track" Browser-Einstellung respektieren','Wenn diese Browser-Einstellung gesetzt ist, wird die Cookie Bar nicht eingeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['defaultCss'] = ['Standard CSS laden','Die CSS-Datei des Cookie Opt In Bar-Moduls laden.'];

$GLOBALS['TL_LANG']['tl_module']['position'] = ['Position'];

$GLOBALS['TL_LANG']['tl_module']['topLeft'] = 'Oben links';
$GLOBALS['TL_LANG']['tl_module']['topCenter'] = 'Oben mitte';
$GLOBALS['TL_LANG']['tl_module']['topRight'] = 'Oben rechts';

$GLOBALS['TL_LANG']['tl_module']['centerLeft'] = 'Mitte links';
$GLOBALS['TL_LANG']['tl_module']['centerCenter'] = 'Mitte mitte';
$GLOBALS['TL_LANG']['tl_module']['centerRight'] = 'Mitte rechts';

$GLOBALS['TL_LANG']['tl_module']['bottomLeft'] = 'Unten links';
$GLOBALS['TL_LANG']['tl_module']['bottomCenter'] = 'Unten mitte';
$GLOBALS['TL_LANG']['tl_module']['bottomRight'] = 'Unten rechts';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle'] = ['Template Style'];

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['dark'] = 'dunkel';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['light'] = 'hell';

$GLOBALS['TL_LANG']['tl_module']['maxWidth'] = ['Breite' , 'in Pixel'];

$GLOBALS['TL_LANG']['tl_module']['templateBar'] = ['Template' , 'Der Template-Name muss mit mod_cookie_opt_in_bar beginnen.'];

$GLOBALS['TL_LANG']['tl_module']['animation'] = ['Animation', 'Bei klick auf die Buttons im Frontend'];

$GLOBALS['TL_LANG']['tl_module']['shrink'] = 'schrumpfen';

$GLOBALS['TL_LANG']['tl_module']['go-up'] = 'auffahren';