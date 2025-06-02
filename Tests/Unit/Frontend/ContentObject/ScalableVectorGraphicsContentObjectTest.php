<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Functional\Frontend\ContentObject;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;

class ScalableVectorGraphicsContentObjectTest extends UnitTestCase {

	protected bool $resetSingletonInstances = true;

	public function setUp(): void {
		parent::setUp();

		$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['SVG'] = ScalableVectorGraphicsContentObject::class;
	}

	public function tearDown(): void {
		parent::tearDown();

		unset($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['SVG']);
	}

	/**
	 * inlining an svg
	 */
	#[Test]
	public function testSvg(): void {
		$testObject = new ScalableVectorGraphicsContentObject();

		$contentObjectFactoryMock = $this->getMockBuilder(ContentObjectFactory::class)
			->disableOriginalConstructor()
			->onlyMethods(['getContentObject'])
			->getMock();
		$contentObjectFactoryMock->method('getContentObject')->willReturn($testObject);
		GeneralUtility::addInstance(ContentObjectFactory::class, $contentObjectFactoryMock);

		$eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcherMock);

		// @phpstan-ignore-next-line TypoScriptFrontendController is deprecated. Ignore for now.
		$TSFE = $this->getMockBuilder(TypoScriptFrontendController::class)
			->disableOriginalConstructor()
			->getMock();

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $TSFE);
		$cObj->stdWrapOrder = [
			'stdWrap' => 'stdWrap',
			'stdWrap.' => 'array',
			'wrap' => 'wrap',
			'wrap.' => 'array',
		];
		$requestMock = $this->getMockBuilder(ServerRequestInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$cObj->setRequest($requestMock);
		$testObject->setRequest($requestMock);
		$testObject->setContentObjectRenderer($cObj);

		$svg = $cObj->cObjGetSingle('SVG', [
			'value' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
			'stdWrap.' => [
				'wrap' => 'a|b',
			],
		]);

		self::assertEquals('a<svg class="svg" role="img" aria-hidden="true"></svg>b', $svg);
	}

}
