<?php

namespace PixelPay\Sdk\Models;

use PixelPay\Sdk\Base\Helpers;
use PixelPay\Sdk\Resources\Environment;

class Settings
{
	/**
	 * Merchant API auth key
	 *
	 * @var string
	 */
	public $auth_key;

	/**
	 * Merchant API auth hash (MD5 of secret key)
	 *
	 * @var string
	 */
	public $auth_hash;

	/**
	 * Merchant API platform auth user (SHA-512 of user email)
	 *
	 * @var string
	 */
	public $auth_user;

	/**
	 * Merchant API endpoint URL
	 *
	 * @var string
	 */
	public $endpoint;

	/**
	 * Merchant API environment
	 *
	 * @var string
	 */
	public $environment;

	/**
	 * Settings response messages language
	 *
	 * @var string
	 */
	public $lang;

	/**
	 * SDK identifier
	 *
	 * @var string
	 */
	public $sdk;

	/**
	 * Initialize service
	 */
	public function __construct()
	{
		$this->endpoint = 'https://pixelpay.app';
	}

	/**
	 * Setup API endpoint URL
	 *
	 * @param string $endpoint
	 */
	public function setupEndpoint(string $endpoint)
	{
		$this->endpoint = $endpoint;
	}

	/**
	 * Setup API credentials
	 *
	 * @param string $key
	 * @param string $hash
	 */
	public function setupCredentials(string $key, string $hash)
	{
		$this->auth_key = $key;
		$this->auth_hash = $hash;
	}

	/**
	 * Setup API platform user
	 *
	 * @param string $hash
	 */
	public function setupPlatformUser(string $hash)
	{
		$this->auth_user = $hash;
	}

	/**
	 * Setup API environment
	 *
	 * @param string $env
	 */
	public function setupEnvironment(string $env)
	{
		$this->environment = $env;
	}

	/**
	 * Setup defaults to Sandbox credentials
	 */
	public function setupSandbox()
	{
		$this->endpoint = 'https://pixel-pay.com';
		$this->auth_key = '1234567890';
		$this->auth_hash = Helpers::hash('MD5', '@s4ndb0x-abcd-1234-n1l4-p1x3l'); // MD5: @s4ndb0x-abcd-1234-n1l4-p1x3l

		$this->environment = Environment::SANDBOX;
	}

	/**
	 * Setup response messages language
	 *
	 * @param string $lang
	 */
	public function setupLanguage(string $lang)
	{
		$this->lang = $lang;
	}
}
