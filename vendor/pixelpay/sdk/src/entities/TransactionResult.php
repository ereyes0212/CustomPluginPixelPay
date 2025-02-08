<?php

namespace PixelPay\Sdk\Entities;

use PixelPay\Sdk\Base\Helpers;
use PixelPay\Sdk\Base\Response;
use PixelPay\Sdk\Responses\ErrorResponse;
use PixelPay\Sdk\Responses\PaymentDeclinedResponse;
use PixelPay\Sdk\Responses\SuccessResponse;
use PixelPay\Sdk\Responses\TimeoutResponse;

class TransactionResult
{
	/**
	 * Transaction response type
	 *
	 * @var string
	 */
	public $transaction_type;

	/**
	 * Transaction redeemed points
	 *
	 * @var float
	 */
	public $transaction_redeemed_points;

	/**
	 * Approved amount on capture/sale
	 *
	 * @var float
	 */
	public $transaction_approved_amount;

	/**
	 * Initial or registered transaction amount
	 *
	 * @var float
	 */
	public $transaction_amount;

	/**
	 * Transaction AUTH reference code
	 *
	 * @var string
	 */
	public $transaction_auth;

	/**
	 * Transacction network terminal ID
	 *
	 * @var string
	 */
	public $transaction_terminal;

	/**
	 * Transaction network merchant ID
	 *
	 * @var string
	 */
	public $transaction_merchant;

	/**
	 * CVV2 result response code
	 *
	 * @var string
	 */
	public $response_cvn;

	/**
	 * Address verification code response
	 *
	 * @var string
	 */
	public $response_avs;

	/**
	 * CAVV network evaluation result code
	 *
	 * @var string
	 */
	public $response_cavv;

	/**
	 * Transaction identifier
	 *
	 * @var string
	 */
	public $transaction_id;

	/**
	 * Transaction STAN, proccesor transacction identifier or transaction reference
	 *
	 * @var string
	 */
	public $transaction_reference;

	/**
	 * Transaction result time
	 *
	 * @var string
	 */
	public $transaction_time;

	/**
	 * Transaction result date
	 *
	 * @var string
	 */
	public $transaction_date;

	/**
	 * Response is financial approved
	 *
	 * @var boolean
	 */
	public $response_approved;

	/**
	 * Response fatal not completed or excecution interrupted
	 *
	 * @var boolean
	 */
	public $response_incomplete;

	/**
	 * Proccesor response code
	 *
	 * @var string
	 */
	public $response_code;

	/**
	 * Network response time
	 *
	 * @var string
	 */
	public $response_time;

	/**
	 * Proccesor response message
	 *
	 * @var string
	 */
	public $response_reason;

	/**
	 * Transaction installment type
	 *
	 * @var string
	 */
	public $installment_type;

	/**
	 * Transaction installment value
	 *
	 * @var string
	 */
	public $installment_months;

	/**
	 * Payment unique identifier
	 *
	 * @var string
	 */
	public $payment_uuid;

	/**
	 * Payment integrity validation hash
	 *
	 * @var string
	 */
	public $payment_hash;

	/**
	 * Validate if response type is valid for parse
	 *
	 * @param Response $response
	 * @return boolean
	 */
	public static function validateResponse(Response $response): bool
	{
		return $response instanceof SuccessResponse
			|| $response instanceof PaymentDeclinedResponse
			|| $response instanceof ErrorResponse
			|| $response instanceof TimeoutResponse;
	}

	/**
	 * Convert success response to transaction entity
	 *
	 * @param Response $response
	 * @return TransactionResult
	 */
	public static function fromResponse(Response $response): TransactionResult
	{
		return Helpers::jsonToObject(json_encode($response->data), self::class);
	}
}
