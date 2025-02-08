<?php

namespace PixelPay\Sdk\Requests;

use PixelPay\Sdk\Base\RequestBehaviour;

class CaptureTransaction extends RequestBehaviour
{
	/**
	 * Payment UUID
	 *
	 * @var string
	 */
	public $payment_uuid;

	/**
	 * The total amount to capture, equal to or less than the authorized amount.
	 *
	 * @var string
	 */
	public $transaction_approved_amount;
}
