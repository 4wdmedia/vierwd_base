<?php

namespace Vierwd\VierwdBase\ExtensionBuilder\Service;

use \PhpParser\Node\Stmt;

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
}
