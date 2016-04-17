<?php


namespace ExchangeClient;


interface ExchangeServiceInterface
{

	/**
	 * @param $CreateItem
	 * @return ResponseMessage
	 */
	public function CreateItem($CreateItem);

	public function FindItem($FindItem);

	public function GetItem($GetItem);

	public function GetAttachment($GetAttachment);

	public function CreateAttachment($CreateAttachment);

	public function SendItem($CreateItem);

	public function DeleteItem($DeleteItem);

	public function MoveItem($MoveItem);

	public function FindFolder($FolderItem);
}