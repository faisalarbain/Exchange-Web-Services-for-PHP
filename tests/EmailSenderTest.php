<?php


use ExchangeClient\Client;
use ExchangeClient\Email;

class EmailSenderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * EmailSenderTest constructor.
	 */
	public function __construct() {
		try{
			$dotenv = new Dotenv\Dotenv(__DIR__ . "/../", '.env.testing');
			$dotenv->load();
		}catch (Exception $e){
			$this->printIntegrationTestRequirement();
			die;
		}
	}

	/** @test */
	public function can_compose_email()
	{
		$client = $this->makeClient();
		$mail = $client->composeEmail("john@email.com","composed email", "hello world");
		$mail2 = Email::compose();
		$mail2->to("john@email.com")->subject("composed email")->body("hello world");
		$this->assertPublicFieldsEquals($mail, $mail2);
		$this->assertEquals(Email::SEND_AND_SAVE_COPY, $mail2->MessageDisposition);
	}

	/** @test */
	public function can_compose_email_2()
	{
		$client = $this->makeClient();
		$mail = $client->composeEmail(["john@email.com", "jane@email.com"],"composed email", "hello world");
		$mail2 = Email::compose();
		$mail2->to(["john@email.com", "jane@email.com"])->subject("composed email")->body("hello world");

		$this->assertPublicFieldsEquals($mail, $mail2);
	}

	/** @test */
	public function can_compose_email_3()
	{
		$client = $this->makeClient();
		$mail = $client->composeEmail(["john@email.com", "jane@email.com"],"composed email", "hello world");

		$mail3 = Email::compose();
		$mail3->to("john@email.com")->to("jane@email.com")->subject("composed email")->body("hello world");
		$this->assertPublicFieldsEquals($mail, $mail3);
	}

	/** @test */
	public function can_compose_email_4()
	{
		$client = $this->makeClient();
		$mail = $client->composeEmail(["john@email.com", "jane@email.com","foo@email.com"],"composed email", "hello world");

		$mail3 = Email::compose();
		$mail3->to("john@email.com")->to("jane@email.com")->to("foo@email.com")->subject("composed email")->body("hello world");
		$this->assertPublicFieldsEquals($mail, $mail3);
	}

	/** @test */
	public function can_compose_email_with_cc_and_bcc()
	{
		$client = $this->makeClient();
		$cc = "jane@email.com";
		$bcc = "june@email.com";
		$mail = $client->composeEmail("john@email.com","composed email", "hello world", 'Text',true,true,false, $cc, $bcc);

		$mail2 = Email::compose();
		$mail2->to("john@email.com")
			->subject("composed email")
			->body("hello world")
			->cc($cc)->bcc($bcc);

		$this->assertPublicFieldsEquals($mail, $mail2);
	}

	/** @test */
	public function can_compose_email_with_cc_and_bcc_2()
	{
		$client = $this->makeClient();
		$cc = ["jane@email.com", "jane2@email.com"];
		$bcc = ["june@email.com","june2@email.com", "june3@email.com"];
		$mail = $client->composeEmail("john@email.com","composed email", "hello world", 'Text',true,true,false, $cc, $bcc);

		$mail2 = Email::compose();
		$mail2->to("john@email.com")
			->subject("composed email")
			->body("hello world")
			->cc($cc)->bcc($bcc);

		$this->assertPublicFieldsEquals($mail, $mail2);
	}

	/**
	 * @test
	 * @group  integration
	 */
	public function can_send_composed_email()
	{
		$mail = Email::compose();
		$mail->to(getenv('TEST_EMAIL'))->subject("send composed email")->body("hello from composed email");
		$client = $this->makeClient();
		$client->send($mail);

	}


	/**
	 * @test
	 * @group smoke
	 * */
	public function can_send_email()
	{
		$email = getenv('TEST_EMAIL');

		$client = $this->makeClient();
		$success = $client->send_message($email,"test exchange","hello world");

		$this->assertNull($client->lastError);
		$this->assertTrue($success);
	}

	/**
	 * @test
	 * @group smoke
	 * */
	public function can_send_to_multiple_recipients()
	{
		$client = $this->makeClient();
		$success = $client->send_message([getenv('TEST_EMAIL'), getenv('TEST_EMAIL2')], "test multiple recipients", "test sending");
		$this->assertTrue($success);
	}

	/**
	 * @test
	 * @group smoke
	 */
	public function can_send_file_with_attachment()
	{
		$attachments = [
			__DIR__ . '/sample.txt'
		];

		$client = $this->makeClient();
		$success = $client->send_message([getenv('TEST_EMAIL')], "test send email with attachment", "hello world", 'Text', false,true, $attachments);
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

	private function assertPublicFieldsEquals($mail, $mail2) {
		$mail = json_decode(json_encode($mail));
		$mail2 = json_decode(json_encode($mail2));
		$this->assertEquals($mail, $mail2);
	}

	private function printIntegrationTestRequirement() {
		echo <<<MSG
		
Integration test require actual credential information to run. 
The information should be place in `.env.testing` file.
to run integration test:
	
	phpunit --group integration
	phpunit --group smoke

/* ---- .env.testing ---- */

USERNAME=
PASSWORD=
WSDL=https://url-to-your-wsdl/Services.wsdl
TEST_EMAIL=
TEST_EMAIL2=


MSG;

	}


}
