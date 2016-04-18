<?php


namespace ExchangeClient;


class ResponseMessage
{
	protected $payload;

	/**
	 * ResponseMessage constructor.
	 * @param $payload
	 */
	public function __construct($payload) {
		$this->payload = $payload;
	}

	public function success() {
		//print_r(sprintf("\nItemId : %s , ChangeKey : %s \n", $this->getItemId(), $this->getChangeKey()));
		return $this->payload->ResponseCode == "NoError";
	}

	public function getError(){
		return (!$this->success())?$this->payload->ResponseCode : null;
	}

	public function getItemId() {
		if(!isset($this->payload->Attachments)){
			return $this->payload->Items->Message->ItemId->Id;
		}else{
			return $this->payload->Attachments->FileAttachment->AttachmentId->RootItemId;
		}
	}

	public function getChangeKey() {
		if(!isset($this->payload->Attachments)){
			return $this->payload->Items->Message->ItemId->ChangeKey;
		}else{
			return $this->payload->Attachments->FileAttachment->AttachmentId->RootItemChangeKey;
		}
	}

	public function getRaw() {
		return $this->payload;
	}
}