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
     * @var array $tools
     */
    private $tools;

    /**
     * @var array $external
     */
    private $external;

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
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param array $tools
     */
    public function setTools($tools)
    {
        $this->tools = $tools;
    }

    /**
     * @return array
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * @param array $external
     */
    public function setExternal($external)
    {
        $this->external = $external;
    }
}