<?php

namespace Vierwd\VierwdBase\Tests\Functional\Frontend\ContentObject;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;

class ScalableVectorGraphicsContentObjectTest extends UnitTestCase {

	/**
	 * inlining an svg
	 *
	 * @test
	 */
	public function testSvg(): void {
		$TSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
			->disableOriginalConstructor()
			->getMock();

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $TSFE);
		$cObj->registerContentObjectClass(ScalableVectorGraphicsContentObject::class, 'SVG');

		$svg = $cObj->cObjGetSingle('SVG', [
			'value' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
			'stdWrap.' => [
				'wrap' => 'a|b',
			],
		]);

		$this->assertEquals('a<svg class="svg" role="img" aria-hidden="true"></svg>b', $svg);
	}

}
