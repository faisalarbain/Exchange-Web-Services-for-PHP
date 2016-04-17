<?php


namespace ExchangeClient;


class ResponseMessage
{
	private $payload;

	/**
	 * ResponseMessage constructor.
	 * @param $payload
	 */
	public function __construct($payload) {
		$this->payload = $payload;
	}

	public function success() {
		return $this->payload->ResponseCode == "NoError";
	}

	public function getError(){
		return (!$this->success())?$this->payload->ResponseCode : null;
	}

	public function getItemId() {
		return $this->payload->Items->Message->ItemId->Id;
	}

	public function getChangeKey() {
		return $this->payload->Items->Message->ItemId->ChangeKey;
	}
}