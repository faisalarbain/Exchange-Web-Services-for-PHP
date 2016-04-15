<?php

namespace ExchangeClient;

use \Exception;
use \SoapHeader;
use \stdClass;

/**
 * Exchangeclient class.
 *
 * @author Riley Dutton
 * @author Rudolf Leermakers
 */
class ExchangeClient
{
    private $client = false;

    private $wsdl;
    private $user;
    private $pass;
    private $delegate;

    /**
     * The last error that occurred when communicating with the Exchange server.
     *
     * @var mixed
     * @access public
     */
    public $lastError;
    private $impersonate;

    /**
     * Initialize the class.
     *
     * @access public
     * @param string $user (the username of the mailbox account you want to use on the Exchange server)
     * @param string $pass (the password of the account)
     * @param string $delegate. (the email address you would like to access...the account you are logging in as must be an administrator account.
     * @param string $wsdl. (The path to the WSDL file. If you put them in the same directory as the Exchangeclient.php script, you can leave this alone. default: "Services.wsdl")
     * @return void
     */
    public function __construct($user, $pass, $delegate = null, $wsdl = "Services.wsdl")
    {
        $this->wsdl = $wsdl;
        $this->user = $user;
        $this->pass = $pass;
        $this->delegate = $delegate;
    }

    /**
     * Create the client object and connect to the EWS server, the the object doesn't already exist
     * @access private
     * @return void
     */
    private function connect()
    {
        if ($this->client !== false) {
            return;
        }

        $this->setup();

        $this->client = new NTLMSoapClient($this->wsdl, array(
            'trace' => 1,
            'exceptions' => true,
            'login' => $this->user,
            'password' => $this->pass,
            'connection_timeout'=> 600,
        ));

        $this->teardown();
    }

    /**
     * Create an event in the user's calendar. Does not currently support sending invitations to other users. Times must be passed as ISO date format.
     *
     * @access public
     * @param string $subject
     * @param string $start (start time of event in ISO date format e.g. "2010-09-21T16:00:00Z"
     * @param string $end (ISO date format)
     * @param string $location
     * @param bool $isallday. (default: false)
     * @return bool $success (true if the message was created, false if there was an error)
     */
    public function create_event($subject, $start, $end, $location, $isallday = false)
    {
        $this->connect();
        $this->setup();

        $CreateItem->SendMeetingInvitations = "SendToNone";
        $CreateItem->SavedItemFolderId->DistinguishedFolderId->Id = "calendar";
        if ($this->delegate != null) {
            $FindItem->SavedItemFolderId->DistinguishedFolderId->Mailbox->EmailAddress = $this->delegate;
        }
        $CreateItem->Items->CalendarItem->Subject = $subject;
        $CreateItem->Items->CalendarItem->Start = $start; #e.g. "2010-09-21T16:00:00Z"; # ISO date format. Z denotes UTC time
        $CreateItem->Items->CalendarItem->End = $end;
        $CreateItem->Items->CalendarItem->IsAllDayEvent = $isallday;
        $CreateItem->Items->CalendarItem->LegacyFreeBusyStatus = "Busy";
        $CreateItem->Items->CalendarItem->Location = $location;

        $response = $this->client->CreateItem($CreateItem);

        $this->teardown();

        if ($response->ResponseMessages->CreateItemResponseMessage->ResponseCode == "NoError") {
            return true;
        } else {
            $this->lastError = $response->ResponseMessages->CreateItemResponseMessage->ResponseCode;
            return false;
        }
    }

