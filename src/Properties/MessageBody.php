<?php
namespace ExchangeClient\Properties;

class MessageBody
{
	public $BodyType;
	public $_;

	public function __construct($Content, $BodyType = 'Text')
	{
		$this->BodyType = $BodyType;
		$this->_ = $Content;
	}

	public function setContent($content, $type)
	{
		$this->BodyType = $type;
		$this->_ = $content;
	}

}