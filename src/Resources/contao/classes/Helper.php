<?php


namespace Netzhirsch\CookieOptInBundle\Classes;


use Contao\StringUtil;
use Exception;
use Less_Exception_Parser;
use Less_Parser;

class Helper
{
    /**
     * @param             $lessFile
     * @param             $cssFile
     * @param null $maxWidth
     * @param null $blockSite
     * @throws Less_Exception_Parser
     */
	public static function parseLessToCss($lessFile,$cssFile,$maxWidth = null,$blockSite = null){
		$path = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR;

		$parser = new Less_Parser();
		$parser->parseFile($path.$lessFile);
		$array = StringUtil::deserialize($maxWidth);
		$maxWidth = $array['value'];
		$maxWidth .= $array['unit'];
		$parser->ModifyVars(['maxWidth' => $maxWidth]);

        $zIndex = 0;
		if ($blockSite == '1')
            $zIndex = 1000000;
        $parser->ModifyVars(['zIndex' => $zIndex]);

		try {
			$css = $parser->getCss();
			file_put_contents($path.$cssFile,$css);
		} catch (Exception $e) {
		}


	}
}