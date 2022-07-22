<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use EBT\ExtensionBuilder\Domain\Model\File;
use EBT\ExtensionBuilder\Utility\Inflector;
use PhpParser\Node\Stmt;

class Printer extends \EBT\ExtensionBuilder\Service\Printer {

	/** @var string */
	protected $indentToken = "\t";

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

					$methodProperty = lcfirst(substr($methodName, 3));
					if (substr($methodName, 0, 3) === 'get' && 'Returns the ' . $methodProperty === $method->getDescription()) {
						$method->setDescription('');
						continue;
					}

					if (substr($methodName, 0, 3) === 'set' && 'Sets the ' . $methodProperty === $method->getDescription()) {
						$method->setDescription('');
						continue;
					}

					if (substr($methodName, 0, 3) === 'add' && 'Adds a ' . ucfirst($methodProperty) === $method->getDescription()) {
						$method->setDescription('');
						continue;
					}

					$methodProperty = substr($methodName, 6);
					if (substr($methodName, 0, 6) === 'remove' && 'Removes a ' . $methodProperty === $method->getDescription()) {
						$method->setDescription('');
						continue;
					}
				}
			}
		}
		return parent::renderFileObject($fileObject, $addDeclareStrictTypes);
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

	/**
	 * @phpstan-return string
	 */
	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		$string = (string)parent::pStmt_ClassMethod($node);
		return (string)preg_replace('/\n\s+\{/', ' {', $string);
	}

}
