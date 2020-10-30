<?php

namespace Vierwd\VierwdBase\Tests\Unit\View;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Vierwd\VierwdBase\Hooks\Utility as BaseUtility;

class UtilityTest extends UnitTestCase {

	/**
	 * test adding meta tags to page
	 *
	 * @test
	 */
	public function testMetaTags(): void {
		// Initialize Application, because a valid Service Container is needed
		$classLoader = include PHPUNIT_COMPOSER_INSTALL;
		Bootstrap::init($classLoader, true);

		$utility = GeneralUtility::makeInstance(BaseUtility::class);
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

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

		$this->assertEquals('<meta name="generator" content="TYPO3 CMS" />
<meta name="google" content="notranslate" />
<meta name="meta.og:image:width" content="400" />
<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">', $pageRenderer->render());

		// Bootstrap::init starts output buffering
		ob_end_clean();
	}

	/**
	 * test for html processing
	 *
	 * @test
	 */
	public function testProcessHtml(): void {
		/** @var BaseUtility&\PHPUnit\Framework\MockObject\MockObject $utility */
		$utility = $this->getMockBuilder(BaseUtility::class)
			->setMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->will($this->returnCallback(function() {
			return ['con•sec•tetur', 'adi#pi#sicing'];
		}));
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

		$baseContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/MetaTagsBase.html');
		$TSFE = $this->setupTsfeMock();
		$TSFE->content = $baseContent;
		$utility->postProcessHTML([], $TSFE);

		$expectedContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/HyphenationExpected.html');
		$expectedContent = str_replace('%SHY%', html_entity_decode('&shy;', 0, 'UTF-8'), $expectedContent);
		$expectedContent = str_replace("\n", '', $expectedContent);

		$actualContent = str_replace("\n", '', $TSFE->content);

		$this->assertEquals($expectedContent, $actualContent);
	}

	/**
	 * when script tags get too long, regular expressions might throw an error
	 *
	 * @test
	 */
	public function testProcessHtmlWithLongScript(): void {
		/** @var BaseUtility&\PHPUnit\Framework\MockObject\MockObject $utility */
		$utility = $this->getMockBuilder(BaseUtility::class)
			->setMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->will($this->returnCallback(function() {
			return [];
		}));
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

		$baseContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/ProcessLongHtml.html');
		$TSFE = $this->setupTsfeMock();
		$TSFE->content = $baseContent;
		$utility->postProcessHTML([], $TSFE);

		$expectedContent = str_replace("\n", '', trim($baseContent));
		$actualContent = str_replace("\n", '', trim($TSFE->content));

		$this->assertEquals($expectedContent, $actualContent);
	}

	/**
	 * @return TypoScriptFrontendController&\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function setupTsfeMock() {
		/** @var TypoScriptFrontendController&\PHPUnit\Framework\MockObject\MockObject $tsfe */
		$tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
			->disableOriginalConstructor()
			->getMock();
		$tsfe->content = '';
		$config = [
			'config' => [
				'tx_vierwd.' => [
				],
			],
		];
		$tsfe->config = $config;
		$GLOBALS['TSFE'] = $tsfe;

		return $tsfe;
	}
}
