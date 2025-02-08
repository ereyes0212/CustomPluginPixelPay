<?php

namespace PixelPay\Sdk\Models;

class Billing
{
	/**
	 * Customer billing address
	 *
	 * @var string
	 */
	public $address;

	/**
	 * Customer billing country alpha-2 code (ISO 3166-1)
	 *
	 * @var string
	 */
	public $country;

	/**
	 * Customer billing state alpha code (ISO 3166-2)
	 *
	 * @var string
	 */
	public $state;

	/**
	 * Customer billing city
	 *
	 * @var string
	 */
	public $city;

	/**
	 * Customer billing postal code
	 *
	 * @var string
	 */
	public $zip;

	/**
	 * Customer billing phone
	 *
	 * @var string
	 */
	public $phone;
}
