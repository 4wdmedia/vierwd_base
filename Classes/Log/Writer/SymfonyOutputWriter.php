<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Log\Writer;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;

class SymfonyOutputWriter extends AbstractWriter {

	public static ?OutputInterface $output = null;

	/** @var ?callable */
	public static $formatter;

	private static int $indent = 0;

	public static function indent(): void {
		self::$indent++;
	}

	public static function outdent(): void {
		self::$indent--;
		if (self::$indent < 0) {
			throw new \Exception('Too much outdent for logging', 1638954904);
		}
	}

	public function writeLog(LogRecord $record): WriterInterface {
		if (self::$output === null) {
			// only output messages in CLI context
			return $this;
		}

		$verbosity = [
			LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
			LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
			LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
		];

		$colors = [
			LogLevel::EMERGENCY => '<fg=red>',
			LogLevel::ALERT => '<fg=red>',
			LogLevel::CRITICAL => '<fg=red>',
			LogLevel::ERROR => '<fg=red>',
			LogLevel::WARNING => '<fg=yellow>',
			LogLevel::NOTICE => '<fg=green>',
			LogLevel::INFO => '<fg=cyan>',
			LogLevel::DEBUG => '<fg=cyan>',
		];

		$data = $record->getData();
		$message = $this->interpolate($record->getMessage(), $data);

		if (self::$formatter) {
			$message = call_user_func(self::$formatter, $message, $data);
		}

		$indent = str_repeat(' ', self::$indent * 2);
		self::$output->writeln($indent . $colors[$record->getLevel()] . $message . '</>', $verbosity[$record->getLevel()] ?? OutputInterface::VERBOSITY_NORMAL);

		return $this;
	}

}
