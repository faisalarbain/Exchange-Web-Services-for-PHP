<?php


namespace ExchangeClient;


interface ReponseMessageInterface
{

	public function success();

	public function getError();

	public function getItemId();

	public function getChangeKey();

	public function getRaw();
}