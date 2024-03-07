<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use EBT\ExtensionBuilder\Domain\Model\File;
use EBT\ExtensionBuilder\Utility\Inflector;
use PhpParser\Node\Stmt;

class Printer extends \EBT\ExtensionBuilder\Service\Printer {

	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->options['shortArraySyntax'] = true;
	}

	protected function trimTrailingWhitespace(string $code): string {
		return (string)preg_replace('/ +$/m', '', $code);
	}

	public function renderFileObject(File $fileObject, bool $addDeclareStrictTypes = true): string {
		// remove comments which say nothing (set title for a setTitle method, or "title" for a $title property)
		foreach ($fileObject->getNamespaces() as $namespace) {
			foreach ($namespace->getClasses() as $class) {
				if ($class->getDescription() === $class->getName()) {
					$class->setDescription('');
				}

				if ($class->getDescription() === 'The repository for ' . Inflector::pluralize(str_replace('Repository', '', $class->getName()))) {
					$class->setDescription('');
				}

				foreach ($class->getProperties() as $property) {
					if ($property->getDescription() === $property->getName()) {
						$property->setDescription('');
					}
				}

				foreach ($class->getMethods() as $method) {
					$returnValues = $method->getTags() && isset($method->getTags()['return']) ? $method->getTags()['return'] : false;
					if ($returnValues === 'void' || is_array($returnValues) && in_array('void', $returnValues)) {
						$method->removeTag('return');
					}

					$methodName = $method->getName();
					if ($method->getDescription() === $methodName) {
						$method->setDescription('');
						continue;
					}

					$firstParameter = $method->getParameters()[0] ?? null;
					$typeHint = $firstParameter ? $firstParameter->getTypeHint() : '';
					$typeHint = ltrim($typeHint, '?');
					$type = explode('\\', $typeHint);
					$type = array_pop($type);
					$methodProperty = $firstParameter ? preg_quote($type ?: $firstParameter->getName()) : '';
					$methodPropertyName = preg_quote(substr($methodName, str_starts_with($methodName, 'is') ? 2 : 3));

					$methodDescription = $method->getDescription();
					if (preg_match('/Returns the (boolean state of )?(' . $methodProperty . '|' . $methodPropertyName . ')/i', $methodDescription)) {
						$method->setDescription('');
						continue;
					}

					if (preg_match('/Sets the (' . $methodProperty . '|' . $methodPropertyName . ')/i', $methodDescription)) {
						$method->setDescription('');
						continue;
					}

					if (preg_match('/Adds a (' . $methodProperty . '|' . $methodPropertyName . ')/i', $methodDescription)) {
						$method->setDescription('');
						continue;
					}

					if (preg_match('/Removes a (' . $methodProperty . '|' . $methodPropertyName . ')/i', $methodDescription)) {
						$method->setDescription('');
						continue;
					}
				}
			}
		}

		$resultingCode = parent::renderFileObject($fileObject, $addDeclareStrictTypes);
		$resultingCode = str_replace(LF . LF . 'declare(strict_types=1);' . LF . LF . LF, LF . 'declare(strict_types = 1);' . LF . LF, $resultingCode);
		$resultingCode = str_replace(';' . LF . 'class', ';' . LF . LF . 'class', $resultingCode);

		return $resultingCode;
	}

	public function render($stmts): string {
		$code = parent::render($stmts);
		$code = $this->trimTrailingWhitespace($code);

		if (substr($code, 0, 10) === 'namespace ') {
			$code = "\n" . $code;
		}

		$code = (string)preg_replace('/(namespace.*;)\n{3,}/', "$1\n\n", $code);

		return $code;
	}

	/**
	 * @phpstan-return string
	 */
	public function pStmt_Class(Stmt\Class_ $node) {
		$string = (string)parent::pStmt_Class($node);
		$string = str_replace(LF . '{', ' {', $string);

		// replace methods without newline between them and previous line
		$string = (string)preg_replace('/([;}])(\n[\t ]*public)/', "\$1\n\$2", $string);

		return $string;
	}

	protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string {
		$string = (string)parent::pStmt_ClassMethod($node);
		return (string)preg_replace('/\n\s+\{/', ' {', $string);
	}

}
