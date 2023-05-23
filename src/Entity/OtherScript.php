<?php

namespace Netzhirsch\CookieOptInBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoGroupWidget\Entity\AbstractGroupElementEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'tl_ncoi_other_script')]
class OtherScript extends AbstractGroupElementEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer',options: ['unsigned' => true])]
    protected $id;

    #[ORM\Column(type: 'integer',options: ['unsigned' => true])]
    protected $position;

    #[ORM\ManyToOne(targetEntity: OtherScriptContainer::class, inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'parent', nullable: false)]
    protected $parent;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolsName;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsTechnicalName;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsProvider;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsPrivacyPolicyUrl;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolsUse;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolGroup;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolExpiredTime;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolsCode;

    public function getCookieToolsName(): ?string
    {
        return $this->cookieToolsName;
    }

    public function setCookieToolsName(?string $cookieToolsName): self
    {
        $this->cookieToolsName = $cookieToolsName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsTechnicalName()
    {
        return $this->cookieToolsTechnicalName;
    }

    /**
     * @param mixed $cookieToolsTechnicalName
     */
    public function setCookieToolsTechnicalName($cookieToolsTechnicalName): self
    {
        $this->cookieToolsTechnicalName = $cookieToolsTechnicalName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsTrackingId()
    {
        return $this->cookieToolsTrackingId;
    }

    /**
     * @param mixed $cookieToolsTrackingId
     */
    public function setCookieToolsTrackingId($cookieToolsTrackingId): self
    {
        $this->cookieToolsTrackingId = $cookieToolsTrackingId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsTrackingServerUrl()
    {
        return $this->cookieToolsTrackingServerUrl;
    }

    /**
     * @param mixed $cookieToolsTrackingServerUrl
     */
    public function setCookieToolsTrackingServerUrl($cookieToolsTrackingServerUrl): self
    {
        $this->cookieToolsTrackingServerUrl = $cookieToolsTrackingServerUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsProvider()
    {
        return $this->cookieToolsProvider;
    }

    /**
     * @param mixed $cookieToolsProvider
     */
    public function setCookieToolsProvider($cookieToolsProvider): self
    {
        $this->cookieToolsProvider = $cookieToolsProvider;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsPrivacyPolicyUrl()
    {
        return $this->cookieToolsPrivacyPolicyUrl;
    }

    /**
     * @param mixed $cookieToolsPrivacyPolicyUrl
     */
    public function setCookieToolsPrivacyPolicyUrl($cookieToolsPrivacyPolicyUrl): self
    {
        $this->cookieToolsPrivacyPolicyUrl = $cookieToolsPrivacyPolicyUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsUse()
    {
        return $this->cookieToolsUse;
    }

    /**
     * @param mixed $cookieToolsUse
     */
    public function setCookieToolsUse($cookieToolsUse): self
    {
        $this->cookieToolsUse = $cookieToolsUse;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolGroup()
    {
        return $this->cookieToolGroup;
    }

    /**
     * @param mixed $cookieToolGroup
     */
    public function setCookieToolGroup($cookieToolGroup): self
    {
        $this->cookieToolGroup = $cookieToolGroup;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolExpiredTime()
    {
        return $this->cookieToolExpiredTime;
    }

    /**
     * @param mixed $cookieToolExpiredTime
     */
    public function setCookieToolExpiredTime($cookieToolExpiredTime): self
    {
        $this->cookieToolExpiredTime = $cookieToolExpiredTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCookieToolsCode()
    {
        return $this->cookieToolsCode;
    }

    /**
     * @param mixed $cookieToolsCode
     */
    public function setCookieToolsCode($cookieToolsCode): self
    {
        $this->cookieToolsCode = $cookieToolsCode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIFrameBlockedText()
    {
        return $this->i_frame_blocked_text;
    }

    /**
     * @param mixed $i_frame_blocked_text
     */
    public function setIFrameBlockedText($i_frame_blocked_text): self
    {
        $this->i_frame_blocked_text = $i_frame_blocked_text;

        return $this;
    }
}
