<?php
namespace ExchangeClient\Properties;

class DistinguishedFolderId
{
	public $Id;

	public function __construct($id)
	{
		$this->Id = $id;
	}

	private static function get($id)
	{
		return (object)['DistinguishedFolderId' => new self($id)];
	}

	public static function SendItems()
	{
		return self::get("sentitems");
	}

	public static function Drafts() {
		return self::get('drafts');
	}
}