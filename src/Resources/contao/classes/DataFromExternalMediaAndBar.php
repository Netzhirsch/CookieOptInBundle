<?php

namespace Netzhirsch\CookieOptInBundle\Classes;

class DataFromExternalMediaAndBar
{
    /** @var array $cookieIds */
    private $cookieIds;

    /** @var array $blockedIFrames */
    private $blockedIFrames;

    /** @var string $provider */
    private $provider;

    /** @var string $privacyPolicyLink */
    private $privacyPolicyLink;

    /** @var int $modId */
    private $modId;

    /** @var null|string $disclaimer */
    private $disclaimer;

    /** @var null|string */
    private $iFrameType;

    public function __construct()
    {
        $this->privacyPolicyLink = '';
    }

    /**
     * @return array
     */
    public function getCookieIds()
    {
        $cookieIds = $this->cookieIds;
        if (empty($cookieIds))
            $cookieIds = [];

        return $cookieIds;
    }

    public function addCookieId(int $cookieId) {
        $cookieIds = $this->getCookieIds();
        if (!in_array($cookieId,$cookieIds))
            $cookieIds[] = $cookieId;
        $this->cookieIds = $cookieIds;
    }

    /**
     * @return array
     */
    public function getBlockedIFrames()
    {
        $blockedIFrames = $this->blockedIFrames;
        if (empty($blockedIFrames))
            $blockedIFrames = [];

        return $blockedIFrames;
    }

    public function addBlockedIFrames(string $blockedIFrame) {
        $blockedIFrames = $this->getBlockedIFrames();
        $blockedIFrames[] = $blockedIFrame;
        $this->blockedIFrames = $blockedIFrames;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     */
    public function setProvider(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getPrivacyPolicyLink(): string
    {
        return $this->privacyPolicyLink;
    }

    /**
     * @param string $privacyPolicyLink
     */
    public function setPrivacyPolicyLink(string $privacyPolicyLink)
    {
        $this->privacyPolicyLink = $privacyPolicyLink;
    }

    /**
     * @return null|int
     */
    public function getModId()
    {
        return $this->modId;
    }

    /**
     * @param int $modId
     */
    public function setModId(int $modId)
    {
        $this->modId = $modId;
    }

    /**
     * @return string|null
     */
    public function getDisclaimer()
    {
        return $this->disclaimer;
    }

    /**
     * @param string|null $disclaimer
     */
    public function setDisclaimer($disclaimer)
    {
        $this->disclaimer = $disclaimer;
    }

    /**
     * @return string|null
     */
    public function getIFrameType()
    {
        return $this->iFrameType;
    }

    /**
     * @param string|null $iFrameType
     */
    public function setIFrameType($iFrameType)
    {
        $this->iFrameType = $iFrameType;
    }


}