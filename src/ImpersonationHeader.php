<?php

namespace ExchangeClient;

class ImpersonationHeader
{
    public $ConnectingSID;

    public function __construct($email)
    {
        $this->ConnectingSID->PrimarySmtpAddress = $email;
    }
}
