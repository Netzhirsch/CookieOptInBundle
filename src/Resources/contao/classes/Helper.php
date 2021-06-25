<?php


namespace Netzhirsch\CookieOptInBundle\Classes;


use Contao\StringUtil;
use Contao\System;
use Exception;
use Less_Exception_Parser;
use Less_Parser;
use Netzhirsch\CookieOptInBundle\Logger\Logger;

class Helper
{
    /**
     * @param             $lessFile
     * @param             $cssFile
     * @param null $maxWidth
     * @param null $blockSite
     * @param null $zIndex
     * @param bool $makeNew
     * @throws Less_Exception_Parser
     */
	public static function parseLessToCss($lessFile,$cssFile,$maxWidth = null,$blockSite = null,$zIndex = null,$makeNew = false){
		$path = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR;

		$parser = new Less_Parser();
		$parser->parseFile($path.$lessFile);
		$array = StringUtil::deserialize($maxWidth);
        if (!empty($array)) {
            $maxWidth = $array['value'];
            $maxWidth .= $array['unit'];
            $parser->ModifyVars(['maxWidth' => $maxWidth]);
        }

        if (empty($zIndex))
            $zIndex = 1;
        $parser->ModifyVars(['zIndex' => $zIndex]);

		if ($blockSite == '') {
            $parser->ModifyVars(['background' => 'none']);
            $parser->ModifyVars(['pointerEvents' => 'none']);
        }

		try {
			$css = $parser->getCss();
            $dir = dirname(__DIR__,7).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css';
            if (!file_exists($path.$cssFile) || $makeNew) {
                if (is_dir($dir)) {
                    if ($dh = opendir($dir)) {
                        while (($file = readdir($dh)) !== false) {
                            if($file == '.' || $file == '..' || stripos($file, 'netzhirsch') === false)
                                continue;
                            unlink($dir.DIRECTORY_SEPARATOR.$file);
                        }
                        closedir($dh);
                    }
                }
			    file_put_contents($path.$cssFile,$css);
            }
		} catch (Exception $e) {
		    Logger::logExceptionInContaoSystemLog($e->getMessage());
		}
	}

	public static function isAdmin() {
        $tokenStorage = System::getContainer()->get('security.token_storage');
        $token = $tokenStorage->getToken();
        $user = $token->getUser();
        /** Contao 4.9 diff Contao 4.4 */
        if (method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
            return in_array('ROLE_ADMIN', $roles);
        } else {
            return $user->isAdmin;
        }
    }
}