    public function get_events($start, $end)
    {
        $this->connect();
        $this->setup();

        $FindItem->Traversal = "Shallow";
        $FindItem->ItemShape->BaseShape = "IdOnly";
        $FindItem->ParentFolderIds->DistinguishedFolderId->Id = "calendar";

        if ($this->delegate != null) {
            $FindItem->ParentFolderIds->DistinguishedFolderId->Mailbox->EmailAddress = $this->delegate;
        }

        $FindItem->CalendarView->StartDate = $start;
        $FindItem->CalendarView->EndDate = $end;

        $response = $this->client->FindItem($FindItem);

        if ($response->ResponseMessages->FindItemResponseMessage->ResponseCode != "NoError") {
            $this->lastError = $response->ResponseMessages->FindItemResponseMessage->ResponseCode;
            return false;
        }

        $items = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem;

        $i = 0;
        $events = [];

        if (count($items) == 0) {
            return false;
        }
        //we didn't get anything back!

        if (!is_array($items)) //if we only returned one event, then it doesn't send it as an array, just as a single object. so put it into an array so that everything works as expected.
        {
            $items = array($items);
        }

        foreach ($items as $item) {
            $GetItem->ItemShape->BaseShape = "Default";
            $GetItem->ItemIds->ItemId = $item->ItemId;
            $response = $this->client->GetItem($GetItem);

            if ($response->ResponseMessages->GetItemResponseMessage->ResponseCode != "NoError") {
                $this->lastError = $response->ResponseMessages->GetItemResponseMessage->ResponseCode;
                return false;
            }

            $eventobj = $response->ResponseMessages->GetItemResponseMessage->Items->CalendarItem;

            $newevent = null;
            $newevent->id = $eventobj->ItemId->Id;
            $newevent->changekey = $eventobj->ItemId->ChangeKey;
            $newevent->subject = $eventobj->Subject;
            $newevent->start = strtotime($eventobj->Start);
            $newevent->end = strtotime($eventobj->End);
            $newevent->location = $eventobj->Location;

            $organizer = null;
            $organizer->name = $eventobj->Organizer->Mailbox->Name;
            $organizer->email = $eventobj->Organizer->Mailbox->EmailAddress;

            $people = [];
            $required = $eventobj->RequiredAttendees->Attendee;

            if (!is_array($required)) {
                $required = array($required);
            }

            foreach ($required as $r) {
                $o = null;
                $o->name = $r->Mailbox->Name;
                $o->email = $r->Mailbox->EmailAddress;
                $people[] = $o;
            }

            $newevent->organizer = $organizer;
            $newevent->people = $people;
            $newevent->allpeople = array_merge(array($organizer), $people);

            $events[] = $newevent;
        }

        $this->teardown();

        return $events;
    }

