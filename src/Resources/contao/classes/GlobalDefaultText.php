<?php


namespace Netzhirsch\CookieOptInBundle\Classes;


class GlobalDefaultText
{
    /** @var string $iFrameVideoDefault */
    private $iFrameVideoDefault;

    /** @var string $iFrameMapsDefault */
    private $iFrameMapsDefault;

    /** @var string $iFrameIFrameDefault */
    private $iFrameIFrameDefault;

    /** @var string $iFrameAlwaysLoadDefault */
    private $iFrameAlwaysLoadDefault;

    /** @var string $iFrameLoadDefault */
    private $iFrameLoadDefault;

    /**
     * GlobalDefaultText constructor.
     */
    public function __construct() {
        $this->iFrameVideoDefault = 'By loading this video you agree to the privacy policy of {{provider}}.';
        $this->iFrameMapsDefault = 'By loading this map you agree to the privacy policy of {{provider}}.';
        $this->iFrameIFrameDefault = 'By loading this iframe you agree to the privacy policy of {{provider}}.';
        $this->iFrameAlwaysLoadDefault = 'always load';
        $this->iFrameLoadDefault = 'load';
    }

    public function getAllAssoc(): array
    {
        return [
            'i_frame_videoDefault' => $this->getIFrameVideoDefault(),
            'i_frame_mapsDefault' => $this->getIFrameMapsDefault(),
            'i_frame_i_frameDefault' => $this->getIFrameIFrameDefault(),
            'i_frame_always_loadDefault' => $this->getIFrameAlwaysLoadDefault(),
            'i_frame_loadDefault' => $this->getIFrameLoadDefault()
        ];
    }

    /**
     * @return string
     */
    public function getIFrameVideoDefault(): string
    {
        return $this->iFrameVideoDefault;
    }

    /**
     * @param string $iFrameVideoDefault
     */
    public function setIFrameVideoDefault(string $iFrameVideoDefault)
    {
        $this->iFrameVideoDefault = $iFrameVideoDefault;
    }

    /**
     * @return string
     */
    public function getIFrameMapsDefault(): string
    {
        return $this->iFrameMapsDefault;
    }

    /**
     * @param string $iFrameMapsDefault
     */
    public function setIFrameMapsDefault(string $iFrameMapsDefault)
    {
        $this->iFrameMapsDefault = $iFrameMapsDefault;
    }

    /**
     * @return string
     */
    public function getIFrameIFrameDefault(): string
    {
        return $this->iFrameIFrameDefault;
    }

    /**
     * @param string $iFrameIFrameDefault
     */
    public function setIFrameIFrameDefault(string $iFrameIFrameDefault)
    {
        $this->iFrameIFrameDefault = $iFrameIFrameDefault;
    }

    /**
     * @return string
     */
    public function getIFrameAlwaysLoadDefault(): string
    {
        return $this->iFrameAlwaysLoadDefault;
    }

    /**
     * @param string $iFrameAlwaysLoadDefault
     */
    public function setIFrameAlwaysLoadDefault(string $iFrameAlwaysLoadDefault)
    {
        $this->iFrameAlwaysLoadDefault = $iFrameAlwaysLoadDefault;
    }

    /**
     * @return string
     */
    public function getIFrameLoadDefault(): string
    {
        return $this->iFrameLoadDefault;
    }

    /**
     * @param string $iFrameLoadDefault
     */
    public function setIFrameLoadDefault(string $iFrameLoadDefault)
    {
        $this->iFrameLoadDefault = $iFrameLoadDefault;
    }

}