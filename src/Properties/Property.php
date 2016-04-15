<?php
namespace ExchangeClient\Properties;

class Property
{
	public static function get($key, $value)
	{
		return (object)[$key => $value];
	}

	public static function EmailAddress($EmailAddress)
	{
		return self::get('EmailAddress', $EmailAddress);
	}

	public static function Mailbox($recipients = null)
	{
		return self::get('Mailbox', $recipients);
	}
}