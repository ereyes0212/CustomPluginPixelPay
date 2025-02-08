<?php

namespace PixelPay\Sdk\Entities;

use PixelPay\Sdk\Base\Helpers;
use PixelPay\Sdk\Base\Response;
use PixelPay\Sdk\Responses\SuccessResponse;

class CardResult
{
	/**
	 * Card status
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Card number masked
	 *
	 * @var string
	 */
	public $mask;

	/**
	 * Card network brand
	 *
	 * @var string
	 */
	public $network;

	/**
	 * Card type (debit/credit)
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Car bin number
	 *
	 * @var string
	 */
	public $bin;

	/**
	 * Card last 4 numbers
	 *
	 * @var string
	 */
	public $last;

	/**
	 * Card unique hash number
	 *
	 * @var string
	 */
	public $hash;

	/**
	 * Billing address
	 *
	 * @var string
	 */
	public $address;

	/**
	 * Billing country
	 *
	 * @var string
	 */
	public $country;

	/**
	 * Billing state
	 *
	 * @var string
	 */
	public $state;

	/**
	 * Billing city
	 *
	 * @var string
	 */
	public $city;

	/**
	 * Billing postal code
	 *
	 * @var string
	 */
	public $zip;

	/**
	 * Billing customer email
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Billing phone
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Validate if response type is valid for parse
	 *
	 * @param Response $response
	 * @return boolean
	 */
	public static function validateResponse(Response $response): bool
	{
		return $response instanceof SuccessResponse;
	}

	/**
	 * Convert success response to card entity
	 *
	 * @param SuccessResponse $response
	 * @return CardResult
	 */
	public static function fromResponse(SuccessResponse $response): CardResult
	{
		return Helpers::jsonToObject(json_encode($response->data), self::class);
	}
}
