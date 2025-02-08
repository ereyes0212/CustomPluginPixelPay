<?php

namespace PixelPay\Sdk\Models;

use PixelPay\Sdk\Base\Helpers;

class Card
{
	/**
	 * Card number or PAN
	 *
	 * @var string
	 */
	public $number;

	/**
	 * Card security code
	 *
	 * @var string
	 */
	public $cvv2;

	/**
	 * Card expire month date (MM)
	 *
	 * @var integer
	 */
	public $expire_month;

	/**
	 * Card expire year date (YYYY)
	 *
	 * @var integer
	 */
	public $expire_year;

	/**
	 * Cardholder name
	 *
	 * @var string
	 */
	public $cardholder;

	/**
	 * Get expire ISO format (YYMM)
	 *
	 * @return string
	 */
	public function getExpireFormat(): string
	{
		$year = ($this->expire_year != 0) ? "{$this->expire_year}" : '    ';
		$month = sprintf('%02d', $this->expire_month);

		return Helpers::trimValue(substr($year, -2) . $month);
	}
}
