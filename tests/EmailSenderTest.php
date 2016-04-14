<?php


use ExchangeClient\Client;

class EmailSenderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * EmailSenderTest constructor.
	 */
	public function __construct() {
		$dotenv = new Dotenv\Dotenv(__DIR__ . "/../");
		$dotenv->load();
	}

	/** @test */
	public function can_send_email()
	{
		$user = getenv('USERNAME');
		$pass = getenv('PASSWORD');
		$wsdl = getenv('WSDL');
		$email = getenv('TEST_EMAIL');


		$client = new \ExchangeClient\ExchangeClient($user, $pass, null, $wsdl);
		$success = $client->send_message($email,"test exchange","hello world");

		$this->assertNull($client->lastError);
		$this->assertTrue($success);
	}

}
