<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use EBT\ExtensionBuilder\Domain\Model\File;
use EBT\ExtensionBuilder\Utility\Inflector;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt;

class Printer extends \EBT\ExtensionBuilder\Service\Printer {
	protected $indentToken = "\t";

	protected function trimTrailingWhitespace($code) {
		return preg_replace('/ +$/m', '', $code);
	}

	public function renderFileObject(File $fileObject, $prependPHPTag = false) {
		// remove comments which say nothing (set title for a setTitle method, or "title" for a $title property)
		foreach ($fileObject->getNamespaces() as $namespace) {
			foreach ($namespace->getClasses() as $class) {
				if ($class->getDescription() === $class->getName()) {
					$class->setDescription('');
				}

				if ($class->getDescription() === 'The repository for ' . Inflector(str_replace('Repository', '', $class->getName()))) {
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
		return parent::renderFileObject($fileObject, $prependPHPTag);
	}

	public function render($stmts) {
		$code = parent::render($stmts);
		$code = $this->trimTrailingWhitespace($code);

		if (substr($code, 0, 10) === 'namespace ') {
			$code = "\n" . $code;
		}

		$code = preg_replace('/(namespace.*;)\n{3,}/', "$1\n\n", $code);

		return $code;
	}

	public function pStmt_Class(Stmt\Class_ $node) {
		$string = parent::pStmt_Class($node);
		$string = str_replace(LF . '{', ' {', $string);

		// replace methods without newline between them and previous line
		$string = preg_replace('/([;}])(\n[\t ]*public)/', "\$1\n\$2", $string);

		return $string;
	}

	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		$string = parent::pStmt_ClassMethod($node);
		return preg_replace('/\n\s+\{/', ' {', $string);
	}

	public function pExpr_Array(Array_ $node) {
		$multiLine = false;
		$startLine = $node->getAttribute('startLine');
		$endLine = $node->getAttribute('endLine');
		if ($startLine != $endLine) {
			$multiLine = true;
		}
		$printedNodes = '';
		foreach ($node->items as $itemNode) {
			$glueToken = ', ';
			if ($itemNode->getAttribute('startLine') != $startLine) {
				$glueToken = ',' . LF;
				$startLine = $itemNode->getAttribute('startLine');
			}
			if (!empty($printedNodes)) {
				$printedNodes .= $glueToken . $this->p($itemNode);
			} else {
				$printedNodes .= $this->p($itemNode);
			}
		}
		if ($multiLine) {
			$multiLinedItems = $this->indentToken . preg_replace(
					'~\\n(?!$|' . $this->noIndentToken . ')~',
					LF . $this->indentToken,
					$printedNodes . ($printedNodes ? ',' : '')
				);
			return '[' . LF . $multiLinedItems . LF . ']';
		} else {
			return parent::pExpr_Array($node);
		}
	}
}
