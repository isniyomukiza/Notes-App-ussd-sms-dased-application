<?php
require 'vendor/autoload.php';
//include_once 'util.php';
use AfricasTalking\SDK\AfricasTalking;
class Sms
{
    protected $phone;
    protected $AT;
    function __construct($phone)
    {
        $this->phone = $phone;
        $this->AT = new AfricasTalking("sandbox", "atsk_1cecc2a130b9b46a0b4d38423d873dc5905dee069be12201cfb0d5a586dc1c5544a001ca");
    }
    public function sendSMS($message, $recipient)
    {
        $sms = $this->AT->sms();
        $result = $sms->send([
            'username' => "sandbox",
            'to' => $recipient,
            'message' => $message,
            'from' => "Notes App LTD",
        ]);
        return $result;
    }
}
