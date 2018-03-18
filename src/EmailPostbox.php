<?php
namespace ZJEmailService;

use InvalidArgumentException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Log\LoggerInterface;

class EmailPostbox
{
    const DEFAULT_POSTBOX = 'default';

    protected static $postboxSettingFields = array(
        'Host', 'Port', 'From', 'FromName', 'ContentType', 'SMTPAuth',
        'SMTPSecure', 'CharSet', 'Encoding', 'SMTPOptions', 'Timeout',
        'SMTPDebug', 'Debugoutput'
    );
    protected static $postboxDefaultSetting = array(
        'Port' => 25,
        'ContentType' => 'text/html',
        'SMTPAuth' => true,
        'SMTPSecure' => 'tls',
        'CharSet' => 'UTF-8',
        'Encoding' => 'base64',
        'SMTPOptions' => array(),
        'Timeout' => 300,
        'SMTPDebug' => 0,
        'Debugoutput' => 'error_log'
    );

    protected static $postServiceErrorMap = array(
        0 => 100,
        1 => 102,
        2 => 500
    );

    protected static $postboxRegistry = array();
    protected static $workers = array();

    protected $postService = null;
    protected $id;
    protected $username;
    protected $password;
    protected $from;

    protected function __construct($id, $username, $password, $from)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->from = false;

        $username_is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
        if (is_string($from)) {
            $from_is_email = filter_var($from, FILTER_VALIDATE_EMAIL);
            if ($from_is_email) {
                $this->from = array($from, '');
            } elseif ($username_is_email && $from !== '') {
                $this->from = array($username, $from);
            }
        } elseif (is_array($from)) {
            $this->from = $from;
        }
    }

    public static function register(array $options, $id = '', LoggerInterface $debugger = null)
    {
        if (trim($id) == '') {
            $id = static::DEFAULT_POSTBOX;
        }

        // Host is required
        if (!isset($options['Host'])) {
            throw new InvalidArgumentException('SMTP must have a Host value.');
        }

        $postbox_setting = array_intersect_key(
            array_replace_recursive(static::$postboxDefaultSetting, $options),
            array_flip(static::$postboxSettingFields)
        );

        if (!is_null($debugger)) {
            $postbox_setting['Debugoutput'] = $debugger;
        }

        static::$postboxRegistry[$id] = $postbox_setting;
    }

    public static function unlock($username, $password, $from = '', $id = '')
    {
        if (trim($id) == '') {
            $id = static::DEFAULT_POSTBOX;
        }

        if (!isset(static::$postboxRegistry[$id])) {
            throw new InvalidArgumentException('Postbox ' . $id . ' not registered.');
        }

        if (isset(static::$workers[$id])) {
            return static::$workers[$id];
        } else {
            return static::$workers[$id] = new static($id, $username, $password, $from);
        }
    }

    public static function close($id = '')
    {
        if (trim($id) == '') {
            $id = static::DEFAULT_POSTBOX;
        }
        unset(static::$workers[$id]);
    }

    public function send(Envelope &$envelope, $throw_exception = false)
    {
        if (!$envelope instanceof Envelope) {
            throw new MailException('Invalid envelope type provided.');
        }

        $result = array(
            'status_code' => 0,
            'reason_phrase' => ''
        );

        if (is_null($this->postService)) {
            $this->initPostService();
        }

        try {
            $this->bootPostService($envelope);
            $result['status_code'] = 200;
            $result['reason_phrase'] = 'OK';
        } catch (PHPMailerException $e) {
            $result['status_code'] = static::$postServiceErrorMap[$e->getCode()];
            $result['reason_phrase'] = $e->getMessage();
        }

        $this->resetPostService();
        // Unset the envelope to save memory
        $envelope = null;

        if ($throw_exception && $result['status_code'] !== 200) {
            throw new MailException($result['reason_phrase'], $result['status_code']);
        }

        return $result;
    }

    protected function initPostService()
    {
        $post_service = new PHPMailer(true);
        $post_service->IsSMTP();

        $id = $this->id;
        $postbox_setting = static::$postboxRegistry[$id];
        foreach ($postbox_setting as $field => $value) {
            $post_service->$field = $value;
        }

        $post_service->Username = $this->username;
        $post_service->Password = $this->password;

        return $this->postService = $post_service;
    }

    protected function bootPostService($envelope)
    {
        $post_service = $this->postService;
        // Username & Password & From name
        if (!empty($envelope['from'])) {
            $from = $envelope['from'];
        } elseif (!empty($this->from)) {
            $from = $this->from;
        } else {
            throw new InvalidArgumentException('Missed From Info.');
        }

        call_user_func_array(
            array($post_service, 'setFrom'),
            $from
        );
        // Priority
        switch ($envelope['priority']) {
            case 'HIGH':
                $post_service->Priority = 1;
                $post_service->addCustomHeader('X-MSMail-Priority: High');
                $post_service->addCustomHeader('Importance: High');
                break;
            case 'LOW':
                $post_service->Priority = 5;
                $post_service->addCustomHeader('X-MSMail-Priority: Low');
                $post_service->addCustomHeader('Importance: Low');
                break;
            default:
                $post_service->Priority = 3;
                $post_service->addCustomHeader('X-MSMail-Priority: Normal');
                $post_service->addCustomHeader('Importance: Normal');
                break;
        }

        // Body & Subject
        $post_service->Body = $envelope['body'];
        $post_service->Subject = $envelope['subject'];

        // Recipients
        foreach ($envelope['to'] as $to) {
            call_user_func_array(
                array($post_service, 'addAddress'),
                $to
            );
        }
        foreach ($envelope['cc'] as $cc) {
            call_user_func_array(
                array($post_service, 'addCC'),
                $cc
            );
        }
        foreach ($envelope['bcc'] as $bcc) {
            call_user_func_array(
                array($post_service, 'addBCC'),
                $bcc
            );
        }
        foreach ($envelope['replyto'] as $replyto) {
            call_user_func_array(
                array($post_service, 'addReplyTo'),
                $replyto
            );
        }

        // Attachments
        foreach ($envelope['attachment'] as $attachment) {
            call_user_func_array(
                array($post_service, 'addAttachment'),
                $attachment
            );
        }

        // Blast out
        $post_service->send();
    }

    protected function resetPostService()
    {
        $post_service = $this->postService;
        $post_service->clearAllRecipients();
        $post_service->clearAttachments();
        $post_service->clearCustomHeaders();
        $post_service->clearReplyTos();
    }

    public function __destruct()
    {
        if (!is_null($this->postService)) {
            $this->postService->smtpClose();
        }
    }
}
