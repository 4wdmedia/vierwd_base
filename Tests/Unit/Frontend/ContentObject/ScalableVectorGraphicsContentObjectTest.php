<?php

namespace Vierwd\VierwdBase\Tests\Functional\Frontend\ContentObject;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;

class ScalableVectorGraphicsContentObjectTest extends UnitTestCase {

	protected $resetSingletonInstances = true;

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
		$requestMock = $this->getMockBuilder(ServerRequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$cObj->setRequest($requestMock);
		$cObj->registerContentObjectClass(ScalableVectorGraphicsContentObject::class, 'SVG');

		$svg = $cObj->cObjGetSingle('SVG', [
			'value' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
			'stdWrap.' => [
				'wrap' => 'a|b',
			],
		]);

		self::assertEquals('a<svg class="svg" role="img" aria-hidden="true"></svg>b', $svg);
	}
}
