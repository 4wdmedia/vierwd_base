<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Vierwd\VierwdBase\Frontend\PostProcessHTML;

class PostProcessHTMLTest extends UnitTestCase {

	/**
	 * test for html processing
	 */
	#[Test]
	public function testProcessHtml(): void {
		/** @var PostProcessHTML&MockObject $utility */
		$utility = $this->getMockBuilder(PostProcessHTML::class)
			->onlyMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->willReturnCallback(function(): array {
			return ['con•sec•tetur', 'adi#pi#sicing'];
		});

		$baseContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/MetaTagsBase.html');
		$typoScriptArray = $this->getTypoScriptArray();
		$actualContent = $utility->postProcessHTML($baseContent, $typoScriptArray);

		$expectedContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/HyphenationExpected.html');
		$expectedContent = str_replace('%SHY%', html_entity_decode('&shy;', 0, 'UTF-8'), $expectedContent);
		$expectedContent = trim(str_replace("\n", '', $expectedContent));

		$actualContent = trim(str_replace("\n", '', $actualContent));

		self::assertEquals($expectedContent, $actualContent);
	}

	/**
	 * when script tags get too long, regular expressions might throw an error
	 */
	#[Test]
	public function testProcessHtmlWithLongScript(): void {
		/** @var PostProcessHTML&MockObject $utility */
		$utility = $this->getMockBuilder(PostProcessHTML::class)
			->onlyMethods(['getHyphenationWords'])
			->getMock();
		$utility->method('getHyphenationWords')->willReturnCallback(function(): array {
			return [];
		});

		$baseContent = (string)file_get_contents(getcwd() . '/Tests/Unit/Fixtures/Utility/ProcessLongHtml.html');
		$typoScriptArray = $this->getTypoScriptArray();
		$actualContent = $utility->postProcessHTML($baseContent, $typoScriptArray);

		$expectedContent = str_replace("\n", '', trim($baseContent));
		$actualContent = str_replace("\n", '', trim($actualContent));

		self::assertEquals($expectedContent, $actualContent);
	}

	protected function getTypoScriptArray(): array {
		return [
			'tx_vierwd.' => [
			],
		];
	}

}
