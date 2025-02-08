<?php

namespace PixelPay\Sdk\Base;

class Response
{
	/**
	 * HTTP response status code
	 *
	 * @var integer
	 */
	protected $status;

	/**
	 * Response status success
	 *
	 * @var boolean
	 */
	public $success;

	/**
	 * Response friendly message
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Response 'action to' format
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Response data payload
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Response input validation felds errors
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Define HTTP status code response
	 *
	 * @param int $status
	 */
	public function setStatus(int $status)
	{
		$this->status = $status;
	}

	/**
	 * Get HTTP status code
	 *
	 * @return integer
	 */
	public function getStatus(): int
	{
		return $this->status ?? 0;
	}

	/**
	 * Verify input has error
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function inputHasError($key): bool
	{
		if ($this->errors == null) {
			return false;
		}

		return array_key_exists($key, $this->errors);
	}

	/**
	 * Get data payload by key or dot notation
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getData(string $key)
	{
		if ($this->data == null) {
			return null;
		}

		return dot($this->data)->get($key);
	}

	/**
	 * Serialize object to JSON string
	 *
	 * @return string
	 */
	public function toJson(): string
	{
		return Helpers::objectToJson($this);
	}
}
