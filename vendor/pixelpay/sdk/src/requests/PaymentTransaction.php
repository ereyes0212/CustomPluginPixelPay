<?php

namespace PixelPay\Sdk\Requests;

use PixelPay\Sdk\Base\Helpers;
use PixelPay\Sdk\Base\RequestBehaviour;
use PixelPay\Sdk\Models\Billing;
use PixelPay\Sdk\Models\Card;
use PixelPay\Sdk\Models\Order;

class PaymentTransaction extends RequestBehaviour
{
	/**
	 * Payment UUID
	 *
	 * @var string
	 */
	public $payment_uuid;

	/**
	 * Tokenized card identifier (T-* format)
	 *
	 * @var string
	 */
	public $card_token;

	/**
	 * Card number or PAN
	 *
	 * @var string
	 */
	public $card_number;

	/**
	 * Card security code
	 *
	 * @var string
	 */
	public $card_cvv;

	/**
	 * Card expire year/month date (YYMM)
	 *
	 * @var string
	 */
	public $card_expire;

	/**
	 * Cardholder name
	 *
	 * @var string
	 */
	public $card_holder;

	/**
	 * Customer billing address
	 *
	 * @var string
	 */
	public $billing_address;

	/**
	 * Customer billing country alpha-2 code (ISO 3166-1)
	 *
	 * @var string
	 */
	public $billing_country;

	/**
	 * Customer billing state alpha code (ISO 3166-2)
	 *
	 * @var string
	 */
	public $billing_state;

	/**
	 * Customer billing city
	 *
	 * @var string
	 */
	public $billing_city;

	/**
	 * Customer billing postal code
	 *
	 * @var string
	 */
	public $billing_zip;

	/**
	 * Customer billing phone
	 *
	 * @var string
	 */
	public $billing_phone;

	/**
	 * Order customer name
	 *
	 * @var string
	 */
	public $customer_name;

	/**
	 * Order customer email
	 *
	 * @var string
	 */
	public $customer_email;

	/**
	 * Order customer device fingerprint
	 *
	 * @var string
	 */
	public $customer_fingerprint;

	/**
	 * Order ID
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * Order currency code alpha-3
	 *
	 * @var string
	 */
	public $order_currency;

	/**
	 * Order total amount
	 *
	 * @var string
	 */
	public $order_amount;

	/**
	 * Order total tax amount
	 *
	 * @var string
	 */
	public $order_tax_amount;

	/**
	 * Order total shipping amount
	 *
	 * @var string
	 */
	public $order_shipping_amount;

	/**
	 * Order summary of items or products
	 *
	 * @var array
	 */
	public $order_content;

	/**
	 * Order extra properties
	 *
	 * @var array
	 */
	public $order_extras;

	/**
	 * Order note or aditional instructions
	 *
	 * @var string
	 */
	public $order_note;

	/**
	 * Order calback webhook URL
	 *
	 * @var string
	 */
	public $order_callback;

	/**
	 * Activate authentication request (3DS/EMV)
	 *
	 * @var boolean
	 */
	public $authentication_request = false;

	/**
	 * Authentication transaction identifier
	 *
	 * @var string
	 */
	public $authentication_identifier;

	/**
	 * Associate and mapping Card model properties to transaction
	 *
	 * @param Card $card
	 */
	public function setCard(Card $card)
	{
		$this->card_number = Helpers::trimValue($card->number);
		$this->card_cvv = $card->cvv2;
		$this->card_expire = $card->getExpireFormat();
		$this->card_holder = Helpers::trimValue($card->cardholder);
	}

	/**
	 * Associate and mapping CardToken model properties to transaction
	 *
	 * @param string $token
	 */
	public function setCardToken(String $token)
	{
		$this->card_token = $token;
	}

	/**
	 * Associate and mapping Billing model properties to transaction
	 *
	 * @param Billing $billing
	 */
	public function setBilling(Billing $billing)
	{
		$this->billing_address = Helpers::trimValue($billing->address);
		$this->billing_country = $billing->country;
		$this->billing_state = $billing->state;
		$this->billing_city = Helpers::trimValue($billing->city);
		$this->billing_zip = $billing->zip;
		$this->billing_phone = $billing->phone;
	}

	/**
	 * Associate and mapping Order model properties to transaction
	 *
	 * @param Order $order
	 */
	public function setOrder(Order $order)
	{
		$this->order_id = $order->id;
		$this->order_currency = $order->currency;
		$this->order_amount = Helpers::parseAmount($order->amount);
		$this->order_tax_amount = Helpers::parseAmount($order->tax_amount);
		$this->order_shipping_amount = Helpers::parseAmount($order->shipping_amount);
		$this->order_content = empty($order->content) ? null : json_decode(json_encode($order->content), true);
		$this->order_extras = empty($order->extras) ? null : $order->extras;
		$this->order_note = Helpers::trimValue($order->note);
		$this->order_callback = $order->callback_url;

		$this->customer_name = Helpers::trimValue($order->customer_name);
		$this->customer_email = $order->customer_email;
	}

	/**
	 * Enable 3DS/EMV authentication request
	 */
	public function withAuthenticationRequest()
	{
		$this->authentication_request = true;
	}
}
