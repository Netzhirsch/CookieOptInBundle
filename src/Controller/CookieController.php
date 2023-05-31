<?php
namespace Netzhirsch\CookieOptInBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Doctrine\ORM\EntityManagerInterface;
use Netzhirsch\CookieOptInBundle\Entity\CookieTool;
use Netzhirsch\CookieOptInBundle\Entity\CookieToolContainer;
use Netzhirsch\CookieOptInBundle\Entity\OtherScript;
use Netzhirsch\CookieOptInBundle\Entity\OtherScriptContainer;
use Netzhirsch\CookieOptInBundle\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class CookieController extends AbstractController
{

    /** @var RequestStack $requestStack */
    private $requestStack;
    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    public function __construct(
        RequestStack $requestStack,
        ContaoFramework $contaoFramework
    )
    {
        $this->requestStack = $requestStack;
        $this->contaoFramework = $contaoFramework;
    }

    /**
     * @Route("/cookie/allowed", name="cookie_allowed")
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     *
     * @return JsonResponse|RedirectResponse
     */
	public function allowedAction(Request $request,EntityManagerInterface $entityManager): RedirectResponse|JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setData(['success' => false]);
        $files = $request->files->all();
        if (!empty($files))
            return $jsonResponse;

        $data = $request->get('data');
        if (empty($data['modId']))
            return $jsonResponse;

        $this->contaoFramework->initialize();
        $database = Database::getInstance();
        $return = $this->getTlCookieData($database,$data['modId']);
        if (empty($return)) {
            $jsonResponse->setData(['message' => 'Module '.$data['modId'].' in tl_ncoi_cookie nicht gefunden.']);
            return $jsonResponse;
        }

        $data['ipFormatSave'] = $return['ipFormatSave'];
        $data['cookieVersion'] = $return['cookieVersion'];
        $data['expireTime'] = $return['expireTime'];
		$cookieDatabase = $this->getModulData($data,$entityManager,$data['modId']);
        if (count($cookieDatabase['cookieTools']) == 0) {
            $jsonResponse->setData(['message' => 'Module '.$data['modId'].' in tl_cookie_tool_container nicht gefunden.']);
            return $jsonResponse;
        }
        if (!isset($data['cookieIds']))
            $data['cookieIds'] = [];

		$cookiesToSet = [
            'cookieTools' => [],
            'otherScripts' => [],
        ];

		foreach ($cookieDatabase['cookieTools'] as $cookieTool) {
			if (in_array($cookieTool->getId(),$data['cookieIds']))
				$cookiesToSet['cookieTools'][] = $cookieTool;
		}

        foreach ($cookieDatabase['otherScripts']  as $otherScript) {
            if (in_array($otherScript->getId(),$data['cookieIds']))
                $cookiesToSet['otherScripts'][] = $otherScript;
        }
        $this->deleteCookies(array_merge($cookiesToSet['cookieTools'],$cookiesToSet['otherScripts']));

        $id = null;
        if (isset($data['id']))
            $id = $data['id'];

        $newConsent = $data['newConsent'];
        if ($newConsent) {
            $id = $this->changeConsent($database,$cookiesToSet,$data,$id);
        }

        $expireTime = self::getExpireTime($cookieDatabase['expireTime']);
        $tools = [];
        /** @var CookieTool $cookieTool */
        foreach ($cookiesToSet['cookieTools'] as $cookieTool) {
            $tools[] = [
                'cookieToolsTechnicalName' => $cookieTool->getCookieToolsTechnicalName(),
                'cookieToolsSelect' => $cookieTool->getCookieToolsSelect(),
                'cookieToolsTrackingID' => $cookieTool->getCookieToolsTrackingId(),
                'cookieToolsTrackingServerUrl' => $cookieTool->getCookieToolsTrackingServerUrl()
            ];
        }
        $otherScripts = [];
        /** @var OtherScript $otherScript */
        foreach ($cookiesToSet['otherScripts'] as $otherScript) {
            $otherScripts[] = [
                'cookieToolsTechnicalName' => $otherScript->getCookieToolsTechnicalName(),
                'cookieToolsCode' => $otherScript->getCookieToolsCode(),
            ];
        }
        $jsonResponse->setData([
            'success' => true,
            'tools' => $tools,
            'otherScripts' => $otherScripts,
            'id' => $id,
            'cookieVersion' => $cookieDatabase['cookieVersion'],
            'expireTime' => $expireTime,
        ]);

		return $jsonResponse;
	}

    /**
     * @param                        $data
     * @param EntityManagerInterface $entityManager
     * @param                        $modId
     *
     * @return array
     */
	private function getModulData($data,EntityManagerInterface $entityManager,$modId): array
    {
		
		$response = [];

        $response['cookieVersion'] = 1;
        if (!empty($data['cookieVersion']))
		    $response['cookieVersion'] = $data['cookieVersion'];

        $response['expireTime'] = 30;
        if (!empty($data['expireTime']))
		    $response['expireTime'] = $data['expireTime'];

        $repoCookieToolContainer = $entityManager->getRepository(CookieToolContainer::class);
        $cookieToolContainer = $repoCookieToolContainer->findOneBy(['sourceId' => $modId]);
        if (empty($cookieToolContainer)) {
            $response['cookieTools'] = [];
        } else {
            $response['cookieTools'] = $cookieToolContainer->getElements();
        }

        $repoOtherScriptContainer = $entityManager->getRepository(OtherScriptContainer::class);
        $otherScriptContainer = $repoOtherScriptContainer->findOneBy(['sourceId' => $modId]);
        if (empty($otherScriptContainer)) {
            $response['otherScripts'] = [];
        } else {
            $response['otherScripts'] = $otherScriptContainer->getElements();
        }

		return $response;
	}

    /**
     * @param $cookieData
     * @param $data
     * @param $id
     * @return string
     */
	private function changeConsent(Database $database,$cookieData,$data,$id = null)
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
        if  (is_array($cookieData['cookieTools']) && is_array($cookieData['otherScripts']))
		    $otherCookieIds = array_merge($cookieData['cookieTools'],$cookieData['otherScripts']);
        else
            $otherCookieIds = [];

        if (!empty($otherCookieIds)) {
            foreach ($otherCookieIds as $cookieTool) {
                $cookieNames[] = $cookieTool->getCookieToolsName();
                $technicalName = $cookieTool->getCookieToolsTechnicalName();
                if (empty($technicalName))
                    $technicalName = 'kein Eintrag im Module';
                $cookieTechnicalName[] = $technicalName;
            }
        }

        $strQuery = "INSERT tl_consentDirectory %s";
		$stmt = $database->prepare($strQuery);
        if (empty($stmt))
            return $id;

        $domain = $_SERVER['SERVER_NAME'];
        $repo = new Repository($database);
        $set =
            [
                'ip' => $userInfo['ip'],
                'cookieToolsName' => implode(', ', $cookieNames),
                'cookieToolsTechnicalName' => implode(', ', $cookieTechnicalName),
                'date' => date('Y-m-d H:i'),
                'domain' => $domain,
                'url' => $userInfo['consentURL'],
                'pid' => $userInfo['cookieId'] ? $userInfo['cookieId'] : 1,
        ];
        $repo->executeStatement($strQuery, $set,[]);
        $data = $repo->findRow('SELECT id FROM tl_consentDirectory  ORDER BY `id` DESC LIMIT 1', [],[]);

        return $data['id'];

	}

    /**
     * @Route("/cookie/delete", name="cookie_delete")
     * @param array|null $cookieNotToDelete Cookies that should not be deleted
     * @return JsonResponse
     */
    public function deleteCookiesAction(array $cookieNotToDelete = null)
    {
        return self::deleteCookies($cookieNotToDelete);
    }

    /**
     * @param array|null $cookieNotToDelete Cookies that should not be deleted
     * @return JsonResponse
     */
    public static function deleteCookies(array $cookieNotToDelete = null) {
        ob_start();
        $cookiesSet = CookieController::unsetCookiesFromArray($cookieNotToDelete);

        //all possible subdomains
        $domain = $_SERVER['SERVER_NAME'];
        $subDomains = explode(".", $domain);
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
                    || $cookieSetTechnicalName == 'ISOTOPE_TEMP_CART'
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
     * @param Database $database
     * @param $modId
     * @return mixed
     */
    private function getTlCookieData(Database $database,$modId) {
        $repo = new Repository($database);
        $strQuery = "SELECT ipFormatSave,cookieVersion,expireTime FROM tl_ncoi_cookie WHERE pid = ?";
        return $repo->findRow($strQuery,[], [$modId]);
    }

    private static function unsetCookiesFromArray($cookieNotToDelete)
    {
        $cookiesSet = $_COOKIE;
        if (!empty($cookieNotToDelete)) {
            foreach ($cookiesSet as $cookieSetTechnicalName => $value) {
                foreach ($cookieNotToDelete as $cookie) {
                    $cookieToolsTechnicalName = $cookie->getCookieToolsTechnicalName();
                    if (strpos($cookieToolsTechnicalName,',')) {
                        $cookieToolsTechnicalName = explode(',',$cookieToolsTechnicalName);
                        foreach ($cookieToolsTechnicalName as $cookieToolTechnicalName) {
                            $cookieToolTechnicalName = trim($cookieToolTechnicalName);
                            unset($cookiesSet[$cookieToolTechnicalName]);
                        }
                    } else {
                        unset($cookiesSet[$cookieToolsTechnicalName]);
                    }
                }
            }
        }
        return $cookiesSet;
    }
}
