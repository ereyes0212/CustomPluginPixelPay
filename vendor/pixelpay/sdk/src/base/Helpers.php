<?php

namespace PixelPay\Sdk\Base;

use PixelPay\Sdk\Exceptions\IllegalStateException;

class Helpers
{
	/**
	 * Prevent implicit public contructor
	 */
	public function __construct()
	{
		throw new IllegalStateException('Utility class');
	}

	/**
	 * Serialize object to JSON string without empties
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function objectToJson($value): string
	{
		$array = json_decode(json_encode($value), true);

		return json_encode(array_filter($array, function ($item) {
			return !is_null($item);
		}));
	}

	/**
	 * Serialize JSON to object without empties
	 *
	 * @param string $json
	 * @param string $class
	 * @return mixed
	 */
	public static function jsonToObject(string $json, string $class)
	{
		$instance = new $class();
		$decoded = json_decode($json, true);

		foreach ($decoded as $key => $value) {
			if (property_exists($instance, $key)) {
				$instance->{$key} = $value;
			}
		}

		return $instance;
	}

	/**
	 * Helper to hash object by algorithm
	 *
	 * @param string $algorithm
	 * @param string $value
	 * @return string
	 */
	public static function hash(string $algorithm, string $value): string
	{
		switch ($algorithm) {
			case 'MD5':
				return hash('md5', $value);

			case 'SHA256':
				return hash('sha256', $value);

			default:
				return '';
		}
	}

	/**
	 * Trim a string/null value
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	public static function trimValue($value)
	{
		if (is_string($value)) {
			return trim($value);
		}

		return null;
	}

	/**
	 * Parse or nullify amount data
	 *
	 * @param mixed $amount
	 * @return string|null
	 */
	public static function parseAmount($amount)
	{
		if ($amount > 0) {
			return number_format($amount, 2, '.', '');
		}

		return null;
	}
}
