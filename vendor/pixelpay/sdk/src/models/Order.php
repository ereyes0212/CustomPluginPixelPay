<?php

namespace PixelPay\Sdk\Models;

class Order
{
	/**
	 * Order ID
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Order currency code alpha-3
	 *
	 * @var string
	 */
	public $currency;

	/**
	 * Order total amount
	 *
	 * @var float
	 */
	public $amount;

	/**
	 * Order total tax amount
	 *
	 * @var float
	 */
	public $tax_amount;

	/**
	 * Order total shipping amount
	 *
	 * @var float
	 */
	public $shipping_amount;

	/**
	 * Order summary of items or products
	 *
	 * @var array
	 */
	public $content;

	/**
	 * Order extra properties
	 *
	 * @var array
	 */
	public $extras;

	/**
	 * Order note or aditional instructions
	 *
	 * @var string
	 */
	public $note;

	/**
	 * Order calback webhook URL
	 *
	 * @var string
	 */
	public $callback_url;

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
	 * Initialize model
	 */
	public function __construct()
	{
		$this->content = [];
		$this->extras = [];
	}

	/**
	 * Add item to content list of products/items
	 *
	 * @param Item $item
	 * @return $this
	 */
	public function addItem(Item $item)
	{
		$this->content[] = $item;
		$this->totalize();

		return $this;
	}

	/**
	 * Add extra property to order
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return $this
	 */
	public function addExtra(string $key, $value)
	{
		$this->extras[$key] = "{$value}";

		return $this;
	}

	/**
	 * Totalize order amounts and items
	 *
	 * @return $this
	 */
	public function totalize()
	{

		if (!empty($this->content)) {
			$this->amount = 0.00;
			$this->tax_amount = 0.00;

			foreach ($this->content as $item) {
				$item->totalize();

				$this->amount += $item->total;
				$this->tax_amount += ($item->tax * $item->qty);
			}
		}

		return $this;
	}
}
