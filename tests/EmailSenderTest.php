<?php


use ExchangeClient\Client;

class EmailSenderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * EmailSenderTest constructor.
	 */
	public function __construct() {
		$dotenv = new Dotenv\Dotenv(__DIR__ . "/../", '.env.testing');
		$dotenv->load();
	}

	/** @test */
	public function can_send_email()
	{
		$email = getenv('TEST_EMAIL');

		$client = $this->makeClient();
		$success = $client->send_message($email,"test exchange","hello world");

		$this->assertNull($client->lastError);
		$this->assertTrue($success);
	}

	/** @test */
	public function can_send_to_multiple_recipients()
	{
		$client = $this->makeClient();
		$success = $client->send_message([getenv('TEST_EMAIL'), getenv('TEST_EMAIL2')], "test multiple recipients", "test sending");
		$this->assertTrue($success);
	}


	/**
	 * @return \ExchangeClient\ExchangeClient
	 */
	private function makeClient()
	{
		$user = getenv('USERNAME');
		$pass = getenv('PASSWORD');
		$wsdl = getenv('WSDL');


		$client = new \ExchangeClient\ExchangeClient($user, $pass, null, $wsdl);
		return $client;
	}

}