    /**
     * Get the messages for a mailbox.
     *
     * @access public
     * @param int $limit. (How many messages to get? default: 50)
     * @param bool $onlyunread. (Only get unread messages? default: false)
     * @param string $folder. (default: "inbox", other options include "sentitems")
     * @param bool $folderIdIsDistinguishedFolderId. (default: true, is $folder a DistinguishedFolderId or a simple FolderId)
     * @return array $messages (an array of objects representing the messages)
     */
    public function get_messages($limit = 50, $onlyunread = false, $folder = "inbox", $folderIdIsDistinguishedFolderId = true)
    {
        $this->connect();
        $this->setup();

        $FindItem = new stdClass();
        $FindItem->Traversal = "Shallow";

        $FindItem->ItemShape = new stdClass();
        $FindItem->ItemShape->BaseShape = "IdOnly";

        $FindItem->ParentFolderIds = new stdClass();

        if ($folderIdIsDistinguishedFolderId) {
            $FindItem->ParentFolderIds->DistinguishedFolderId = new stdClass();
            $FindItem->ParentFolderIds->DistinguishedFolderId->Id = $folder;
        } else {
            $FindItem->ParentFolderIds->FolderId = new stdClass();
            $FindItem->ParentFolderIds->FolderId->Id = $folder;
        }

        if (!is_null($this->delegate)) {
            $FindItem->ParentFolderIds->DistinguishedFolderId->Mailbox = new stdClass();
            $FindItem->ParentFolderIds->DistinguishedFolderId->Mailbox->EmailAddress = $this->delegate;
        }

        $response = $this->client->FindItem($FindItem);

        if ($response->ResponseMessages->FindItemResponseMessage->ResponseCode != "NoError") {
            $this->lastError = $response->ResponseMessages->FindItemResponseMessage->ResponseCode;
            return false;
        }

        // No email in that folder
        if (!$response->ResponseMessages->FindItemResponseMessage->RootFolder->TotalItemsInView) {
            return [];
        }

        $items = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Message;

        $messages = [];

        if (!is_array($items)) {
            // if we only returned one message, then it doesn't send it as an array, just as a single object.
            // so put it into an array so that everything works as expected.
            $items = [$items];
        }

        foreach ($items as $i => $item) {
            $GetItem = new stdClass();
            $GetItem->ItemShape = new stdClass();

            $GetItem->ItemShape->BaseShape = "Default";
            $GetItem->ItemShape->IncludeMimeContent = "true";

            $GetItem->ItemIds = new stdClass();
            $GetItem->ItemIds->ItemId = $item->ItemId;

            $response = $this->client->GetItem($GetItem);

            if ($response->ResponseMessages->GetItemResponseMessage->ResponseCode != "NoError") {
                $this->lastError = $response->ResponseMessages->GetItemResponseMessage->ResponseCode;
                return false;
            }

            $messageobj = $response->ResponseMessages->GetItemResponseMessage->Items->Message;

            if ($onlyunread && $messageobj->IsRead) {
                continue;
            }

            $newmessage = new stdClass();
            $newmessage->source = base64_decode($messageobj->MimeContent->_);
            $newmessage->bodytext = $messageobj->Body->_;
            $newmessage->bodytype = $messageobj->Body->BodyType;
            $newmessage->isread = $messageobj->IsRead;
            $newmessage->ItemId = $item->ItemId;
            $newmessage->from = $messageobj->From->Mailbox->EmailAddress;
            $newmessage->from_name = $messageobj->From->Mailbox->Name;

            $newmessage->to_recipients = [];

            if (isset($messageobj->ToRecipients)) {
                if (!is_array($messageobj->ToRecipients->Mailbox)) {
                    $messageobj->ToRecipients->Mailbox = array($messageobj->ToRecipients->Mailbox);
                }

                foreach ($messageobj->ToRecipients->Mailbox as $mailbox) {
                    $newmessage->to_recipients[] = $mailbox;
                }
            }

            $newmessage->cc_recipients = [];

            if (isset($messageobj->CcRecipients->Mailbox)) {
                if (!is_array($messageobj->CcRecipients->Mailbox)) {
                    $messageobj->CcRecipients->Mailbox = array($messageobj->CcRecipients->Mailbox);
                }

                foreach ($messageobj->CcRecipients->Mailbox as $mailbox) {
                    $newmessage->cc_recipients[] = $mailbox;
                }
            }

            $newmessage->time_sent = $messageobj->DateTimeSent;
            $newmessage->time_created = $messageobj->DateTimeCreated;
            $newmessage->subject = $messageobj->Subject;
            $newmessage->attachments = [];

            if ($messageobj->HasAttachments == 1) {
                if (property_exists($messageobj->Attachments, 'FileAttachment')) {
                    if (!is_array($messageobj->Attachments->FileAttachment)) {
                        $messageobj->Attachments->FileAttachment = array($messageobj->Attachments->FileAttachment);
                    }

                    foreach ($messageobj->Attachments->FileAttachment as $attachment) {
                        $newmessage->attachments[] = $this->get_attachment($attachment->AttachmentId);
                    }
                }
            }

            $messages[] = $newmessage;

            if (++$i >= $limit) {
                break;
            }
        }

        $this->teardown();

        return $messages;
    }

    private function get_attachment($AttachmentID)
    {
        $GetAttachment = new stdClass();
        $GetAttachment->AttachmentIds = new stdClass();

        $GetAttachment->AttachmentIds->AttachmentId = $AttachmentID;

        $response = $this->client->GetAttachment($GetAttachment);

        if ($response->ResponseMessages->GetAttachmentResponseMessage->ResponseCode != "NoError") {
            $this->lastError = $response->ResponseMessages->GetAttachmentResponseMessage->ResponseCode;
            return false;
        }

        $attachmentobj = $response->ResponseMessages->GetAttachmentResponseMessage->Attachments->FileAttachment;

        return $attachmentobj;
    }

