<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt;

class Printer extends \EBT\ExtensionBuilder\Service\Printer {
	protected $indentToken = "\t";

	public function pStmt_Class(Stmt\Class_ $node) {
		$string = parent::pStmt_Class($node);
		return str_replace(LF . '{', ' {', $string);
	}

	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		$string = parent::pStmt_ClassMethod($node);
		return str_replace(LF . '{', ' {', $string);
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
