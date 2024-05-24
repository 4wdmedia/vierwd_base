<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;

/**
 * Use ScalableVectorGraphicsContentObject as ViewHelpers.
 * Usage:
 * <code title="Example">
 * {namespace fwd=Vierwd\VierwdBase\ViewHelpers}
 * <fwd:svg src="EXT:vierwd_example/Resources/Public/static/img/icons/arrow-down.svg" />
 * </code>
 */
class SvgViewHelper extends AbstractViewHelper {

	use CompileWithRenderStatic;

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 * @var bool
	 */
	protected $escapeOutput = false;

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('width', 'integer', 'width');
		$this->registerArgument('height', 'integer', 'height');
		$this->registerArgument('src', 'string', 'src');
		$this->registerArgument('value', 'string', 'value');
		$this->registerArgument('class', 'string', 'class');
	}

	/**
	 * renders an SVG
	 *
	 * {@inheritdoc}
	 */
	public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$svgObject = GeneralUtility::makeInstance(ScalableVectorGraphicsContentObject::class, $cObj);
		return $svgObject->render($arguments);
	}

}