    /**
     * Send a message through the Exchange server as the currently logged-in user.
     *
     * @access public
     * @param mixed $to (the email address or an array of email address to send the message to)
     * @param string $subject
     * @param string $content
     * @param string $bodytype. (default: "Text", "HTML" for HTML emails)
     * @param bool $saveinsent. (Save in the user's sent folder after sending? default: true)
     * @param bool $markasread. (Mark as read after sending? This currently does nothing. default: true)
     * @param array $attachments. (Array of files to attach. Each array item should be the full path to the file you want to attach)
     * @param mixed $cc (the email address or an array of email address of recipients to receive a carbon copy (cc) of the e-mail message)
     * @param mixed $bcc (the email address or an array of email address of recipients to receive a blind carbon copy (Bcc) of the e-mail message)
     * @return bool $success. (True if the message was sent, false if there was an error).
     */
    public function send_message($to, $subject, $content, $bodytype = "Text", $saveinsent = true, $markasread = true, $attachments = false, $cc = false, $bcc = false)
    {
        $this->connect();
        $this->setup();

        if ($attachments && !is_array($attachments)) {
            $attachments = false;
        }

        $CreateItem = $this->composeEmail($to, $subject, $content, $bodytype, $saveinsent, $markasread, $attachments, $cc, $bcc);

        $response = $this->client->CreateItem($CreateItem);

        if ($response->ResponseMessages->CreateItemResponseMessage->ResponseCode != "NoError") {
            $this->lastError = $response->ResponseMessages->CreateItemResponseMessage->ResponseCode;
            $this->teardown();
            return false;
        }

        if ($attachments && $response->ResponseMessages->CreateItemResponseMessage->ResponseCode == "NoError") {
            $itemId = $response->ResponseMessages->CreateItemResponseMessage->Items->Message->ItemId->Id;
            $itemChangeKey = $response->ResponseMessages->CreateItemResponseMessage->Items->Message->ItemId->ChangeKey;

            foreach ($attachments as $attachment) {
                if (!file_exists($attachment)) {
                    continue;
                }

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

                $CreateAttachment = new stdClass();
                $CreateAttachment->Attachments->FileAttachment = $FileAttachment;
                $CreateAttachment->ParentItemId->Id = $itemId;
                $CreateAttachment->ParentItemId->ChangeKey = $itemChangeKey;

                $response = $this->client->CreateAttachment($CreateAttachment);

                if ($response->ResponseMessages->CreateAttachmentResponseMessage->ResponseCode != "NoError") {
                    $this->lastError = $response->ResponseMessages->CreateAttachmentResponseMessage->ResponseCode;
                    return false;
                }

                $itemId = $response->ResponseMessages->CreateAttachmentResponseMessage->Attachments->FileAttachment->AttachmentId->RootItemId;
                $itemChangeKey = $response->ResponseMessages->CreateAttachmentResponseMessage->Attachments->FileAttachment->AttachmentId->RootItemChangeKey;
            }

            $SendItem = new stdClass();
            $SendItem->ItemIds->ItemId->Id = $itemId;
            $SendItem->ItemIds->ItemId->ChangeKey = $itemChangeKey;

            if ($saveinsent) {
                $SendItem->SaveItemToFolder = true;
                $SendItem->SavedItemFolderId->DistinguishedFolderId->Id = "sentitems";
            } else {
                $SendItem->SaveItemToFolder = false;
            }

            $response = $this->client->SendItem($SendItem);

            if ($response->ResponseMessages->SendItemResponseMessage->ResponseCode != "NoError") {
                $this->lastError = $response->ResponseMessages->SendItemResponseMessage->ResponseCode;
                $this->teardown();
                return false;
            }
        }

        $this->teardown();

        return true;
    }

