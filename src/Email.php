<?php


namespace ExchangeClient;

use ExchangeClient\Properties\DistinguishedFolderId;
use ExchangeClient\Properties\Item;

class Email
{
	private $attachments = [];
	private $to = [];
	private $cc = [];
	private $bcc = [];

	const SEND_AND_SAVE_COPY = 'SendAndSaveCopy';
	const SAVE_ONLY = 'SaveOnly';

	public $MessageDisposition;
	public $SavedItemFolderId;
	public $Items;
	public $SaveItemToFolder = true;

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
		return $this->_addRecipient("to", $email);
	}

	public function cc($email){
		return $this->_addRecipient("cc", $email);
	}

	public function bcc($email){
		return $this->_addRecipient("bcc", $email);
	}

	private function _addRecipient($type, $email) {
		if(!is_array($email)) $email = [$email];

		$this->$type = array_merge($this->$type, $email);
		$this->Items->Message->setRecipeints($type, $this->$type);

		return $this;
	}

	public function attach($attachment){
		if(!is_array($attachment)){
			$attachment = [$attachment];
		}

		$this->attachments = array_merge($this->attachments, $attachment);
		return $this;
	}

	public function hasAttachment() {
		return !empty($this->attachments);
	}

	public function getDraft() {
		/** @var Email $clone */
		$clone = clone $this;
		$clone->MessageDisposition = self::SAVE_ONLY;
		$clone->SavedItemFolderId = DistinguishedFolderId::Drafts();
		$clone->SaveItemToFolder = false;
		return $clone;
	}

	public function getAttachments() {
		return $this->attachments;
	}

	public function getSendItem($itemId = 0, $itemChangeKey = 0) {
		$CreateItem = (object)[
			"ItemIds" => (object)[
				"ItemId" => (object)[
					"Id" => '',
					"ChangeKey" => '',
				]
			],
		];

		$CreateItem->ItemIds->ItemId->Id = $itemId;
		$CreateItem->ItemIds->ItemId->ChangeKey = $itemChangeKey;
		$CreateItem->SaveItemToFolder = $this->SaveItemToFolder;
		$CreateItem->SavedItemFolderId = $this->SavedItemFolderId;

		return $CreateItem;
	}

	public function from($email) {
		$this->Items->Message->setFrom($email);
		return $this;
	}
}


