<?php


namespace ExchangeClient;


class ResponseMessageException extends \Exception
{

	/**
	 * ResponseMessageException constructor.
	 * @param ResponseMessageInterface $resp
	 */
	public function __construct(ResponseMessageInterface $resp) {
		parent::__construct($resp->getError(),500);
	}
}