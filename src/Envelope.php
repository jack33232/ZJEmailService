<?php
namespace ZJEmailService;

use ArrayAccess;
use IteratorAggregate;
use Exception;

class Envelope implements ArrayAccess, IteratorAggregate
{
    protected $mailParts = array(
        'subject' => '',
        'body' => '',
        'priority' => 'NORMAL',
        'from' => array(),
        'to' => array(),
        'cc' => array(),
        'bcc' => array(),
        'replyto' => array(),
        'attachment' => array()
    );

    public function __construct($subject, $body, $priority = 'NORMAL')
    {
        $this->setPriority($priority);
        $this->setSubject($subject);
        $this->setBody($body);
    }

    public function setSubject($subject)
    {
        $this->mailParts['subject'] = $subject;
    }

    public function setBody($body)
    {
        $this->mailParts['body'] = $body;
    }

    public function setPriority($priority)
    {
        $priority = strtoupper($priority);
        if (in_array($priority, array('NORMAL', 'HIGH', 'LOW'))) {
            $this->mailParts['priority'] = $priority;
        }
    }

    public function setFrom($from)
    {
        if (is_string($from)) {
            $from = array($from);
        }

        $this->mailParts['from'] = $from;
    }

    public function setTo($to)
    {
        if (!is_array($to)) {
            $to = array(
                array($to)
            );
        } elseif (!is_array(reset($to))) {
            $to = array($to);
        }
        $this->mailParts['to'] = $to;
    }

    public function setCc($cc)
    {
        if (!is_array($cc)) {
            $cc = array(
                array($cc)
            );
        } elseif (!is_array(reset($cc))) {
            $cc = array($cc);
        }
        $this->mailParts['cc'] = $cc;
    }

    public function setBcc($bcc)
    {
        if (!is_array($bcc)) {
            $bcc = array(
                array($bcc)
            );
        } elseif (!is_array(reset($bcc))) {
            $bcc = array($bcc);
        }
        $this->mailParts['bcc'] = $bcc;
    }

    public function setReplyto($replyto)
    {
        if (!is_array($replyto)) {
            $replyto = array(
                array($replyto)
            );
        } elseif (!is_array(reset($replyto))) {
            $replyto = array($replyto);
        }
        $this->mailParts['replyto'] = $replyto;
    }

    public function setAttachment($attachment)
    {
        if (!is_array($attachment)) {
            $attachment = array(
                array($attachment)
            );
        } elseif (!is_array(reset($attachment))) {
            $attachment = array($attachment);
        }
        $this->mailParts['attachment'] = $attachment;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->mailParts);
    }

    public function offsetSet($offset, $value)
    {
        $method_name = 'set' . ucfirst($offset);
        if (method_exists($this, $method_name)) {
            call_user_func(array($this, $method_name), $value);
        } else {
            throw new Exception(sprintf('Invalid Property "%s" to set.', $offset));
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->mailParts[$offset]);
    }

    public function offsetUnset($offset)
    {
        // No unset
    }

    public function offsetGet($offset)
    {
        return isset($this->mailParts[$offset]) ? $this->mailParts[$offset] : null;
    }
}
