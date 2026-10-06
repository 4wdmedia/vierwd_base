<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use TYPO3\CMS\Core\Resource\FileInterface;

class FileInterfaceDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension {

	public function __construct(private readonly TypeStringResolver $typeStringResolver) {}

	public function getClass(): string {
		return FileInterface::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
	): bool {
		return $methodReflection->getName() === 'getProperty';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope,
	): Type {
		$argument = $methodCall->getArgs()[0] ?? null;

		$returnTypeMapping = [
			'width' => 'int',
			'height' => 'int',
			'alternative' => 'string',
			'description' => 'string',
			'crop' => 'string',
		];

		if ($argument === null || !($argument->value instanceof String_) || !isset($returnTypeMapping[$argument->value->value])) {
			return $methodReflection->getVariants()[0]->getReturnType();
		}

		return $this->typeStringResolver->resolve($returnTypeMapping[$argument->value->value]);
	}

}
