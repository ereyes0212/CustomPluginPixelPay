<?php

namespace PixelPay\Sdk\Requests;

use PixelPay\Sdk\Base\RequestBehaviour;

class StatusTransaction extends RequestBehaviour
{
	/**
	 * Payment UUID
	 *
	 * @var string
	 */
	public $payment_uuid;
}
