<?php

namespace PixelPay\Sdk\Services;

use PixelPay\Sdk\Base\RequestBehaviour;
use PixelPay\Sdk\Base\Response;
use PixelPay\Sdk\Base\ServiceBehaviour;
use PixelPay\Sdk\Requests\CardTokenization;

class Tokenization extends ServiceBehaviour
{
	const BASE_CARD_PATH = 'api/v2/tokenization/card';

	/**
	 * Vault credit/debit card and obtain a token card identifier (T-* format)
	 *
	 * @param CardTokenization $card
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	public function vaultCard(CardTokenization $card): Response
	{
		return $this->post(Tokenization::BASE_CARD_PATH, $card);
	}

	/**
	 * Update credit/debit card by token card identifier
	 *
	 * @param string           $token
	 * @param CardTokenization $card
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	public function updateCard(string $token, CardTokenization $card): Response
	{
		return $this->put(Tokenization::BASE_CARD_PATH . "/{$token}", $card);
	}

	/**
	 * Show credit/debit card metadata by token card identifier
	 *
	 * @param string $token
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	public function showCard(string $token): Response
	{
		return $this->get(Tokenization::BASE_CARD_PATH . "/{$token}", new RequestBehaviour());
	}

	/**
	 * Show credit/debit cards metadata by tokens card identifier
	 *
	 * @param string[] $tokens
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	public function showCards(array $tokens): Response
	{
		return $this->get(Tokenization::BASE_CARD_PATH . '/' . implode(':', $tokens), new RequestBehaviour());
	}

	/**
	 * Delete credit/debit card metadata by token card identifier
	 *
	 * @param string $token
	 * @return Response
	 * @throws InvalidCredentialsException
	 */
	public function deleteCard(string $token): Response
	{
		return $this->delete(Tokenization::BASE_CARD_PATH . "/{$token}", new RequestBehaviour());
	}
}
