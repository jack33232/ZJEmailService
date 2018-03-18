<?php
use ZJEmailService\EmailPostbox;
use ZJEmailService\Envelope;

define('BASE_DIR', dirname(__DIR__));
require_once(BASE_DIR . '/vendor/autoload.php');

EmailPostbox::register(
    array(
        'Host' => 'smtp.example.com',
        'Port' => 25
    )
);

$email_service = EmailPostbox::unlock('username@example.com', 'password123', 'From Name');
$envelope = new Envelope('Test', 'Some text as email body', 'HIGH');
$envelope->setTo(array('abc@example.com', 'ABC'));

$email_service->send($envelope);
