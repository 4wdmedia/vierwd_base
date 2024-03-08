<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\View;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Vierwd\VierwdBase\Hooks\Utility as BaseUtility;

class UtilityTest extends UnitTestCase {

	protected bool $resetSingletonInstances = true;

	/**
	 * test adding meta tags to page
	 *
	 * @test
	 */
	public function testMetaTags(): void {
		$this->markTestIncomplete();

		// Initialize Application, because a valid Service Container is needed
		// @phpstan-ignore-next-line markTestIncomplete finishes code execution
		$classLoader = include PHPUNIT_COMPOSER_INSTALL;
		Bootstrap::init($classLoader, true);

		$utility = GeneralUtility::makeInstance(BaseUtility::class);
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->setContentObjectRenderer($cObj);

		$baseContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/MetaTagsBase.html');
		$params = [
			'meta.' => [
				'google' => 'notranslate',
				'meta.og:image:width' => 400,
				'meta.og:title' => '',
				'meta.og:title.' => [
					'required' => 1,
				],
			],
			'link.' => [
				10 => '<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">',
			],
		];
		$utility->addMetaTags($baseContent, $params);

		// addMetaTags adds the tags to the singleton pageRenderer
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->setTemplateFile(getcwd() . '/Tests/Unit/Fixtures/Utility/PageRendererTemplate.html');

		self::assertEquals('<meta name="generator" content="TYPO3 CMS" />
<meta name="google" content="notranslate" />
<meta name="meta.og:image:width" content="400" />
<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">', $pageRenderer->render());

		// Bootstrap::init starts output buffering
		ob_end_clean();
	}

}
