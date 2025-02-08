<?php

namespace PixelPay\Sdk\Resources;

use PixelPay\Sdk\Exceptions\IllegalStateException;

class Environment
{
	const LIVE = 'live';
	const TEST = 'test';
	const SANDBOX = 'sandbox';
	const STAGING = 'staging';

	/**
	 * Prevent implicit public contructor
	 */
	public function __construct()
	{
		throw new IllegalStateException('Utility class');
	}
}
