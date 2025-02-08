<?php

namespace PixelPay\Sdk\Requests;

use PixelPay\Sdk\Base\Helpers;

class SaleTransaction extends PaymentTransaction
{
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
	 * Transaction total points redeem amount
	 *
	 * @var string
	 */
	public $points_redeem_amount;

	/**
	 * Set Installment properties to transaction
	 *
	 * @param int $months
	 * @param string $type
	 */
	public function setInstallment(int $months, string $type)
	{
		$this->installment_months = strval($months);
		$this->installment_type = $type;
	}

	/**
	 * Set transaction points redeem amount
	 *
	 * @param float $amount
	 */
	public function withPointsRedeemAmount(float $amount)
	{
		$this->points_redeem_amount = Helpers::parseAmount($amount);
	}
}
