<?php

namespace Vierwd\VierwdBase\Tests\Unit\View;

use Vierwd\VierwdBase\Hooks\Utility as BaseUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Page\PageRenderer;

class UtilityTest extends UnitTestCase {

	/**
	 * test adding meta tags to page
	 *
	 * @test
	 */
	public function testMetaTags() {
		$utility = GeneralUtility::makeInstance(BaseUtility::class);
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

		$baseContent = file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/MetaTagsBase.html');
		$params = [
			'meta.' => [
				'google' => 'notranslate',
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
		$reflectionProperty = new \ReflectionProperty($pageRenderer, 'metaTags');
		$reflectionProperty->setAccessible(true);
		$metaTags = $reflectionProperty->getValue($pageRenderer);

		$this->assertEquals([
			'<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">',
			'<meta name="google" content="notranslate">',
		], $metaTags);
	}

	/**
	 * test for html processing
	 *
	 * @test
	 */
	public function testProcessHtml() {
		$utility = $this->getMockBuilder(BaseUtility::class)
			->setMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->will($this->returnCallback(function() {
			return ['con•sec•tetur', 'adi#pi#sicing'];
		}));
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

		$baseContent = file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/MetaTagsBase.html');
		$TSFE = $this->setupTsfeMock();
		$TSFE->content = $baseContent;
		$utility->postProcessHTML([], $TSFE);

		$expectedContent = file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/HyphenationExpected.html');
		$expectedContent = str_replace('%SHY%', html_entity_decode('&shy;', 0, 'UTF-8'), $expectedContent);

		$this->assertEquals($expectedContent, $TSFE->content);
	}

	/**
	 * when script tags get too long, regular expressions might throw an error
	 *
	 * @test
	 */
	public function testProcessHtmlWithLongScript() {
		$utility = $this->getMockBuilder(BaseUtility::class)
			->setMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->will($this->returnCallback(function() {
			return [];
		}));
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '_NO_TABLE');
		$utility->cObj = $cObj;

		$baseContent = file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/ProcessLongHtml.html');
		$TSFE = $this->setupTsfeMock();
		$TSFE->content = $baseContent;
		$utility->postProcessHTML([], $TSFE);

		$this->assertEquals($baseContent, $TSFE->content);
	}

	protected function setupTsfeMock() {
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
