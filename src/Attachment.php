<?php


namespace ExchangeClient;


use stdClass;

class Attachment
{
	public $Attachments;
	public $ParentItemId;

	/**
	 * Attachment constructor.
	 * @param $attachment
	 * @param $itemId
	 * @param $itemChangeKey
	 */
	public function __construct($attachment, $itemId = 0, $itemChangeKey = 0) {
		$attachmentMime = "";

		$fileExtension = pathinfo($attachment, PATHINFO_EXTENSION);

		if ($fileExtension == "xls") {
			$attachmentMime = "application/vnd.ms-excel";
		}

		if (!strlen($attachmentMime)) {
			$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
			$attachmentMime = finfo_file($fileInfo, $attachment);
			finfo_close($fileInfo);
		}

		$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
		$attachmentMime = finfo_file($fileInfo, $attachment);
		finfo_close($fileInfo);

		$filename = pathinfo($attachment, PATHINFO_BASENAME);

		$FileAttachment = new stdClass();
		$FileAttachment->Content = file_get_contents($attachment);
		$FileAttachment->ContentType = $attachmentMime;
		$FileAttachment->Name = $filename;

		$this->Attachments = new stdClass();
		$this->Attachments->FileAttachment = $FileAttachment;
		$this->ParentItemId = new ItemId($itemId, $itemChangeKey);
	}

	public static function make($attachment, ReponseMessageInterface $resp) {
		return new self($attachment, $resp->getItemId(), $resp->getChangeKey());
	}
}

class ItemId{
	public $Id;
	public $ChangeKey;

	/**
	 * ItemId constructor.
	 * @param $Id
	 * @param $ChangeKey
	 */
	public function __construct($Id, $ChangeKey)
	{
		$this->Id = $Id;
		$this->ChangeKey = $ChangeKey;
	}


}