<?php


namespace ExchangeClient;


interface ResponseMessageInterface
{

	public function success();

	public function getError();

	public function getItemId();

	public function getChangeKey();

}