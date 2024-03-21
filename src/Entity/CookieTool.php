<?php

namespace Netzhirsch\CookieOptInBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoGroupWidget\Entity\AbstractGroupElementEntity;

#[ORM\Entity()]
#[ORM\Table(name: 'tl_ncoi_cookie_tool')]
class CookieTool extends AbstractGroupElementEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer',options: ['unsigned' => true])]
    protected $id;

    #[ORM\Column(type: 'integer',options: ['unsigned' => true])]
    protected $position;

    #[ORM\ManyToOne(targetEntity: CookieToolContainer::class, inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'parent', nullable: false)]
    protected $parent;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolsName;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsSelect;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsTechnicalName;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsTrackingId;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsTrackingServerUrl;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsProvider;

    #[ORM\Column(type: 'string',nullable: true)]
    private $cookieToolsPrivacyPolicyUrl;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolsUse;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolGroup;

    #[ORM\Column(type: 'boolean',options: ['default' => '0'])]
    private $googleConsentMode = false;

    #[ORM\Column(type: 'text',nullable: true)]
    private $cookieToolExpiredTime;

    #[ORM\Column(type: 'text',nullable: true)]
    private $i_frame_blocked_urls;

    #[ORM\Column(type: 'text',nullable: true)]
    private $i_frame_blocked_text;

    public static function createDefault(
        CookieToolContainer $cookieToolContainer,
        string $cookieToolsName,
        string $cookieToolsTechnicalName,
        string $cookieToolsUse,
        int $position,
    )
    {
        $cookieTool = new self();
        $cookieTool->setParent($cookieToolContainer);
        $cookieTool->setCookieToolsName($cookieToolsName);
        $cookieTool->setCookieToolsTechnicalName($cookieToolsTechnicalName);
        $cookieTool->setCookieToolsProvider('Contao');
        $cookieTool->setCookieToolExpiredTime(0);
        $cookieTool->setCookieToolsUse($cookieToolsUse);
        $cookieTool->setCookieToolsSelect('-');
        $cookieTool->setCookieToolGroup(1);
        $cookieTool->setPosition($position);
        return $cookieTool;
    }
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
    public function getCookieToolsSelect()
    {
        return $this->cookieToolsSelect;
    }

    /**
     * @param mixed $cookieToolsSelect
     */
    public function setCookieToolsSelect($cookieToolsSelect): self
    {
        $this->cookieToolsSelect = $cookieToolsSelect;

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
    public function getIFrameBlockedUrls()
    {
        return $this->i_frame_blocked_urls;
    }

    /**
     * @param mixed $i_frame_blocked_urls
     */
    public function setIFrameBlockedUrls($i_frame_blocked_urls): self
    {
        $this->i_frame_blocked_urls = $i_frame_blocked_urls;

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

    public function isGoogleConsentMode(): bool
    {
        return $this->googleConsentMode;
    }

    public function setGoogleConsentMode(bool $googleConsentMode): void
    {
        $this->googleConsentMode = $googleConsentMode;
    }
}
