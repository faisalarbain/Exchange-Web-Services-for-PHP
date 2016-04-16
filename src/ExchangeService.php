<?php


namespace ExchangeClient;


use SoapHeader;

class ExchangeService implements ExchangeServiceInterface
{
	/**
	 * @var NTLMSoapClient
	 */
	private $client;
	
	/**
	 * ExchangeService constructor.
	 * @param $user
	 * @param $pass
	 * @param $wsdl
	 * @param null $impersonate
	 * @throws Exception
	 */
	public function __construct($user , $pass , $wsdl, $impersonate = null) {
		$this->setup($user, $pass, $impersonate);

		$this->client = new NTLMSoapClient($wsdl, array(
			'trace' => 1,
			'exceptions' => true,
			'login' => $user,
			'password' => $pass,
			'connection_timeout'=> 600,
		));
		$this->teardown();
	}

	public function CreateItem($CreateItem)
	{
		return $this->client->CreateItem($CreateItem);
	}

	public function CreateAttachment($CreateAttachment)
	{
		return $this->client->CreateAttachment($CreateAttachment);
	}

	public function SendItem($CreateItem)
	{
		return $this->client->SendItem($CreateItem);
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

	private function setup($user, $pass, $impersonate){
		if ($impersonate != null) {
			$impheader = new ImpersonationHeader($impersonate);
			$header = new SoapHeader("http://schemas.microsoft.com/exchange/services/2006/messages", "ExchangeImpersonation", $impheader, false);
			$this->client->__setSoapHeaders($header);
		}

		ExchangeNTLMStream::setCredentials($user, $pass);

		stream_wrapper_unregister('http');
		stream_wrapper_unregister('https');

		if (!stream_wrapper_register('http', '\ExchangeClient\ExchangeNTLMStream')) {
			throw new Exception("Failed to register protocol");
		}

		if (!stream_wrapper_register('https', '\ExchangeClient\ExchangeNTLMStream')) {
			throw new Exception("Failed to register protocol");
		}
	}

	private function teardown() {
		stream_wrapper_restore('http');
		stream_wrapper_restore('https');
	}
}