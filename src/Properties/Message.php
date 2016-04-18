<?php
namespace ExchangeClient\Properties;

use ExchangeClient\Properties;

class Message
{
	public $ItemClass = 'IPM.Note';
	public $Subject = '';
	public $Body;
	public $ToRecipients;
	public $IsRead = 'true';

	public function __construct($Subject, $Body)
	{
		$this->Subject = $Subject;
		$this->Body = new MessageBody($Body);
		$this->ToRecipients = Properties\Property::Mailbox();
	}

	public function setRecipeints($type, $recipients)
	{
		$this->{ucfirst($type) . 'Recipients'} = Property::Mailbox(array_map(function ($email) {
			return Property::EmailAddress($email);
		}, $recipients));
	}

	public function setBody($content, $type)
	{
		$this->Body->setContent($content, $type);
	}


	public static function blank()
	{
		return new self('', '');
	}

	public function setFrom($email) {
		$this->From = Property::Mailbox(Property::EmailAddress($email));
	}

}