    /**
     * Deletes a message in the mailbox of the current user.
     *
     * @access public
     * @param ItemId $ItemId (such as one returned by get_messages)
     * @param string $deletetype. (default: "HardDelete")
     * @return bool $success (true: message was deleted, false: message failed to delete)
     */
    public function delete_message($ItemId, $deletetype = "HardDelete")
    {
        $this->connect();
        $this->setup();

        $DeleteItem->DeleteType = $deletetype;
        $DeleteItem->ItemIds->ItemId = $ItemId;

        $response = $this->client->DeleteItem($DeleteItem);

        $this->teardown();

        if ($response->ResponseMessages->DeleteItemResponseMessage->ResponseCode == "NoError") {
            return true;
        } else {
            $this->lastError = $response->ResponseMessages->DeleteItemResponseMessage->ResponseCode;
            return false;
        }
    }

    /**
     * Moves a message to a different folder.
     *
     * @access public
     * @param ItemId $ItemId (such as one returned by get_messages, has Id and ChangeKey)
     * @return ItemID $ItemId The new ItemId (such as one returned by get_messages, has Id and ChangeKey)
     */
    public function move_message($ItemId, $FolderId)
    {
        $this->connect();
        $this->setup();

        $MoveItem = new stdClass();
        $MoveItem->ToFolderId = new stdClass();
        $MoveItem->ToFolderId->FolderId = new stdClass();
        $MoveItem->ItemIds = new stdClass();

        $MoveItem->ToFolderId->FolderId->Id = $FolderId;
        $MoveItem->ItemIds->ItemId = $ItemId;

        $response = $this->client->MoveItem($MoveItem);

        if ($response->ResponseMessages->MoveItemResponseMessage->ResponseCode == "NoError") {
            return $response->ResponseMessages->MoveItemResponseMessage->Items->Message->ItemId;
        } else {
            $this->lastError = $response->ResponseMessages->MoveItemResponseMessage->ResponseCode;
        }
    }

    public function getFolder($regex, $parent = 'inbox')
    {
        foreach ($this->get_subfolders($parent) as $folder) {
            if (preg_match(sprintf('#%s#', $regex), $folder->DisplayName)) {
                return $folder;
            }

        }

        return false;
    }

    /**
     * Get all subfolders of a single folder.
     *
     * @access public
     * @param string $ParentFolderId string representing the folder id of the parent folder, defaults to "inbox"
     * @param bool $Distinguished Defines whether or not its a distinguished folder name or not
     * @return array $response the response containing all the folders
     */
    public function get_subfolders($ParentFolderId = "inbox", $Distinguished = true)
    {
        $this->connect();
        $this->setup();

        $FolderItem = new stdClass();
        $FolderItem->FolderShape = new stdClass();
        $FolderItem->ParentFolderIds = new stdClass();

        $FolderItem->FolderShape->BaseShape = "Default";
        $FolderItem->Traversal = "Shallow";

        if ($Distinguished) {
            $FolderItem->ParentFolderIds->DistinguishedFolderId = new stdClass();
            $FolderItem->ParentFolderIds->DistinguishedFolderId->Id = $ParentFolderId;
        } else {
            $FolderItem->ParentFolderIds->FolderId = new stdClass();
            $FolderItem->ParentFolderIds->FolderId->Id = $ParentFolderId;
        }

        $response = $this->client->FindFolder($FolderItem);

        if ($response->ResponseMessages->FindFolderResponseMessage->ResponseCode == "NoError") {

            if (!$response->ResponseMessages->FindFolderResponseMessage->RootFolder->TotalItemsInView) {
                return [];
            }

            $folders = [];

            if (!is_array($response->ResponseMessages->FindFolderResponseMessage->RootFolder->Folders->Folder)) {
                $folders[] = $response->ResponseMessages->FindFolderResponseMessage->RootFolder->Folders->Folder;
            } else {
                $folders = $response->ResponseMessages->FindFolderResponseMessage->RootFolder->Folders->Folder;
            }

            return $folders;
        } else {
            $this->lastError = $response->ResponseMessages->FindFolderResponseMessage->ResponseCode;
        }
    }

