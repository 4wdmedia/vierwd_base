<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Install;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

use Vierwd\VierwdBase\Console\Command\PostComposerCommand;

class ServiceProvider extends AbstractServiceProvider {

	protected static function getPackagePath(): string {
		return __DIR__ . '/../';
	}

	public function getFactories(): array {
		return [
			PostComposerCommand::class => [ static::class, 'getPostComposerCommand' ],
		];
	}

	public function getExtensions(): array {
		return parent::getExtensions() + [CommandRegistry::class => [static::class, 'configureCommands']];
	}

	public static function getPostComposerCommand(ContainerInterface $container): PostComposerCommand {
		return new PostComposerCommand('vierwd:post-composer');
	}

	public static function configureCommands(ContainerInterface $container, CommandRegistry $commandRegistry): CommandRegistry {
		$commandRegistry->addLazyCommand('vierwd:post-composer', PostComposerCommand::class);
		return $commandRegistry;
	}

}
