<?php
declare(strict_types = 1);

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;

/**
 * Usage: {svg width='36' height='36' src='EXT:vierwd_example/Resources/Public/static/img/font-awesome/chevron-right.svg'}
 */
function smarty_function_svg(array $params, Smarty_Internal_Template $smarty): string {
	$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
	$svgObject = GeneralUtility::makeInstance(ScalableVectorGraphicsContentObject::class, $cObj);
	return $svgObject->render($params);
}
