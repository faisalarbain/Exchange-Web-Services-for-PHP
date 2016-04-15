<?php


namespace ExchangeClient\Properties;


class Item
{
	public $Message;
	/**
	 * Item constructor.
	 */
	public function __construct() {
		$this->Message = Message::blank();
	}

	public static function blank() {
		return new self;
	}
}