<?php

namespace Clascade\Mail\Mailer;

class MailFunction
{
	public function send ($message)
	{
		$body = $message->formatMIME($headers, ['to', 'subject']);
		return mail($message->format('to'), $message['subject'], $body, $headers);
	}
}
