<?php


namespace ExchangeClient;


class CreateItem
{

	public static function blank() {
		$struct = [
			'MessageDisposition' => 'SendAndSaveCopy',
			'SavedItemFolderId' => [
				'DistinguishedFolderId' => [
					'Id' => '',
				]
			],
			'Items' => [
				'Message' => [
					'ItemClass' => '',
					'Subject' => '',
					'Body' => [
						'BodyType' => '',
						'_' => '',
					],
					'ToRecipients' => [
						'Mailbox' => [
							'EmailAddress' => ''
						]
					]
				]
			]
		];

		return json_decode(json_encode($struct));
	}
}