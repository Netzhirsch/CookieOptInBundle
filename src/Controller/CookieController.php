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
		$cookieDatabase = $this->getModulData($data['modID']);
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
		$cookiesToDelete = [];
		$cookiesToSet = [];
		foreach ($cookieDatabase['cookieTools'] as $cookieTool) {
			if (!in_array($cookieTool['id'],$data['cookieIds'])) {
				$cookiesToDelete[] = $cookieTool;
			} else {
				$cookiesToSet['cookieTools'][] = $cookieTool;
			}
		}
        if (!isset($cookiesToSet['otherScripts'])) {
            $cookiesToSet['otherScripts'] = [];
        }
		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (!in_array($otherScripts['id'],$data['cookieIds'])) {
				$cookiesToDelete[] = $otherScripts;
			}else {
				$cookiesToSet['otherScripts'][] = $otherScripts;
			}
		}

		if (!empty($cookiesToDelete))
			PageLayoutListener::deleteCookie($cookiesToDelete);

        if ($newConsent) {

            $cookieData = new CookieData();
            $cookieData->setId($this->changeConsent($cookiesToSet,$data['modID'],$cookieDatabase));
            $cookieData->setVersion(intval($cookieDatabase['cookieVersion']));
            $cookieData->setOtherCookieIds($data['cookieIds']);
            $cookieData->setIsJavaScript($data['isJavaScript']);
            $this->setNetzhirschCookie(
                $cookieData,
                $data['modID'],
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
     * @param $modID
     * @param $cookieExpiredTime
     * @throws DBALException
     */
	private function setNetzhirschCookie($cookieData,$modID,$cookieExpiredTime){

		$cookieToolsTechnicalName = $this->getNetzhirschTechnicalCookieName($modID);

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
	 * @param $modID
	 * @return mixed
	 * @throws DBALException
	 */
	private function getModulData($modID){

		$response = [];

		/** @noinspection PhpParamsInspection */
		$conn = $this->get('database_connection');
		/* @var Connection $conn */
		$sql = "SELECT cookieVersion,cookieExpiredTime FROM tl_module WHERE type = ? AND id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, 'cookieOptInBar');
		$stmt->bindValue(2, $modID);
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

		$stmt->bindValue(1, $modID);
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

		$stmt->bindValue(1, $modID);
		$stmt->bindValue(2, 'otherScripts');

		$stmt->execute();
		$otherScripts = $stmt->fetchAll();

		$response['cookieTools'] = $tools;
		$response['otherScripts'] = $otherScripts;

		return $response;
	}

    /**
     * @param $cookieData
     * @param $modID
     * @param $cookieDatabase
     * @return string
     * @throws DBALException
     */
	private function changeConsent($cookieData,$modID,$cookieDatabase)
	{
		/** @noinspection PhpParamsInspection */
		$requestStack = $this->get('request_stack');
		/* @var Connection $conn */
		/** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');

        $sql = "SELECT ipFormatSave FROM tl_module WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modID);
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
            $cookieData = self::getUserCookie($requestStack);
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
     * @param RequestStack $requestStack
     * @param Request $request
     * @return CookieData
     */
	public static function getUserCookie($requestStack = null,$request = null)
    {
        $cookieData = new CookieData();

        if (empty($requestStack) && empty($request))
            return $cookieData;

        $currentRequest = $request;
        if (empty($currentRequest))
            $currentRequest = $requestStack->getCurrentRequest();

        if (!empty($currentRequest)) {
            $cookieSet = $currentRequest->cookies;
            if (!empty($cookieSet)) {
                $cookieSet = $cookieSet->get('_netzhirsch_cookie_opt_in');
                if (!empty($cookieSet)) {
                    /** @noinspection PhpComposerExtensionStubsInspection "ext-json": "*" is required in bundle composer phpStorm don't know this*/
                    $cookieId = json_decode($cookieSet);
                    $cookieData->setId(intval($cookieId->cookieId));
                    $cookieData->setVersion($cookieId->cookieVersion);
                    $cookieData->setOtherCookieIds($cookieId->cookieIds);
                }
            }
        }
        return $cookieData;
    }

    /**
     * @Route("/cookie/revoke", name="cookie_revoke")
     * @param Request $request
     * @return RedirectResponse
     */
    public function revokeAction(Request $request)
    {
        setrawcookie('_netzhirsch_cookie_opt_in', 1, time() - 360000, '/', $_SERVER['HTTP_HOST']);

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
     * @param $modID
     * @return false|mixed
     * @throws DBALException
     */
    private function getNetzhirschTechnicalCookieName($modID)
    {
        /* @var Connection $conn */
        /** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');
        $sql = "SELECT cookieToolsTechnicalName FROM tl_fieldpalette WHERE cookieToolsTechnicalName = ? AND pid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, '_netzhirsch_cookie_opt_in');
        $stmt->bindValue(2, $modID);
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
        $modID = $request->get('modID');
        /* @var Connection $conn */
        /** @noinspection PhpParamsInspection */
        $conn = $this->get('database_connection');
        $sql = "SELECT id,cookieToolsSelect,cookieToolExpiredTime FROM tl_fieldpalette WHERE (pid = ? AND cookieToolsSelect = ?) OR (pid = ? AND cookieToolsTechnicalName = ?) ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modID);
        $stmt->bindValue(2, $iframe);
        $stmt->bindValue(3, $modID);
        $stmt->bindValue(4, '_netzhirsch_cookie_opt_in');
        $stmt->execute();
        $cookies = $stmt->fetchAll();
        $nhCookie = null;
        $otherCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie['cookieToolsSelect'] == '-') {
                $nhCookie = $cookie;
            } else {
                $otherCookie = $cookie;
            }
        }

        if (!empty($nhCookie) && !empty($otherCookie) ) {
            $cookieData = self::getUserCookie(null,$request);

            if (!empty($cookieData)) {
                $otherCookieIds = $cookieData->getOtherCookieIds();
            } else {
                $cookieData = new CookieData();
            }
            $otherCookieIds[] = $otherCookie['id'];
            $cookieData->setOtherCookieIds($otherCookieIds);
            $this->setNetzhirschCookie($cookieData,$modID,$nhCookie['cookieToolExpiredTime']);
        }

        return $this->redirectToPageBefore($request->get('currentPage'));
    }
}
