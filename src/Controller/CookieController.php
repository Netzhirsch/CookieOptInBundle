<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\Database;
use Netzhirsch\CookieOptInBundle\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class CookieController extends AbstractController
{

    /** @var Database $database */
    private $database;
    /** @var RequestStack $requestStack */
    private $requestStack;

    public function __construct(
        RequestStack $requestStack
    )
    {
        $this->database = Database::getInstance();
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/cookie/allowed", name="cookie_allowed")
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
	public function allowedAction(Request $request)
	{
        $jsonResponse = new JsonResponse();
        $jsonResponse->setData(['success' => false]);
        $files = $request->files->all();
        if (!empty($files))
            return $jsonResponse;

        $data = $request->get('data');
        if (empty($data['modId']))
            return $jsonResponse;

        $return = $this->getTlCookieData($data['modId']);
        $data['ipFormatSave'] = $return['ipFormatSave'];
        $data['cookieVersion'] = $return['cookieVersion'];
        $data['expireTime'] = $return['expireTime'];
		$cookieDatabase = $this->getModulData($data['modId'],$data);
        if (count($cookieDatabase) == 0)
            return $jsonResponse;

        //nur ohne JS gesetzt
        if (isset($data['isNoJavaScript'])) {
            if (!isset($data['cookieIds']))
                $data['cookieIds'] = [];
            foreach ($data['cookieGroups'] as $cookieGroup) {
                foreach ($cookieDatabase['cookieTools'] as $cookieInDB) {
                    if (!empty($data['all']))
                        $data['cookieIds'][] = $cookieInDB['id'];
                    elseif ($cookieGroup == $cookieInDB['cookieToolGroup'])
                        $data['cookieIds'][] = $cookieInDB['id'];
                }
                foreach ($cookieDatabase['otherScripts'] as $cookieInDB) {
                    if (!empty($data['all']))
                        $data['cookieIds'][] = $cookieInDB['id'];
                    elseif ($cookieGroup == $cookieInDB['cookieToolGroup'])
                        $data['cookieIds'][] = $cookieInDB['id'];
                }
            }

        }
		$cookiesToSet = [
            'cookieTools' => [],
            'otherScripts' => [],
        ];

		foreach ($cookieDatabase['cookieTools'] as $cookieTool) {
			if (in_array($cookieTool['id'],$data['cookieIds']))
				$cookiesToSet['cookieTools'][] = $cookieTool;
		}

		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (in_array($otherScripts['id'],$data['cookieIds']))
				$cookiesToSet['otherScripts'][] = $otherScripts;
		}

        self::deleteCookies(array_merge($cookiesToSet['cookieTools'],$cookiesToSet['otherScripts']));

        $id = null;
        if (isset($data['id']))
            $id = $data['id'];

        $newConsent = $data['newConsent'];
        if ($newConsent) {
            $id = $this->changeConsent($cookiesToSet,$data,$id);
        }

        $expireTime = self::getExpireTime($cookieDatabase['expireTime']);

        if (isset($data['isNoJavaScript']) && !$request->get('isJava')) {
            if ($request->hasSession()) {
                $session = $request->getSession();
                $session->set('ncoi',[
                    'id' => $id,
                    'cookieIds' => $data['cookieIds'],
                    'cookieVersion' => $cookieDatabase['cookieVersion'],
                    'expireTime' => $expireTime
                ]);
                $session->save();
            }
        }

        $jsonResponse->setData([
            'success' => true,
            'tools' => $cookiesToSet['cookieTools'],
            'otherScripts' => $cookiesToSet['otherScripts'],
            'id' => $id,
            'cookieVersion' => $cookieDatabase['cookieVersion'],
            'expireTime' => $expireTime
        ]);

		return $jsonResponse;
	}

    /**
     * @param $modId
     * @param $data
     * @return array
     */
	private function getModulData($modId,$data){
		
		$response = [];

        $response['cookieVersion'] = 1;
        if (!empty($data['cookieVersion']))
		    $response['cookieVersion'] = $data['cookieVersion'];

        $response['expireTime'] = 30;
        if (!empty($data['expireTime']))
		    $response['expireTime'] = $data['expireTime'];

		$select = [
			'id',
			'cookieToolsName',
			'cookieToolsTechnicalName',
			'cookieToolsPrivacyPolicyUrl',
			'cookieToolsProvider',
			'cookieToolsTrackingID',
			'cookieToolsTrackingServerUrl',
			'cookieToolsSelect',
			'cookieToolsUse',
			'cookieToolGroup',
			'cookieToolExpiredTime',
		];
        $repo = new Repository($this->database);
        $strQueryTools = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$strQueryTools .= ' WHERE pid = %s AND pfield = %s';
        $tools = $repo->findAllAssoc($strQueryTools,[], [$modId,'cookieTools']);

		$select = [
			'id',
			'cookieToolsName',
			'cookieToolsTechnicalName',
			'cookieToolsPrivacyPolicyUrl',
			'cookieToolsProvider',
			'cookieToolsUse',
			'cookieToolsCode',
			'cookieToolsCode',
			'cookieToolExpiredTime',
			'cookieToolGroup',
		];

		$strQueryOtherScripts = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$strQueryOtherScripts .= ' WHERE pid = %s AND pfield = %s';
		
		$otherScripts = $repo->findAllAssoc($strQueryOtherScripts,[], [$modId,'otherScripts']);
		
		$response['cookieTools'] = $tools;
		$response['otherScripts'] = $otherScripts;
		
		return $response;
	}

    /**
     * @param $cookieData
     * @param $data
     * @param $id
     * @return string
     */
	private function changeConsent($cookieData,$data,$id = null)
	{
		$requestStack = $this->requestStack;

        $ipFormatSave = $data['ipFormatSave'];

        //Besucher Infos
        $currentRequest = $requestStack->getCurrentRequest();
        $userInfo = [
            'ip' => '',
            'consentURL' => '',
            'cookieId' => null,
        ];
        if (!empty($currentRequest)) {

            $userInfo['ip'] = $currentRequest->getClientIp();
            if ($userInfo['ip'] == '::1')
                $userInfo['ip'] = '127.0.0.1';

            $headers = $currentRequest->headers;
            $userInfo['consentURL'] = '';
            if (!empty($headers)) {
                $referer = $headers->get('referer');
                if (!empty($referer))
                    $userInfo['consentURL'] = $referer;
            }
            $userInfo['cookieId'] = $id;
        }

        if (!empty($ipFormatSave) && $ipFormatSave != 'uncut') {
            $userInfo['ip'] = explode('.',$userInfo['ip']);
            end($userInfo['ip']);
            $lastIndex = key($userInfo['ip']);
            $userInfo['ip'][$lastIndex] = '*';
            if ($ipFormatSave == 'anon')
                $userInfo['ip'][--$lastIndex] = '*';

            $userInfo['ip'] = implode('.',$userInfo['ip']);
        }

		$cookieNames = [];
		$cookieTechnicalName = [];
		$otherCookieIds = array_merge($cookieData['cookieTools'],$cookieData['otherScripts']);
        if (!empty($otherCookieIds)) {
            foreach ($otherCookieIds as $cookieTool) {
                $cookieNames[] = $cookieTool['cookieToolsName'];
                $cookieTechnicalName[] = $cookieTool['cookieToolsTechnicalName'];
            }
        }

        $strQuery = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain,url,pid) VALUES(%s,%s,%s,%s,%s,%s,%s)";
        $conn = $this->database;
		$stmt = $conn->prepare($strQuery);
        if (empty($stmt))
            return $id;

        $host = $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']:'';
        $repo = new Repository($this->database);
        $set =
            [
                $userInfo['ip'],
                implode(', ', $cookieNames),
                implode(', ', $cookieTechnicalName),
                date('Y-m-d H:i'),
                $host,
                $userInfo['consentURL'],
                $userInfo['cookieId'] ? $userInfo['cookieId'] : 1,
        ];

        $result = $repo->executeStatement($strQuery, $set,[]);

        return $result->last()['id'];

	}

    /**
     * @Route("/cookie/allowed/iframe", name="cookie_allowed_iframe")
     * @param Request $request
     */
    public function allowedIframeAction(Request $request)
    {
        $iframe = $request->get('iframe');
        $modId = $request->get('data')['modId'];

        $strQuery = "SELECT id,cookieToolsSelect,cookieToolExpiredTime,cookieToolsName,cookieToolsTechnicalName FROM tl_fieldpalette WHERE (pid = %s AND cookieToolsSelect = %s)";

        $repo = new Repository($this->database);
        $cookie = $repo->findAllAssoc($strQuery,[], [$modId,$iframe]);

        if (count($cookie) == 0)
            return;
        if ($request->hasSession()) {
            $session = $request->getSession();
            $data = $this->getTlCookieData($modId);
            $cookieDatabase = $this->getModulData($modId,$data);
            $id = $this->changeConsent(['cookieTools' => [$cookie],'otherScripts' => []],$data);
            if (isset($_SESSION) && isset($_SESSION['_sf2_attributes']) && isset($_SESSION['_sf2_attributes']['ncoi'])) {
                $ncoi = $_SESSION['_sf2_attributes']['ncoi'];
                $ncoi['cookieIds'][] = $cookie['id'];
            } else {
                $ncoi = [
                    'id' => $id,
                    'cookieIds' => [$cookie['id']],
                    'cookieVersion' => $cookieDatabase['cookieVersion'],
                    'expireTime' => self::getExpireTime($cookieDatabase['expireTime'])
                ];
            }
            $session->set('ncoi',$ncoi);
            $session->save();
        }
    }

    /**
     * @Route("/cookie/delete", name="cookie_delete")
     * @param array|null $cookieNotToDelete Cookies that should not be deleted
     * @return JsonResponse
     */
    public static function deleteCookies(Array $cookieNotToDelete = null) {
        ob_start();
        $cookiesSet = CookieController::unsetCookiesFromArray($cookieNotToDelete);

        //all possible subdomains
        $subDomains = explode(".", $_SERVER['HTTP_HOST']);
        foreach ($subDomains as $key => $subDomain) {
            $domain = implode(".", $subDomains);
            unset($subDomains[$key]);

            $domainWithDot = explode('www',$domain);
            if (is_array($domainWithDot) && count($domainWithDot) >= 2) {
                $domainWithDot = $domainWithDot[1];
            } else {
                $domainWithDot = '';
            }
            foreach ($cookiesSet as $cookieSetTechnicalName => $cookieSet) {
                if (
                    $cookieSetTechnicalName == 'XDEBUG_SESSION'
                    || $cookieSetTechnicalName == 'BE_USER_AUTH'
                    || $cookieSetTechnicalName == 'FE_USER_AUTH'
                    || $cookieSetTechnicalName == 'BE_PAGE_OFFSET'
                    || $cookieSetTechnicalName == 'trusted_device'
                    || $cookieSetTechnicalName == 'csrf_contao_csrf_token'
                    || $cookieSetTechnicalName == 'csrf_https-contao_csrf_token'
                    || $cookieSetTechnicalName == 'PHPSESSID'
                    || $cookieSetTechnicalName == 'contao_settings'
                )
                    continue;
                setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/');
                setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/', $domain);
                setrawcookie($cookieSetTechnicalName, '', time() - 36000000, '/', '.' . $domain);
                setrawcookie(
                    $cookieSetTechnicalName
                    , ''
                    , time() - 36000000
                    , '/'
                    , $domainWithDot
                );

            }
        }
        ob_end_flush();

        return new JsonResponse();
    }

    public static function getExpireTime($expireTimeFromDB)
    {
        $expireDays = --$expireTimeFromDB;
        return date('Y-m-d', strtotime('+'.$expireDays.'day',time()));
    }

    /**
     * @param $modId
     * @return mixed
     */
    private function getTlCookieData($modId) {
        $repo = new Repository($this->database);
        $strQuery = "SELECT ipFormatSave,cookieVersion,expireTime FROM tl_ncoi_cookie WHERE pid = %s";
        return $repo->findRow($strQuery,[], [$modId]);
    }

    private static function unsetCookiesFromArray($cookieNotToDelete)
    {
        $cookiesSet = $_COOKIE;
        if (!empty($cookieNotToDelete)) {
            foreach ($cookiesSet as $cookieSetTechnicalName => $value) {
                foreach ($cookieNotToDelete as $cookie) {
                    $cookieToolsTechnicalName = $cookie['cookieToolsTechnicalName'];
                    if (strpos($cookieToolsTechnicalName,',')) {
                        $cookieToolsTechnicalName = explode(',',$cookieToolsTechnicalName);
                        foreach ($cookieToolsTechnicalName as $cookieToolTechnicalName) {
                            $cookieToolTechnicalName = trim($cookieToolTechnicalName);
                            unset($cookiesSet[$cookieToolTechnicalName]);
                        }
                    } else {
                        unset($cookiesSet[$cookie['cookieToolsTechnicalName']]);
                    }
                }
            }
        }
        return $cookiesSet;
    }
}
