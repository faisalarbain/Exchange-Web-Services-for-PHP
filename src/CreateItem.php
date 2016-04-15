<?php


namespace ExchangeClient;


class CreateItem
{
	private $to = [];
	private $cc = [];
	private $bcc = [];

	public static function blank() {
		$struct = [
			'MessageDisposition' => 'SendAndSaveCopy',
			'SavedItemFolderId' => [
				'DistinguishedFolderId' => [
					'Id' => 'sentitems',
				]
			],
			'Items' => [
				'Message' => [
					'ItemClass' => 'IPM.Note',
					'Subject' => '',
					'Body' => [
						'BodyType' => '',
						'_' => '',
					],
					'ToRecipients' => [
						'Mailbox' => [
							'EmailAddress' => ''
						]
					],
					'IsRead' => 'true'
				]
			]
		];

		$struct = json_decode(json_encode($struct));
		$self = new self;
		foreach($struct as $field => $value){
			$self->$field = $value;
		}

		return $self;
	}

	public static function compose() {
		return self::blank();
	}

	public function subject($subject) {
		$this->Items->Message->Subject = $subject;
		return $this;
	}

	public function body($content, $type = 'Text'){
		$this->Items->Message->Body->BodyType = $type;
		$this->Items->Message->Body->_ = $content;
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

		if(!is_array($email)){
			$email = [$email];
		}

		$this->$type = array_merge($this->$type, $email);

		$recipients = [];
		foreach ($this->$type as $EmailAddress) {
			$Mailbox = (object)["EmailAddress" => $EmailAddress];
			$recipients[] = $Mailbox;
		}

		$this->Items->Message->{ $_type . "Recipients" } = (object) ["Mailbox" => $recipients];

		return $this;
	}

}