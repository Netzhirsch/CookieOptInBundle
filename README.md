# Netzhirsch Cookie Opt In Bundle

## System requirements
 * Web server
 * PHP 7.2.0 with GDlib, DOM, Phar and SOAP
 * MariaDB 10.4.7


## Getting Started
 ### Console
 * folgenden Abschnitt in die composer.json
 * ```json 
    "repositories": [
          {
              "type": "git",
              "url": "https://netzhirsch@github.com/Netzhirsch/CookieOptInBundle.git"
          }
    ], 
   ```
 * /usr/local/bin/composer req netzhirsch/cookie-opt-in-bundle --optimize-autoloader
 * Installtool aufrufen url/conato/install
 * Module installieren
 * Opt In Bar Modul im Layout einbinden
 * Revoke Modul in Layout oder auf eine Seite einbinden