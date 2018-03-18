# About ZJEmailService

> This is a library combining the powerful ["PHPMailer"](https://github.com/PHPMailer/PHPMailer) and semantic Classes to help you get a easier use experience when try to send emails in your PHP applications.

## Dependency
```json
{
  "php": ">=5.5.0",
  "phpmailer/phpmailer": "~6.0",
  "psr/log": "~1.0"
}
```

## Usage

>Register Email Postbox

Before using a email postbox, it is necessary to register one by giving a setting array (Refer to PHPMailer Doc), an  optional name of the postbox and an optional PSR logger as a debugger. By default, the name of the postbox is "**default**". As for PSR logger, here is a recommended library: [KLogger](https://github.com/jack33232/KLogger). 

```php
use ZJEmailService\EmailPostbox;
use Katzgrau\KLogger\Logger;

// All possible settings, please refer to PHPMailer Doc
$postbox_setting = [
  'Host' => 'example.exchange.com',
  'Port' => 25,
  'ContentType' => 'text/html',
  'SMTPAuth' => true,
  'SMTPSecure' => 'tls',
  'CharSet' => 'UTF-8',
  'Encoding' => 'base64',
  'SMTPOptions' => array(),
  'Timeout' => 300,
  'SMTPDebug' => 0,
  'Debugoutput' => 'error_log' // only works when no debugger assigned
];
// Postbox name
$postbox_name = 'example';
// PSR logger
$debugger = new Logger(__DIR__.'/logs');
EmailPostbox::register($postbox_setting, $postbox_name, $debugger);
```

>Unlock the postbox

After register a postbox, you should use a username & password of that SMTP to unlock that postbox. Then you can use an instance of postbox to send emails.

```php
use ZJEmailService\EmailPostbox;

$username = 'username@emample.com';
$password = 'password123';
$from = 'Username';
$postbox_name = 'example';
// The postbox is a singleton
$postbox = EmailPostbox::unlock($username, $password, $from, $postbox_name);
```

>Compose email to an **Envelope** object

Every email in ZJEmailService library is transformed into a **Envelope** object. An **Envelope** can be set the content, subject, to, cc, bcc, attachments and so on. The object is array accessing. 

```php
use ZJEmailService\Envelope;

// Third parameter is the priority of the email
$envelope = new Envelope('Test', 'Some text as email body', 'HIGH');
$envelope->setTo(['to@example.com', 'To Name']);

$postbox->send($envelope);
```