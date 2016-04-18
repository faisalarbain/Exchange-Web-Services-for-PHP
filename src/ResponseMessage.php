<?php


namespace ExchangeClient;


class ResponseMessage implements ResponseMessageInterface
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
}