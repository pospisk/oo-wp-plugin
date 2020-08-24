<?php

declare(strict_types=1);

namespace onOffice\WPlugin\Utility;

class HTTPHeadersGeneric
	implements HTTPHeaders
{
	/**
	 * @codeCoverageIgnore
	 * @return bool
	 */
	public function headersSent(): bool
	{
		return headers_sent();
	}

	/**
	 * Will silently return an empty array if apache module was not loaded
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getRequestHeaders(): array
	{
		if (extension_loaded('apache')) {
			return apache_request_headers() ?: [];
		}
		return [];
	}

	/**
	 * @param int $responseCode
	 * @codeCoverageIgnore
	 */
	public function setHttpResponseCode(int $responseCode)
	{
		http_response_code($responseCode);
	}

	/**
	 * @param string $header
	 * @param bool $replace
	 * @param int $responseCode
	 */
	public function addHeader(string $header, bool $replace = true, int $responseCode = null)
	{
		if ($responseCode !== null) {
			// @codeCoverageIgnoreStart
			header($header, $replace, $responseCode);
			// @codeCoverageIgnoreEnd
		} else {
			header($header, $replace);
		}
	}
}