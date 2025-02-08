<?php

namespace PixelPay\Sdk\Base;

use Composer\InstalledVersions;

class RequestBehaviour
{
	/**
	 * Environment identifier (live|test|sandbox)
	 *
	 * @var string
	 */
	public $env;

	/**
	 * Transaction response messages language
	 *
	 * @var string
	 */
	public $lang;

	/**
	 * SDK identifier type
	 *
	 * @var string
	 */
	public $from;

	/**
	 * SDK version
	 *
	 * @var string
	 */
	public $sdk_version;

	/**
	 * Initialize request
	 */
	public function __construct()
	{
		$this->lang = substr(setlocale(LC_ALL, 0) ?? '', 0, 2);
		$this->from = 'sdk-php';

		if ($this->lang != 'es' && $this->lang != 'en') {
			$this->lang = 'es';
		}

		$this->sdk_version = InstalledVersions::getVersion('pixelpay/sdk') ?? 'unreleased';
	}

	/**
	 * Serialize object to JSON string
	 *
	 * @return string
	 */
	public function toJson(): string
	{
		return Helpers::objectToJson($this);
	}
}
