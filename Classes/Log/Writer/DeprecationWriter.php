<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Log\Writer;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Filter deprecations and use terminal notifier to show them
 */
class DeprecationWriter extends AbstractWriter {

	protected $messages = [];

	/**
	 * @param LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 */
	public function writeLog(LogRecord $record) {
		if (TYPO3_MODE !== 'FE' || !$this->checkTrace()) {
			return $this;
		}

		if (!$this->messages) {
			$this->registerShutdownFunction();
		}

		$message = $record->getMessage();
		$location = $this->getVierwdClass();
		if ($location['class'] && $location['function']) {
			$message .= "\n" . $location['class'] . ($location['type'] ?? '->') . $location['function'];
		} else if ($location['file'] && isset($location['line'])) {
			$message .= "\n" . $location['file'] . ':' . $location['line'];
		}
		$message = str_replace(Environment::getPublicPath(), '', $message);
		$message = str_replace(Environment::getProjectPath(), '', $message);
		$message = str_replace('Core: Error handler (FE): TYPO3 Deprecation Notice: ', '', $message);

		$this->messages[] = $message;

		return $this;
	}

	/**
	 * check within the current backtrace if one of our classes is involved in this
	 * deprecation warning.
	 */
	protected function checkTrace(): bool {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// only look at the first 20 classes
		$trace = array_slice($trace, 0, 20);

		$classes = array_filter($trace, function(array $traceEntry): bool {
			return !empty($traceEntry['class']) && StringUtility::beginsWith($traceEntry['class'], 'Vierwd\\') && $traceEntry['class'] !== self::class;
		});
		if (!$classes) {
			return false;
		}

		return true;
	}

	protected function getVierwdClass(): ?array {
		$trace = debug_backtrace();
		// only look at the first 20 classes
		$trace = array_slice($trace, 0, 20);

		$classes = array_filter($trace, function(array $traceEntry): bool {
			$class = !empty($traceEntry['class']) && StringUtility::beginsWith($traceEntry['class'], 'Vierwd\\') && $traceEntry['class'] !== self::class;
			$template = !empty($traceEntry['file']) && StringUtility::endsWith($traceEntry['file'], 'smarty_template_resource_base.php');
			return $class || $template;
		});
		if (!$classes) {
			return null;
		}

		$traceEntry = current($classes);
		if (!empty($traceEntry['file']) && StringUtility::endsWith($traceEntry['file'], 'smarty_template_resource_base.php')) {
			if ($traceEntry['args'][0] instanceof \Smarty_Internal_Template) {
				return [
					'file' => $traceEntry['args'][0]->template_resource,
					'line' => 0,
				];
			}
		}

		return $traceEntry;
	}

	protected function registerShutdownFunction(): void {
		if (PHP_SAPI !== 'cli') {
			register_shutdown_function([$this, 'send']);
		}
	}

	public function send(): void {
		// limit the messages to max 20
		$messages = array_slice($this->messages, 0, 20);
		$messages = json_encode($messages);
		echo <<<EOT
<script>
(function() {
	let messages = $messages;
	let container = document.createElement('div');

	container.style.position = 'absolute';
	container.style.top = '10px';
	container.style.left = '10px';
	container.style.padding = '10px';
	container.style.zIndex = 10000000;
	container.style.background = 'rgba(255, 240, 240, .9)';
	container.style.color = '#222';
	container.style.fontSize = '14px';
	container.style.maxWidth = '800px';
	container.style.border = '1px solid #f99';
	container.style.wordWrap = 'break-word';
	container.style.whiteSpace = 'pre-wrap';

	let p = document.createElement('p');
	let strong = document.createElement('strong');
	strong.append('TYPO3 Deprecation Notice');
	p.append(strong);
	container.append(p);

	let x = document.createElement('span');
	x.onclick = () => {
		container.remove();
	};
	x.append('×')
	x.style.position = 'absolute';
	x.style.top = '5px';
	x.style.right = '5px';
	x.style.fontSize = '20px';
	x.style.lineHeight = '20px';
	x.style.cursor = 'pointer';
	container.append(x);

	messages.forEach((message) => {
		let p = document.createElement('p');
		p.append(message);
		container.append(p);
	});

	if (!document.getElementById('krumo-1')) {
		document.body.append(container);
	}
}());
</script>
EOT;
	}
}
