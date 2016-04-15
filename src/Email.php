<?php


namespace ExchangeClient;

use ExchangeClient\Properties\DistinguishedFolderId;
use ExchangeClient\Properties\Item;

class Email
{
	private $to = [];
	private $cc = [];
	private $bcc = [];

	const SEND_AND_SAVE_COPY = 'SendAndSaveCopy';
	public $MessageDisposition;
	public $SavedItemFolderId;
	public $Items;

	public function __construct() {
		$this->MessageDisposition = self::SEND_AND_SAVE_COPY;
		$this->SavedItemFolderId = DistinguishedFolderId::SendItems();
		$this->Items = Item::blank();
	}

	public static function compose() {
		return new self;
	}

	public function subject($subject) {
		$this->Items->Message->Subject = $subject;
		return $this;
	}

	public function body($content, $type = 'Text'){
		$this->Items->Message->setBody($content, $type);
		return $this;
	}

	public function to($email) {
		return $this->_addRecipient("To", $email);
	}

	public function cc($email){
		return $this->_addRecipient("Cc", $email);
	}

	public function bcc($email){
		return $this->_addRecipient("Bcc", $email);
	}

	private function _addRecipient($type, $email) {
		$_type = $type;
		$type = strtolower($type);
		if(!is_array($email)) $email = [$email];

		$this->$type = array_merge($this->$type, $email);
		$this->Items->Message->setRecipeints($_type, $this->$type);

		return $this;
	}
}