    /**
     * Sets up stream handling. Internally used.
     *
     * @access private
     * @return void
     */
    private function setup()
    {
        if ($this->impersonate != null) {
            $impheader = new ImpersonationHeader($this->impersonate);
            $header = new SoapHeader("http://schemas.microsoft.com/exchange/services/2006/messages", "ExchangeImpersonation", $impheader, false);
            $this->client->__setSoapHeaders($header);
        }

        ExchangeNTLMStream::setCredentials($this->user, $this->pass);

        stream_wrapper_unregister('http');
        stream_wrapper_unregister('https');

        if (!stream_wrapper_register('http', '\ExchangeClient\ExchangeNTLMStream')) {
            throw new Exception("Failed to register protocol");
        }

        if (!stream_wrapper_register('https', '\ExchangeClient\ExchangeNTLMStream')) {
            throw new Exception("Failed to register protocol");
        }
    }

    /**
     * Tears down stream handling. Internally used.
     *
     * @access private
     * @return void
     */
    private function teardown()
    {
        stream_wrapper_restore('http');
        stream_wrapper_restore('https');
    }

    /**
     * @param $to
     * @param $subject
     * @param $content
     * @param $bodytype
     * @param $saveinsent
     * @param $markasread
     * @param $attachments
     * @param $cc
     * @param $bcc
     * @return mixed
     */
    public function composeEmail($to, $subject, $content, $bodytype = "Text", $saveinsent = true, $markasread = true, $attachments = false, $cc = false, $bcc = false)
    {
        $CreateItem = Email::compose();

        if ($attachments) {
            $CreateItem->MessageDisposition = "SaveOnly";
            $CreateItem->SavedItemFolderId->DistinguishedFolderId->Id = 'drafts';
        } else {
            if ($saveinsent) {
                $CreateItem->SavedItemFolderId->DistinguishedFolderId->Id = "sentitems";
            } else {
                $CreateItem->MessageDisposition = "SendOnly";
            }
        }

        $CreateItem->Items->Message->ItemClass = "IPM.Note";
        $CreateItem->Items->Message->Subject = $subject;
        $CreateItem->Items->Message->Body->BodyType = $bodytype;
        $CreateItem->Items->Message->Body->_ = $content;

        if(!is_array($to)) $to = [$to];

        $recipients = [];
        foreach ($to as $EmailAddress) {
            $Mailbox = new stdClass();
            $Mailbox->EmailAddress = $EmailAddress;
            $recipients[] = $Mailbox;
        }

        $CreateItem->Items->Message->ToRecipients->Mailbox = $recipients;


        if ($cc) {
            $CreateItem->Items->Message->CcRecipients = new stdClass();

            if(!is_array($cc)) $cc = [$cc];
                $recipients = [];
                foreach ($cc as $EmailAddress) {
                    $Mailbox = new stdClass();
                    $Mailbox->EmailAddress = $EmailAddress;
                    $recipients[] = $Mailbox;
                }

                $CreateItem->Items->Message->CcRecipients->Mailbox = $recipients;
        }

        if ($bcc) {
            $CreateItem->Items->Message->BccRecipients = new stdClass();
            if(!is_array($bcc)) $bcc = [$bcc];

            $recipients = [];
            foreach ($bcc as $EmailAddress) {
                $Mailbox = new stdClass();
                $Mailbox->EmailAddress = $EmailAddress;
                $recipients[] = $Mailbox;
            }

            $CreateItem->Items->Message->BccRecipients->Mailbox = $recipients;

        }

        if ($markasread) {
            $CreateItem->Items->Message->IsRead = "true";
        }

        if ($this->delegate != null) {
            $CreateItem->Items->Message->From->Mailbox->EmailAddress = $this->delegate;
            return $CreateItem;
        }
        return $CreateItem;
    }

    public function send(Email $mail) {
        $this->connect();
        $this->setup();
        return $this->client->CreateItem($mail);
    }
}
