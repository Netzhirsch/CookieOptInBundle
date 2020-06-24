<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
			if (in_array($cookieTool['id'],$data['cookieIds'])) {
				$cookiesToSet['cookieTools'][] = $cookieTool;
			}
		}
		foreach ($cookieDatabase['otherScripts'] as $otherScripts) {
			if (in_array($otherScripts['id'],$data['cookieIds'])) {
				$cookiesToSet['otherScripts'][] = $otherScripts;
			}
		}

        self::deleteCookies(array_merge($cookiesToSet['cookieTools'],$cookiesToSet['otherScripts']));
        $cookieData = null;
        $id = $data['id'];
        if ($newConsent || empty($id)) {
            $id = $this->changeConsent($id,$cookiesToSet,$data['modId'],$cookieDatabase);
        }
        if (isset($data['isNoJavaScript'])) {
            if ($request->hasSession()) {
                $session = $request->getSession();
                $session->set('ncoi',[
                    'id' => $id,
                    'cookieIds' => $data['cookieIds'],
                    'cookieVersion' => $cookieDatabase['cookieVersion']
                ]);
                $session->save();
            }
            return $this->redirectToPageBefore($data['currentPage']);
        }

		$response = [
			'tools' => $cookiesToSet['cookieTools'],
			'otherScripts' => $cookiesToSet['otherScripts'],
            'id' => $id,
            'cookieVersion' => $cookieDatabase['cookieVersion']
		];
		return new JsonResponse($response);
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
		$sql = "SELECT cookieVersion FROM tl_ncoi_cookie WHERE pid = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue(1, $modId);
		$stmt->execute();
		$data = $stmt->fetch();
		
		$response['cookieVersion'] = $data['cookieVersion'];

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
     * @param $id
     * @param $cookieData
     * @param $modId
     * @param $cookieDatabase
     * @return string
     * @throws DBALException
     */
	private function changeConsent($id,$cookieData, $modId, $cookieDatabase)
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

        $sql = "INSERT INTO tl_consentDirectory (ip,cookieToolsName,cookieToolsTechnicalName,date,domain,url,pid) VALUES(?,?,?,?,?,?,?)";

		$stmt = $conn->prepare($sql);

		$stmt->bindValue(1, $userInfo['ip']);
		$cookieNames = [];
		$cookieTechnicalName = [];
		$otherCookieIds = $cookieData['getOtherCookieIds'];
        if (!empty($otherCookieIds)) {
            foreach ($cookieData['getOtherCookieIds'] as $cookieTool) {
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
     * @Route("/cookie/revoke", name="cookie_revoke")
     * @param Request $request
     * @return RedirectResponse
     */
    public function revokeAction(Request $request)
    {
        $query = $request->query;
        $currentPage = '/';
        if (!empty($query)) {
            $currentPage = $query->get('currentPage');
            if (empty($currentPage))
                $currentPage = '';
        }
        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->set('ncoi',null);
            $session->save();
        }

        return $this->redirectToPageBefore($currentPage);
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
        $sql = "SELECT id,cookieToolsSelect,cookieToolExpiredTime FROM tl_fieldpalette WHERE (pid = ? AND cookieToolsSelect = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $modId);
        $stmt->bindValue(2, $iframe);
        $stmt->execute();
        $cookie = $stmt->fetch();

        if ($request->hasSession()) {
            $session = $request->getSession();
            $cookieDatabase = $this->getModulData($modId);
            $id = $this->changeConsent('',[$cookie['id']],$modId,$cookieDatabase);
            if (isset($_SESSION) && isset($_SESSION['_sf2_attributes']) && isset($_SESSION['_sf2_attributes']['ncoi'])) {
                $ncoi = $_SESSION['_sf2_attributes']['ncoi'];
                $ncoi['cookieIds'][] = $cookie['id'];
            } else {
                $ncoi = [
                    'id' => $id,
                    'cookieIds' => [$cookie['id']],
                    'cookieVersion' => $cookieDatabase['cookieVersion']
                ];
            }
            $session->set('ncoi',$ncoi);
            $session->save();
        }

        return $this->redirectToPageBefore($request->get('currentPage'));
    }

    /**
     * @Route("/cookie/delete", name="cookie_delete")
     * @param array|null $cookieNotToDelete Cookies that should not be deleted
     * @return JsonResponse
     */
    public static function deleteCookies(Array $cookieNotToDelete = null) {
        ob_start();
        $cookiesSet = $_COOKIE;
        if (!empty($cookieNotToDelete)) {
            foreach ($cookiesSet as $cookieSetTechnicalName => $value) {
                foreach ($cookieNotToDelete as $cookie) {
                    unset($cookiesSet[$cookie['cookieToolsTechnicalName']]);
                }
            }
        }

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
                    || $cookieSetTechnicalName == 'BE_PAGE_OFFSET'
                    || $cookieSetTechnicalName == 'trusted_device'
                    || $cookieSetTechnicalName == 'csrf_contao_csrf_token'
                    || $cookieSetTechnicalName == 'csrf_https-contao_csrf_token'
                    || $cookieSetTechnicalName == 'PHPSESSID'
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
}
