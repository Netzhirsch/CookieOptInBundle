<?php


namespace Netzhirsch\CookieOptInBundle\Resources\contao\Classes;


class LicenseAPIResponse
{
	/** @var bool $success */
	private $success;

	/** @var string $success */
	private $licenseKey;

	/** @var string $success */
	private $dateOfExpiry;

	public function __construct() {
		$this->setSuccess(false);
	}

	/**
	 * @return bool
	 */
	public function getSuccess() {
		return $this->success;
	}

	/**
	 * @param bool $success
	 */
	public function setSuccess($success) {
		$this->success = $success;
	}

	/**
	 * @return string
	 */
	public function getLicenseKey() {
		return $this->licenseKey;
	}

	/**
	 * @param string $licenseKey
	 */
	public function setLicenseKey($licenseKey) {
		$this->licenseKey = $licenseKey;
	}

	/**
	 * @format Y-m-d
	 * @return string
	 */
	public function getDateOfExpiry() {
		return $this->dateOfExpiry;
	}

	/**
	 * @format Y-m-d
	 *
	 * @param string $dateOfExpiry
	 */
	public function setDateOfExpiry($dateOfExpiry) {
		$this->dateOfExpiry = $dateOfExpiry;
	}


}