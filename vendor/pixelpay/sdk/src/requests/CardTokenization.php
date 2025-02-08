<?php

namespace PixelPay\Sdk\Requests;

use PixelPay\Sdk\Base\Helpers;
use PixelPay\Sdk\Base\RequestBehaviour;
use PixelPay\Sdk\Models\Billing;
use PixelPay\Sdk\Models\Card;

class CardTokenization extends RequestBehaviour
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
	 * @var string
	 */
	public $expire_month;

	/**
	 * Card expire year date (YYYY)
	 *
	 * @var string
	 */
	public $expire_year;

	/**
	 * Cardholder name
	 *
	 * @var string
	 */
	public $cardholder;

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

	/**
	 * Customer email
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Associate and mapping Card model properties to transaction
	 *
	 * @param Card $card
	 */
	public function setCard(Card $card)
	{
		$this->number = Helpers::trimValue($card->number);
		$this->cvv2 = $card->cvv2;

		if ($card->expire_month != 0) {
			$this->expire_month = sprintf('%02d', $card->expire_month);
		}

		if ($card->expire_year != 0) {
			$this->expire_year = "{$card->expire_year}";
		}

		$this->cardholder = Helpers::trimValue($card->cardholder);
	}

	/**
	 * Associate and mapping Billing model properties to transaction
	 *
	 * @param Billing $billing
	 */
	public function setBilling(Billing $billing)
	{
		$this->address = Helpers::trimValue($billing->address);
		$this->country = $billing->country;
		$this->state = $billing->state;
		$this->city = Helpers::trimValue($billing->city);
		$this->zip = $billing->zip;
		$this->phone = $billing->phone;
	}
}
