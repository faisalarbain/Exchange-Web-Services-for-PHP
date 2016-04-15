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
					'CcRecipients' => [
						'Mailbox' => [
							'EmailAddress' => ''
						]
					],
					'BccRecipients' => [
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

	public function to($email) {
		if(is_array($email)){
			$this->to = array_merge($this->to, $email);
		}else{
			$this->to[] = $email;
		}

		if (count($this->to) > 1) {
			$recipients = [];
			foreach ($this->to as $EmailAddress) {
				$Mailbox = (object)["EmailAddress" => $EmailAddress];
				$recipients[] = $Mailbox;
			}

			$this->Items->Message->ToRecipients->Mailbox = $recipients;
		} else {
			$this->Items->Message->ToRecipients->Mailbox->EmailAddress = $this->to[0];
		}

		return $this;
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

	public function cc($email){
		if(is_array($email)){
			$this->cc = array_merge($this->cc, $email);
		}else{
			$this->cc[] = $email;
		}

		if (count($this->cc) > 1) {
			$recipients = [];
			foreach ($this->cc as $EmailAddress) {
				$Mailbox = (object)["EmailAddress" => $EmailAddress];
				$recipients[] = $Mailbox;
			}

			$this->Items->Message->CcRecipients->Mailbox = $recipients;
		} else {
			$this->Items->Message->CcRecipients->Mailbox->EmailAddress = $this->cc[0];
		}
		return $this;
	}

	public function bcc($email){
		if(is_array($email)){
			$this->bcc = array_merge($this->bcc, $email);
		}else{
			$this->bcc[] = $email;
		}

		if (count($this->bcc) > 1) {
			$recipients = [];
			foreach ($this->bcc as $EmailAddress) {
				$Mailbox = (object)["EmailAddress" => $EmailAddress];
				$recipients[] = $Mailbox;
			}

			$this->Items->Message->BccRecipients->Mailbox = $recipients;
		} else {
			$this->Items->Message->BccRecipients->Mailbox->EmailAddress = $this->bcc[0];
		}
		return $this;
	}

}