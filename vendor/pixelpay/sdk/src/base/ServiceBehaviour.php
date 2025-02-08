<?php

namespace PixelPay\Sdk\Base;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use PixelPay\Sdk\Base\Response;
use PixelPay\Sdk\Exceptions\InvalidCredentialsException;
use PixelPay\Sdk\Models\Settings;
use PixelPay\Sdk\Responses\ErrorResponse;
use PixelPay\Sdk\Responses\FailureResponse;
use PixelPay\Sdk\Responses\InputErrorResponse;
use PixelPay\Sdk\Responses\NetworkFailureResponse;
use PixelPay\Sdk\Responses\NoAccessResponse;
use PixelPay\Sdk\Responses\NotFoundResponse;
use PixelPay\Sdk\Responses\PayloadResponse;
use PixelPay\Sdk\Responses\PaymentDeclinedResponse;
use PixelPay\Sdk\Responses\PreconditionalResponse;
use PixelPay\Sdk\Responses\SuccessResponse;
use PixelPay\Sdk\Responses\TimeoutResponse;
use Throwable;

class ServiceBehaviour
{
	/**
	 * Settings service model
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Guzzle client instance
	 *
	 * @var \GuzzleHttp\Client
	 */
	protected $http;

	/**
	 * Http client headers
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * Initialize service
	 *
	 * @param Settings $settings
	 */
	public function __construct(Settings $settings)
	{
		$this->settings = $settings;
		$this->http = new Client();
	}

	/**
	 * Verify settings variables and inherith values
	 *
	 * @param RequestBehaviour $request
	 */
	private function validateRequest(RequestBehaviour $request)
	{
		if ($this->settings->environment != null) {
			$request->env = $this->settings->environment;
		}

		if ($this->settings->lang != null) {
			$request->lang = $this->settings->lang;
		}

		if ($this->settings->sdk != null) {
			$request->from = $this->settings->sdk;
		}
	}

	/**
	 * Build HTTP request to API
	 *
	 * @param RequestBehaviour $request
	 * @return string
	 * @throws InvalidCredentialsException
	 */
	private function buildRequest(RequestBehaviour $request): string
	{
		if (empty($this->settings->auth_key) || empty($this->settings->auth_hash)) {
			throw new InvalidCredentialsException('The merchant credentials are not definied (key/hash).');
		}

		$php_version = phpversion();

		$this->headers = [
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'User-Agent' => "PixelPay-SDK/{$request->sdk_version} (PHP; rv{$php_version})",
			'x-auth-key' => $this->settings->auth_key,
			'x-auth-hash' => $this->settings->auth_hash,
		];

		if (!empty($this->settings->auth_user)) {
			$this->headers['x-auth-user'] = $this->settings->auth_user;
		}

		return $request->toJson();
	}

	/**
	 * Get API route
	 *
	 * @param string $route
	 * @return string
	 */
	private function getRoute(string $route): string
	{
		return "{$this->settings->endpoint}/{$route}";
	}

	/**
	 * Mapping and cast HTTP response
	 *
	 * @param string  $body
	 * @param integer $status
	 * @return Response
	 */
	private function parseResponse(string $body, int $status): Response
	{
		$bag = new Response();

		switch ($status) {
			case 200:
				$bag = Helpers::jsonToObject($body, SuccessResponse::class);
				break;

			case 202:
				$bag = Helpers::jsonToObject($body, PayloadResponse::class);
				break;

			case 400:
				$bag = Helpers::jsonToObject($body, ErrorResponse::class);
				break;

			case 401:
			case 403:
				$bag = Helpers::jsonToObject($body, NoAccessResponse::class);
				break;

			case 402:
				$bag = Helpers::jsonToObject($body, PaymentDeclinedResponse::class);
				break;

			case 404:
			case 405:
			case 406:
				$bag = Helpers::jsonToObject($body, NotFoundResponse::class);
				break;

			case 408:
				$bag = Helpers::jsonToObject($body, TimeoutResponse::class);
				break;

			case 412:
			case 418:
				$bag = Helpers::jsonToObject($body, PreconditionalResponse::class);
				break;

			case 422:
				$bag = Helpers::jsonToObject($body, InputErrorResponse::class);
				break;

			case 500:
				$bag = Helpers::jsonToObject($body, FailureResponse::class);
				break;

			default:
				if ($status > 500) {
					$bag = Helpers::jsonToObject($body, NetworkFailureResponse::class);
				}
				break;
		}

		if (empty($bag->getStatus())) {
			$bag->setStatus($status);
		}

		return $bag;
	}

	/**
	 * Proccess the exception to Response object
	 *
	 * @param Throwable $e
	 * @return Response
	 */
	private function exceptionResponse(Throwable $e): Response
	{
		$response = new FailureResponse();
		$response->success = false;
		$response->setStatus(520);
		$response->message = $e->getMessage();

		return $response;
	}

	/**
	 * API POST request
	 *
	 * @param string           $url
	 * @param RequestBehaviour $request
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	protected function post(string $url, RequestBehaviour $request): Response
	{
		$this->validateRequest($request);
		$body = $this->buildRequest($request);

		try {
			$result = $this->http->post($this->getRoute($url), [
				'headers' => $this->headers,
				'body' => $body,
			]);

			return $this->parseResponse($result->getBody(), $result->getStatusCode());
		} catch (ClientException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (ServerException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (Throwable $th) {
			return $this->exceptionResponse($th);
		}
	}

	/**
	 * API PUT request
	 *
	 * @param string           $url
	 * @param RequestBehaviour $request
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	protected function put(string $url, RequestBehaviour $request): Response
	{
		$this->validateRequest($request);
		$body = $this->buildRequest($request);

		try {
			$result = $this->http->put($this->getRoute($url), [
				'headers' => $this->headers,
				'body' => $body,
			]);

			return $this->parseResponse($result->getBody(), $result->getStatusCode());
		} catch (ClientException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (ServerException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (Throwable $th) {
			return $this->exceptionResponse($th);
		}
	}

	/**
	 * API DELETE request
	 *
	 * @param string           $url
	 * @param RequestBehaviour $request
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	protected function delete(string $url, RequestBehaviour $request): Response
	{
		$this->validateRequest($request);
		$body = $this->buildRequest($request);

		try {
			$result = $this->http->delete($this->getRoute($url), [
				'headers' => $this->headers,
				'body' => $body,
			]);

			return $this->parseResponse($result->getBody(), $result->getStatusCode());
		} catch (ClientException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (ServerException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (Throwable $th) {
			return $this->exceptionResponse($th);
		}
	}

	/**
	 * API GET request
	 *
	 * @param string           $url
	 * @param RequestBehaviour $request
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	protected function get(string $url, RequestBehaviour $request): Response
	{
		$this->validateRequest($request);

		$body = $this->buildRequest($request);

		try {
			$result = $this->http->get($this->getRoute($url), [
				'headers' => $this->headers,
				'body' => $body,
			]);

			return $this->parseResponse($result->getBody(), $result->getStatusCode());
		} catch (ClientException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (ServerException $ex) {
			return $this->parseResponse($ex->getResponse()->getBody(), $ex->getResponse()->getStatusCode());
		} catch (Throwable $th) {
			return $this->exceptionResponse($th);
		}
	}
}
