<?php


namespace Netzhirsch\CookieOptInBundle\Classes;


class CookieData
{
    /**
     * @var int $id
     */
    private $id;

    /**
     * @var int $version
     */
    private $version;

    /**
     * @var array $otherCookieIds
     */
    private $otherCookieIds;

    /**
     * @var bool $isJavaScript
     */
    private $isJavaScript;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return array
     */
    public function getOtherCookieIds()
    {
        return $this->otherCookieIds;
    }

    /**
     * @param array $otherCookieIds
     */
    public function setOtherCookieIds($otherCookieIds)
    {
        $this->otherCookieIds = $otherCookieIds;
    }

    /**
     * @return bool
     */
    public function isJavaScript()
    {
        return $this->isJavaScript;
    }

    /**
     * @param bool $isJavaScript
     */
    public function setIsJavaScript($isJavaScript)
    {
        $this->isJavaScript = $isJavaScript;
    }
}