<?php


use ExchangeClient\Client;
use ExchangeClient\Email;
use ExchangeClient\ReponseMessageInterface;

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
	public function can_compose_email_for_single_recipient_same_as_legacy_send_message()
	{
		$service1 = $this->makeMockService();
		$this->makeClient($service1)->send_message("john@email.com","composed email", "hello world");

		$service2 = $this->makeMockService();
		$this->makeClient($service2)->send(
			Email::compose()
				->to("john@email.com")
				->subject("composed email")
				->body("hello world")
		);

		$this->assertEquals($service1->getJobs(), $service2->getJobs());
	}

	/** @test */
	public function can_compose_email_multiple_recipients()
	{
		$service1 = $this->makeMockService();
		$this->makeClient($service1)->send(
			Email::compose()
				->to("john@email.com")
				->to("jane@email.com")
				->subject("composed email")
				->body("hello world")
		);

		$service2 = $this->makeMockService();
		$this->makeClient($service2)->send(
			Email::compose()
				->to(["john@email.com", "jane@email.com"])
				->subject("composed email")
				->body("hello world")
		);

		$this->assertEquals($service1->getJobs(), $service2->getJobs());
	}

	/** @test */
	public function can_compose_email_multiple_recipients_2()
	{
		$service1 = $this->makeMockService();
		$this->makeClient($service1)->send_message(["john@email.com", "jane@email.com","foo@email.com"],"composed email", "hello world");

		$mail = Email::compose();
		$mail->to("john@email.com")->to("jane@email.com")->to("foo@email.com")->subject("composed email")->body("hello world");

		$service2 = $this->makeMockService();
		$this->makeClient($service2)->send($mail);

		$this->assertEquals($service1->getJobs(), $service2->getJobs());
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
	public function can_compose_email_with_multiple_cc_and_bcc()
	{
		$service1 = $this->makeMockService();
		$client = $this->makeClient($service1);
		$cc = ["jane@email.com", "jane2@email.com"];
		$bcc = ["june@email.com","june2@email.com", "june3@email.com"];
		$client->send_message("john@email.com","composed email", "hello world", 'Text',true,true,false, $cc, $bcc);

		$mail = Email::compose()->to("john@email.com")
			->subject("composed email")
			->body("hello world")
			->cc($cc)->bcc($bcc);

		$service2 = $this->makeMockService();
		$this->makeClient($service2)->send($mail);

		$this->assertEquals($service1->getJobs(), $service2->getJobs());
	}

	/**
	 * @test
	 * @group wip
	 */
	public function can_send_email_with_attachment()
	{
		$email_address = "john@email.com";

		$service1 = $this->makeMockService();
		$this->makeClient($service1)->send_message($email_address, "send with attachment", "hello world", "Text", true, true, [__DIR__ . '/sample.txt', __DIR__ . '/sample2.txt']);

		$mail = Email::compose()
			->to($email_address)
			->subject("send with attachment")
			->body("hello world")
			->attach(__DIR__ . '/sample.txt')
			->attach(__DIR__ . '/sample2.txt');
		$service2 = $this->makeMockService();
		$this->makeClient($service2)->send($mail);

		$this->assertEquals($service1->getJobs(), $service2->getJobs());
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
	 * @group integration
	 */
	public function can_send_file_with_attachment()
	{
		$attachments = [
			__DIR__ . '/sample.txt',
			__DIR__ . '/sample2.txt',
		];

		$client = $this->makeClient($this->makeLiveService());
		$success = $client->send_message([getenv('TEST_EMAIL')], "test send email with attachment", "hello world", 'Text', false,true, $attachments);
		$this->assertTrue($success);
	}

	private function makeLiveService(){
		$user = getenv('USERNAME');
		$pass = getenv('PASSWORD');
		$wsdl = getenv('WSDL');

		return new \ExchangeClient\ExchangeService($user, $pass, $wsdl);
	}

	/**
	 * @param \ExchangeClient\ExchangeServiceInterface $exchangeService
	 * @return \ExchangeClient\ExchangeClient
	 */
	private function makeClient(\ExchangeClient\ExchangeServiceInterface $exchangeService = NULL)
	{
		if(!$exchangeService){
			$exchangeService = $this->makeMockService();
		}
		return new \ExchangeClient\ExchangeClient($exchangeService);
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

	private function makeMockService() {
		return new MockExchangeService();
	}

}

class DummySuccessResponseMessage implements ReponseMessageInterface{
	protected $itemId = 0;
	protected $changeKey = 0;

	public function success()
	{
		return true;
	}

	public function getError()
	{
		return null;
	}

	public function getItemId()
	{
		return $this->itemId++;
	}

	public function getChangeKey()
	{
		return $this->changeKey++;
	}
}


class MockExchangeService implements \ExchangeClient\ExchangeServiceInterface{
	/**
	 * @var \ExchangeClient\ReponseMessageInterface
	 */
	protected  $response;
	protected  $jobs = [];
	/**
	 * MockExchangeService constructor.
	 */
	public function __construct() {
		$this->response = new DummySuccessResponseMessage();
	}

	public function getJobs(){
		return $this->jobs;
	}
	/**
	 * @param $CreateItem
	 * @return \ExchangeClient\ResponseMessage
	 */
	public function CreateItem($CreateItem)
	{
		$this->storeSentJob($CreateItem, "CreateItem");
		return $this->response;
	}

	public function FindItem($FindItem)
	{
		// TODO: Implement FindItem() method.
	}

	public function GetItem($GetItem)
	{
		// TODO: Implement GetItem() method.
	}

	public function GetAttachment($GetAttachment)
	{
		// TODO: Implement GetAttachment() method.
	}

	/**
	 * @param $CreateAttachment
	 * @return \ExchangeClient\ResponseMessage
	 */
	public function CreateAttachment($CreateAttachment)
	{
		$this->storeSentJob($CreateAttachment, "CreateAttachment");
		return $this->response;
	}

	/**
	 * @param $CreateItem
	 * @return \ExchangeClient\ResponseMessage
	 */
	public function SendItem($CreateItem)
	{
		$this->storeSentJob($CreateItem, "SendItem");
		return $this->response;
	}

	public function DeleteItem($DeleteItem)
	{
		// TODO: Implement DeleteItem() method.
	}

	public function MoveItem($MoveItem)
	{
		// TODO: Implement MoveItem() method.
	}

	public function FindFolder($FolderItem)
	{
		// TODO: Implement FindFolder() method.
	}

	/**
	 * @param $CreateItem
	 * @param $type
	 */
	private function storeSentJob($CreateItem, $type)
	{
		$this->jobs[] = ["type" => $type, json_decode(json_encode($CreateItem))];
	}
}