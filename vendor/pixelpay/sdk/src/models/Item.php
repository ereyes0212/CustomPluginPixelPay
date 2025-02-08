<?php

namespace PixelPay\Sdk\Models;

class Item
{
	/**
	 * Item identifier code or UPC/EAN
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Item product title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Item per unit price
	 *
	 * @var float
	 */
	public $price;

	/**
	 * Item quantity
	 *
	 * @var integer
	 */
	public $qty;

	/**
	 * Item tax amount per unit
	 *
	 * @var float
	 */
	public $tax;

	/**
	 * Item total value
	 *
	 * @var float
	 */
	public $total;

	/**
	 * Initialize model
	 */
	public function __construct()
	{
		$this->price = 0.00;
		$this->qty = 1;
		$this->tax = 0.00;
		$this->total = 0.00;
	}

	/**
	 * Totalize item price by quantity
	 *
	 * @return $this
	 */
	public function totalize()
	{
		$this->total = $this->price * $this->qty;

		return $this;
	}
}
