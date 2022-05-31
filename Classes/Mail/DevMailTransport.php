<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Mail;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\AbstractStream;
use Symfony\Component\Mailer\Transport\Smtp\Stream\ProcessStream;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class DevMailTransport extends AbstractTransport {

	private const SENDER_ADDRESS = 'clients@4wdmedia.de';

	private string $command;
	private ProcessStream $stream;

	public function __construct(array $mailSettings) {
		$this->command = '/usr/local/bin/msmtp';
		if (empty($this->command)) {
			$this->command = '/usr/local/bin/msmtp';
		}
		$this->stream = new ProcessStream();
		parent::__construct();
	}

	public function __toString(): string {
		return $this->command;
	}

	private function getReceiverAddress(): string {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultReceiverAddress'])) {
			return $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultReceiverAddress'];
		}

		// dev-environment sendmail_path contains receiver address
		$sendmailPathParts = explode(' ', ini_get('sendmail_path') ?? '');
		foreach ($sendmailPathParts as $commandPart) {
			if (strpos($commandPart, '@')) {
				return $commandPart;
			}
		}

		if (isset($_SERVER['VIERWD_EMAIL'])) {
			return $_SERVER['VIERWD_EMAIL'];
		}

		throw new \Exception('Could not find default mail receiver address', 1653980884);
	}

	public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage {
		if ($message instanceof Message) {
			// Update from header
			$headers = $message->getHeaders();
			$senderAddress = $headers->getHeaderBody('From')[0];
			$senderName = $senderAddress ? $senderAddress->getName() : $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
			$headers->remove('From');
			$sender = new Address(self::SENDER_ADDRESS, $senderName);
			$headers->addHeader('From', [$sender]);
		}
		return parent::send($message, $envelope);
	}

	protected function doSend(SentMessage $message): void {
		$this->getLogger()->debug(sprintf('Email transport "%s" starting', self::class));

		$command = $this->command;

		$sender = $message->getEnvelope()->getSender();
		$sender = new Address(self::SENDER_ADDRESS, $sender->getName());
		$message->getEnvelope()->setSender($sender);
		if (!str_contains($command, ' -f')) {
			$command .= ' -f' . escapeshellarg($sender->getAddress());
		}
		$command .= ' ' . $this->getReceiverAddress();

		$chunks = AbstractStream::replace("\r\n", "\n", $message->toIterable());

		if (!str_contains($command, ' -i') && !str_contains($command, ' -oi')) {
			$chunks = AbstractStream::replace("\n.", "\n..", $chunks);
		}

		$this->stream->setCommand($command);
		$this->stream->initialize();
		foreach ($chunks as $chunk) {
			$this->stream->write($chunk);
		}
		$this->stream->flush();
		$this->stream->terminate();

		$this->getLogger()->debug(sprintf('Email transport "%s" stopped', self::class));
	}
}
