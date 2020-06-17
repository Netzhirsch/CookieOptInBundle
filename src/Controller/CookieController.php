<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Netzhirsch\CookieOptInBundle\Classes\CookieData;
use Netzhirsch\CookieOptInBundle\EventListener\PageLayoutListener;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class CookieController extends AbstractController
{

    /**
     * @Route("/cookie/allowed", name="cookie_allowed")
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     * @throws DBALException
     */
	public function allowedAction(Request $request)
	{
        $data = $request->get('data');
        $newConsent = $data['newConsent'];
		$cookieDatabase = $this->getModulData($data['modId']);
        //nur ohne JS gefüllt
        if (!$data['isJavaScript']) {
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
			if (in_array($cookieTool['id'],$data['cookieIds'])) {
				$cookiesToSet['cookieTools'][] = $cookieTool;
			}
		}
		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (in_array($otherScripts['id'],$data['cookieIds'])) {
				$cookiesToSet['otherScripts'][] = $otherScripts;
			}
		}

        PageLayoutListener::deleteCookie(array_merge($cookiesToSet['cookieTools'],$cookiesToSet['otherScripts']));

        if ($newConsent) {

            $cookieData = new CookieData();
            $cookieData->setId($this->changeConsent($cookiesToSet,$data['modId'],$cookieDatabase));
            $cookieData->setVersion(intval($cookieDatabase['cookieVersion']));
            $cookieData->setOtherCookieIds($data['cookieIds']);
            $cookieData->setIsJavaScript($data['isJavaScript']);
            $this->setNetzhirschCookie(
                $cookieData,
                $data['modId'],
                $cookieDatabase['cookieExpiredTime']
            );
        }
        if (!$data['isJavaScript']) {
            return $this->redirectToPageBefore($data['currentPage']);
        }

		$response = [
			'tools' => $cookiesToSet['cookieTools'],
			'otherScripts' => $cookiesToSet['otherScripts'],
		];
		return new JsonResponse($response);
	}

    /**
     * @param CookieData $cookieData
     * @param $modId
     * @param $cookieExpiredTime
     * @throws DBALException
     */
	private function setNetzhirschCookie($cookieData, $modId, $cookieExpiredTime){

	    /** @noinspection PhpParamsInspection */
        /* @var Connection $conn */
        $conn = $this->get('database_connection');
		$cookieToolsTechnicalName = self::getOptInTechnicalCookieName($conn,$modId);

		$expiredDate = new DateTime();
		$cookieExpiredTime = strtotime('+'.$cookieExpiredTime.' day',$expiredDate->getTimestamp());

		$netzhirschOptInCookie = [
            'cookieId' => $cookieData->getId(),
			'cookieIds' => $cookieData->getOtherCookieIds(),
			'cookieVersion' => $cookieData->getVersion(),
            'isJavaScript' => $cookieData->isJavaScript()
		];
		/** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
		setcookie($cookieToolsTechnicalName, json_encode($netzhirschOptInCookie),$cookieExpiredTime,'/',$_SERVER['HTTP_HOST']);
	}
	
	/**
	 * @param $modId
	 * @return mixed
	 * @throws DBALException
	 */
	private function getModulData($modId){
		
		$response = [];
		
		/** @noinspection PhpParamsInspection */
		/* @var Connection $conn */
		$conn = $this->get('database_connection');
		$sql = "SELECT cookieVersion,cookieExpiredTime FROM tl_ncoi_cookie WHERE pid = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, $modId);
		$stmt->execute();
		$data = $stmt->fetch();
		
		$response['cookieVersion'] = $data['cookieVersion'];
		$response['cookieExpiredTime'] = $data['cookieExpiredTime'];
		
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
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modId);
		$stmt->bindValue(2, 'cookieTools');
		
		$stmt->execute();
		$tools = $stmt->fetchAll();
		
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
		$sql = "SELECT ".implode(", ", $select)." FROM tl_fieldpalette";
		$sql .= ' WHERE pid = ? AND pfield = ?';
		
		$stmt = $conn->prepare($sql);
		
		$stmt->bindValue(1, $modId);
		$stmt->bindValue(2, 'otherScripts');
		
		$stmt->execute();
		$otherScripts = $stmt->fetchAll();
		
		$response['cookieTools'] = $tools;
		$response['otherScripts'] = $otherScripts;
		
		return $response;
	}

    /**
     * @param $cookieData
     * @param $modId
     * @param $cookieDatabase
     * @return string
     * @throws DBALException
     */
	private function changeConsent($cookieData, $modId, $cookieDatabase)
	{
		/** @noinspection PhpParamsInspection */
		$requestStack = $this->get('request_stack');
		/* @var Connection $conn */
		/** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');

        $sql = "SELECT ipFormatSave FROM tl_ncoi_cookie WHERE pid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->execute();
        $ipFormatSave = $stmt->fetchColumn();

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
            if (!empty($headers)) {
                $referer = $headers->get('referer');
                if (!empty($referer))
                    $userInfo['consentURL'] = $referer;
            }
            $cookieData = self::getUserCookie($conn,$requestStack);
            $userInfo['cookieId'] = $cookieData->getId();
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

        $sql = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain,url,pid) VALUES(?,?,?,?,?,?,?)";

		$stmt = $conn->prepare($sql);

		$stmt->bindValue(1, $userInfo['ip']);
		$cookieNames = [];
		$cookieTechnicalName = [];
		$otherCookieIds = $cookieData->getOtherCookieIds();
        if (!empty($otherCookieIds)) {
            foreach ($cookieData->getOtherCookieIds() as $cookieTool) {
                foreach ($cookieDatabase['cookieTools'] as $cookieDataFromDb) {
                    if ($cookieDataFromDb['id'] == $cookieTool) {
                        $cookieNames[] = $cookieDataFromDb['cookieToolsName'];
                        $cookieTechnicalName[] = $cookieDataFromDb['cookieToolsTechnicalName'];
                    }
                }
            }
        }
        $stmt->bindValue(2, implode(', ', $cookieNames));
        $stmt->bindValue(3, implode(', ', $cookieTechnicalName));
        $stmt->bindValue(4, date('Y-m-d H:i'));
        $stmt->bindValue(5, $_SERVER['HTTP_HOST']);
        $stmt->bindValue(6, $userInfo['consentURL']);
        $stmt->bindValue(6, $userInfo['consentURL']);
        $stmt->bindValue(7, ($userInfo['cookieId']) == '' ? null : $userInfo['cookieId']);

        $stmt->execute();

        return $conn->lastInsertId();

	}

    /**
     * @param $conn
     * @param RequestStack $requestStack
     * @param Request $request
     * @return CookieData
     * @throws DBALException
     */
	public static function getUserCookie($conn,$requestStack = null,$request = null)
    {
        $cookieData = new CookieData();

        if (empty($requestStack) && empty($request))
            return $cookieData;

        $currentRequest = $request;
        if (empty($currentRequest))
            $currentRequest = $requestStack->getCurrentRequest();


        if (!empty($currentRequest)) {
            $cookieSet = $currentRequest->cookies;
            $data = $currentRequest->request->get('data');
            $modId = $data['modId'];
            $cookieToolsTechnicalName = self::getOptInTechnicalCookieName($conn,$modId);
            if (!empty($cookieSet) && !empty($cookieToolsTechnicalName)) {
                $cookieSet = $cookieSet->get($cookieToolsTechnicalName);
                if (empty($cookieSet))
                    $cookieSet = $_COOKIE[$cookieToolsTechnicalName];

                /** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
                $cookieId = json_decode($cookieSet);
                $cookieData->setId(intval($cookieId->cookieId));
                $cookieData->setVersion($cookieId->cookieVersion);
                $cookieData->setOtherCookieIds($cookieId->cookieIds);
            }
        }
        return $cookieData;
    }
    /**
     * @Route("/cookie/revoke", name="cookie_revoke")
     * @param Request $request
     * @return RedirectResponse
     * @throws DBALException
     */
    public function revokeAction(Request $request)
    {
        /* @var Connection $conn */
        /** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');
        $optInTechnicalName = self::getOptInTechnicalCookieName($conn,$request->get('data')['modId']);
        setrawcookie($optInTechnicalName, 1, time() - 360000, '/', $_SERVER['HTTP_HOST']);

        return $this->redirectToPageBefore($request->get('currentPage'));
    }

    /**
     * @param $currentPage
     * @return RedirectResponse
     */
    private function redirectToPageBefore($currentPage){
        /* @var ContaoFramework $framework */
        /** @noinspection PhpParamsInspection */
        $framework = $this->get('contao.framework');
        $framework->initialize();

        if (empty($currentPage))
            $currentPage = '/';
        /** @noinspection PhpParamsInspection */
        return $this->redirect($currentPage);
    }

    /**
     * @param Connection $conn
     * @param $modId
     * @return false|mixed
     * @throws DBALException
     */
    public static function getOptInTechnicalCookieName($conn, $modId)
    {
        $sql = "SELECT cookieToolsTechnicalName FROM tl_fieldpalette WHERE cookieToolsSelect = ? AND pid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, 'optInCookie');
        $stmt->bindValue(2, $modId);
        $stmt->execute();
        return $stmt->fetchColumn();

    }

    /**
     * @Route("/cookie/allowed/iframe", name="cookie_allowed_iframe")
     * @param Request $request
     * @return RedirectResponse
     * @throws DBALException
     */
    public function allowedIframeAction(Request $request)
    {
        $iframe = $request->get('iframe');
        $modId = $request->get('data')['modId'];
        /* @var Connection $conn */
        /** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');
        $optInTechnicalName = self::getOptInTechnicalCookieName($conn,$modId);
        $sql = "SELECT id,cookieToolsSelect,cookieToolExpiredTime FROM tl_fieldpalette WHERE (pid = ? AND cookieToolsSelect = ?) OR (pid = ? AND cookieToolsTechnicalName = ?) ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->bindValue(2, $iframe);
        $stmt->bindValue(3, $modId);
        $stmt->bindValue(4, $optInTechnicalName);
        $stmt->execute();
        $cookies = $stmt->fetchAll();
        $nhCookie = null;
        $otherCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie['cookieToolsSelect'] == 'optInCookie') {
                $nhCookie = $cookie;
            } else {
                $otherCookie = $cookie;
            }
        }

        if (!empty($nhCookie) && !empty($otherCookie) ) {
            $cookieData = self::getUserCookie($conn,null,$request);

            if (!empty($cookieData)) {
                $otherCookieIds = $cookieData->getOtherCookieIds();
            } else {
                $cookieData = new CookieData();
            }
            $otherCookieIds[] = $otherCookie['id'];
            $cookieData->setOtherCookieIds($otherCookieIds);
            $this->setNetzhirschCookie($cookieData,$modId,$nhCookie['cookieToolExpiredTime']);
        }

        return $this->redirectToPageBefore($request->get('currentPage'));
    }
}
