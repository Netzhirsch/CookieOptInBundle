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

$GLOBALS['TL_LANG']['tl_module']['saveAllButton'] = ['Alle annehmen-Button','Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookies annimmt. Falls es nur essenzielle Cookies gibt, wird dieser Button ausgeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['rejectAllButtonDefault'] = 'Alle ablehnen';

$GLOBALS['TL_LANG']['tl_module']['rejectAllButton'] = ['Alle ablehnen-Button','Bitte geben Sie die Beschriftung des Buttons ein, der alle nicht essenziell Cookies ablehnt. Falls es nur essenzielle Cookies gibt, wird dieser Button ausgeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['highlightSaveAllButton'] = ['Alle annehmen-Button hervorheben'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBar'] = ['Überschrift'];

$GLOBALS['TL_LANG']['tl_module']['headlineCookieOptInBarDefault'] = 'a:2:{s:5:"value";s:24:"Datenschutzeinstellungen";s:4:"unit";s:2:"h2";}';

$GLOBALS['TL_LANG']['tl_module']['questionHintDefault'] = 'Wir nutzen Cookies auf unserer Website. Einige von ihnen sind essenziell, während andere uns helfen, diese Website und Ihre Erfahrung zu verbessern.';

$GLOBALS['TL_LANG']['tl_module']['infoHint'] = ['Informationen','Bitte geben Sie den Informationstext ein, der beim Blick auf den Info-anzeigen-Button erscheinen soll.'];

$GLOBALS['TL_LANG']['tl_module']['infoHintDefault'] = 'In dieser Übersicht können Sie einzelne Cookies einer Kategorie oder ganze Kategorien an- und abwählen. Außerdem erhalten Sie weitere Informationen zu den verfügbaren Cookies.';

$GLOBALS['TL_LANG']['tl_module']['infoButtonShow'] = ['Info-anzeigen-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen anzeigt.'];

$GLOBALS['TL_LANG']['tl_module']['infoButtonShowDefault'] = 'Info';

$GLOBALS['TL_LANG']['tl_module']['infoButtonHide'] = ['Info-ausblenden-Button', 'Bitte geben Sie die Beschriftung des Buttons ein, der alle Cookie Informationen ausblendet.'];

$GLOBALS['TL_LANG']['tl_module']['infoButtonHideDefault'] = 'Info ausblenden';

$GLOBALS['TL_LANG']['tl_module']['isNewCookieVersion'] = ['Alle gesetzten Cookies zurücksetzen','Sie sollten diese Option bei datenschutzrelevanten Änderungen aktivieren. Jeder Besucher bekommt anschließend erneut die Cookie Bar angezeigt.'];

$GLOBALS['TL_LANG']['tl_module']['cookieVersion'] = [''];

$GLOBALS['TL_LANG']['tl_module']['cookieVersionDefault'] = '1';

$GLOBALS['TL_LANG']['tl_module']['cookieGroups'] = ['Cookie Gruppen','Der Schlüssel dient der internen Verarbeitung, der Wert wird im Frontend angezeigt.'];

$GLOBALS['TL_LANG']['tl_module']['cookieGroupsDefault'] =
	'a:3:{i:0;a:2:{s:3:"key";s:1:"1";s:5:"value";s:10:"Essenziell";}i:1;a:2:{s:3:"key";s:1:"2";s:5:"value";s:7:"Analyse";}i:2;a:2:{s:3:"key";s:1:"3";s:5:"value";s:14:"Externe Medien";}}'
;

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['essential'] = 'Essenziell';
$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['analysis'] = 'Analyse';
$GLOBALS['TL_LANG']['tl_module']['cookieToolGroupNames']['external_media'] = 'Externe Medien';

/*************** Fieldpalette Tools ***************/

$GLOBALS['TL_LANG']['tl_module']['cookieTools'] = ['Tools','<a href="https://www.netzhirsch.de/contao-cookie-opt-in-bundle.html#ccoi-examples" target="_blank">Klicken Sie hier für eine Hilfestellung.</a>'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsName'] = ['Cookie Name','z.B. Facebook Pixel'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelect'] = ['Type'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsSelectOptions'] = [
    'googleAnalytics' => 'Google Analytics',
    'googleTagManager' => 'Google Tag Manager',
    'facebookPixel' => 'Facebook Pixel',
    'matomo' => 'Matomo',
    'youtube' => 'YouTube',
    'vimeo' => 'Vimeo',
    'googleMaps' => 'iFrame [Google Maps]',
    'iframe' => 'iFrame [Andere]',
    'script' => 'HTML-Element [script]',
    '-' => '-'
];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingId'] =
	['Tracking ID','z.B. UA-123456789-1 für Google Analytics'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsTrackingServerUrl'] =
	['Tracking Server URL ','Nur für Matomo z.B. https://netzhirsch.matomo.cloud/'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsProvider'] = ['Anbieter'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsPrivacyPolicyUrl'] =
	['Datenschutzerklärung URL','z.B. https://policies.google.com/privacy'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsUse'] = ['Zweck','Bitte geben Sie den Zweck des Cookies an.'];

$GLOBALS['TL_LANG']['tl_module']['netzhirschCookieFieldModel']['cookieToolsUse'] = 'Wird verwendet, um festzustellen, welches Cookie akzeptiert oder abgelehnt wurde.';

$GLOBALS['TL_LANG']['tl_module']['contaoCsrfToken']['cookieToolsUse'] = 'Dient zum Schutz der Website vor Fälschungen von standortübergreifenden Anfragen. Nach dem Schließen des Browsers wird das Cookie wieder gelöscht';

$GLOBALS['TL_LANG']['tl_module']['contaoCsrfHttpsToken']['cookieToolsUse'] = 'Dient zum Schutz der verschlüsselten Website (HTTPS) vor Fälschungen von standortübergreifenden Anfragen. Nach dem Schließen des Browsers wird das Cookie wieder gelöscht';

$GLOBALS['TL_LANG']['tl_module']['phpSessionID']['cookieToolsUse'] = 'Cookie von PHP (Programmiersprache), PHP Daten-Identifikator. Enthält nur einen Verweis auf die aktuelle Sitzung. Im Browser des Nutzers werden keine Informationen gespeichert und dieses Cookie kann nur von der aktuellen Website genutzt werden. Dieses Cookie wird vor allem in Formularen benutzt, um die Benutzerfreundlichkeit zu erhöhen. In Formulare eingegebene Daten werden z. B. kurzzeitig gespeichert, wenn ein Eingabefehler durch den Nutzer vorliegt und dieser eine Fehlermeldung erhält. Ansonsten müssten alle Daten erneut eingegeben werden.';


$GLOBALS['TL_LANG']['tl_module']['cookieToolsTechnicalName'] =
	['Technischer Name','z.B. _gat,_gtag_UA_123456789_1 Komma getrennt. Wichtig zum Löschen der Cookies'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolGroup'] = ['Cookie Gruppe'];

$GLOBALS['TL_LANG']['tl_module']['essential'] = 'Essenziell';

$GLOBALS['TL_LANG']['tl_module']['FE_USER_AUTH']['cookieToolsUse']
    = 'Speichert Informationen eines Besuchers, sobald er sich im Frontend einloggt.';

$GLOBALS['TL_LANG']['tl_module']['i_frame_blocked_urls'] =
    ['Blockierte URL','Bitte geben Sie hier die URL des IFrames ein. Sollten Sie keine angeben wird die entsprechende URL des IFrame Typen verwendet. Mehrer URLS bitte mit Komma getrennt.'];

$GLOBALS['TL_LANG']['tl_module']['i_frame_blocked_text'] =
    ['Blockierter Text','Bitte geben Sie hier den Text ein, der für dieses blockiert IFrame verwendet werden soll. Sollten Sie keinen angeben wird der entsprechende Text des IFrame Typen verwendet. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'];

/*************** End Fieldpalette Tools ***************/

/*************** Fieldpalette otherScripts ***************/
$GLOBALS['TL_LANG']['tl_module']['otherScripts'] = ['Andere Skripte'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolsCode'] = ['JavaScript Code','Mit script-Tag. jQuery kann über $ genutzt werden.'];

$GLOBALS['TL_LANG']['tl_module']['cookieToolExpiredTime'] = ['Ablauf in Tagen','Bitte geben Sie die Laufzeit des Cookies an.'];

/*************** End Fieldpalette otherScripts ***************/
$GLOBALS['TL_LANG']['tl_module']['i_frame'] = [
    'video' => ['Blockierte Videos','Bitte geben Sie hier den Text ein, der für blockiert Videos verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
    'maps' => ['Blockierte Karten','Bitte geben Sie hier den Text ein, der für blockiert Karten verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
    'i_frame' => ['Blockierte iFrames','Bitte geben Sie hier den Text ein, der für blockiert iFrames verwendet werden soll. {{provider}} wird, mit Datenschutzlink, durch den eingetragenen Anbieter des Tools ersetzt.'],
    'always_load'
        => [
            'Immer-laden-Checkbock',
            'Bitte geben Sie die Beschriftung der Checkbox ein, die die Entscheidung für diesen Typen ändert.'
    ],
    'load' => [
        'Laden-Button',
        'Bitte geben Sie die Beschriftung des Buttons ein, der alle blockierten Inhalte eines Typs lädt.'
    ]
];

$GLOBALS['TL_LANG']['tl_module']['i_frame_videoDefault']
    = 'Durch das Laden dieses Videos, geben Sie Ihre Zustimmung zu den Datenschutzbedingungen von {{provider}}.';
$GLOBALS['TL_LANG']['tl_module']['i_frame_mapsDefault']
    = 'Durch das Laden dieser Karte, geben Sie Ihre Zustimmung zu den Datenschutzbedingungen von {{provider}}.';
$GLOBALS['TL_LANG']['tl_module']['i_frame_i_frameDefault']
    = 'Durch das Laden dieses iFrames, geben Sie Ihre Zustimmung zu den Datenschutzbedingungen von {{provider}}.';
$GLOBALS['TL_LANG']['tl_module']['i_frame_always_loadDefault']
    = 'immer laden';
$GLOBALS['TL_LANG']['tl_module']['i_frame_loadDefault']
    = 'laden';

$GLOBALS['TL_LANG']['tl_module']['expireTime'] = ['Ablauf in Tagen'
    ,'Bitte geben Sie die Laufzeit der Einwilligung an. Danach wird die Cookie Bar erneut eingeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['expireTimeDefault'] = '30';

$GLOBALS['TL_LANG']['tl_module']['privacyPolicy'] = ['Datenschutzerklärung'];

$GLOBALS['TL_LANG']['tl_module']['imprint'] = ['Impressum'];

$GLOBALS['TL_LANG']['tl_module']['excludePages'] = ['Cookie Bar auf folgenden Seiten nicht anzeigen.'];

$GLOBALS['TL_LANG']['tl_module']['respectDoNotTrack'] = ['"Do Not Track" Browser-Einstellung respektieren','Wenn diese Browser-Einstellung gesetzt ist, wird die Cookie Bar nicht eingeblendet.'];

$GLOBALS['TL_LANG']['tl_module']['optOut'] = ['Opt Out','Cookies setzen und bei Ablehnung löschen, nur in einigen Länder erlaubt. Nicht in Deutschland.'];

$GLOBALS['TL_LANG']['tl_module']['blockSite'] = ['Nutzung der Seite unterbinden','Elemente der Seite können erst angeklickt werden, wenn Cookies aktzeptiert oder abgelehnt wurden.'];

$GLOBALS['TL_LANG']['tl_module']['zIndex'] = ['z-index-Einstellung','Erhöhen Sie diesen Wert, wenn das Cookie-Banner von anderen Elementen überdeckt wird.'];

$GLOBALS['TL_LANG']['tl_module']['zIndexDefault'] = '1';

$GLOBALS['TL_LANG']['tl_module']['defaultCss'] = ['Standard-CSS laden','Die CSS-Datei des Cookie Opt In Bar-Moduls laden.'];

$GLOBALS['TL_LANG']['tl_module']['defaultCssDefault'] = true;

$GLOBALS['TL_LANG']['tl_module']['position'] = ['Position'];

$GLOBALS['TL_LANG']['tl_module']['leftTop'] = 'Links Oben';
$GLOBALS['TL_LANG']['tl_module']['leftCenter'] = 'Links Mitte';
$GLOBALS['TL_LANG']['tl_module']['leftBottom'] = 'Links Unten';

$GLOBALS['TL_LANG']['tl_module']['centerTop'] = 'Mitte Oben';
$GLOBALS['TL_LANG']['tl_module']['centerCenter'] = 'Mitte Mitte';
$GLOBALS['TL_LANG']['tl_module']['centerBottom'] = 'Mitte Unten';

$GLOBALS['TL_LANG']['tl_module']['rightTop'] = 'Rechts Oben';
$GLOBALS['TL_LANG']['tl_module']['rightCenter'] = 'Rechts Mitte';
$GLOBALS['TL_LANG']['tl_module']['rightBottom'] = 'Rechts Unten';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle'] = ['Template Style'];

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['dark'] = 'dunkel';

$GLOBALS['TL_LANG']['tl_module']['cssTemplateStyle']['light'] = 'hell';

$GLOBALS['TL_LANG']['tl_module']['maxWidth'] = ['Breite','Bitte geben Sie die Breite der Cookie Bar an.'];

$GLOBALS['TL_LANG']['tl_module']['templateBar'] = ['Template' , 'Der Template-Name muss mit mod_cookie_opt_in_bar beginnen. Achtung auch das Template mod_cookie_opt_in_table muss überschrieben werden.'];

$GLOBALS['TL_LANG']['tl_module']['animation'] = ['Animation', 'Bei Klick auf die Buttons im Frontend'];

$GLOBALS['TL_LANG']['tl_module']['shrink'] = 'rein-/rauszoomen';
$GLOBALS['TL_LANG']['tl_module']['go-up'] = 'ein-/ausfahren';
$GLOBALS['TL_LANG']['tl_module']['shrink-and-rotate'] = 'schrumpfen und drehen';
$GLOBALS['TL_LANG']['tl_module']['hinge'] = 'Scharnier';

$GLOBALS['TL_LANG']['tl_module']['ipFormatSave'] = ['IP-Speicherung','Wählen Sie die Art des Loggings der IP-Adressen.'];

$GLOBALS['TL_LANG']['tl_module']['uncut'] = 'ungekürzt';
$GLOBALS['TL_LANG']['tl_module']['pseudo'] = 'pseudonymisiert';
$GLOBALS['TL_LANG']['tl_module']['anon'] = 'anonymisiert';

$GLOBALS['TL_LANG']['tl_module']['external'] = ['Extern','Über Artikel eingebundene HTML Elemente mit iFrame werden nur bei Einwilligung geladen.'];

$GLOBALS['TL_LANG']['tl_module']['youtube'] = 'YouTube';
$GLOBALS['TL_LANG']['tl_module']['googleMaps'] = 'Google Maps';
