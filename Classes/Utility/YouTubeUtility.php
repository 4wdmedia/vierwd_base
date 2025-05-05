<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use Vierwd\SvgInliner\SvgInliner;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\sprintf;

class YouTubeUtility {

	protected static ?SvgInliner $svgInliner = null;

	public static function generatePreview(string $videoId, array $arguments = []): string {
		$previewImage = self::getPreviewImage($videoId);

		$arguments = [
			'autoplay' => 1,
			'rel' => 0,
		] + $arguments;
		$youtubeLink = 'https://www.youtube.com/watch?v=' . $videoId . GeneralUtility::implodeArrayForUrl('', $arguments);
		$embedLink = 'https://www.youtube-nocookie.com/embed/' . $videoId . '?' . ltrim(GeneralUtility::implodeArrayForUrl('', $arguments), '&');

		$attributes = [
			'class' => 'youtube-preview',
			'data-youtube-url' => htmlspecialchars($embedLink),
		];

		$result = '<div ' . GeneralUtility::implodeAttributes($attributes) . '>';
		$result .= '<div class="youtube-preview__preview">';
		if ($previewImage instanceof File) {
			// Generate Thumbnails
			$taskType = ProcessedFile::CONTEXT_IMAGECROPSCALEMASK;
			$sizes = [
				992 => 1280,
				592 => 992,
				0 => 592,
			];
			$result .= '<picture>';
			foreach ($sizes as $breakpoint => $maxImageWidth) {
				$scaledImage = $previewImage->process($taskType, [
					'maxWidth' => $maxImageWidth,
					'fileExtension' => 'webp',
				]);
				$media = $breakpoint ? ' media="(min-width: ' . $breakpoint . 'px)"' : '';
				$result .= '<source srcset="' . $scaledImage->getPublicUrl() . '"' . $media . ' type="image/webp">';
			}
			$imageAttributes = [
				'src' => $previewImage->getPublicUrl(),
				'alt' => '',
				'class' => 'youtube-preview__image',
			];
			assert(is_string($imageAttributes['src']));
			$result .= '<img ' . GeneralUtility::implodeAttributes($imageAttributes, true, true) . '>';
			$result .= '</picture>';
		}

		$buttonContent = self::getSVG('EXT:vierwd_base/Resources/Public/Icons/font-awesome/triangle-right.svg', ['width' => 24, 'height' => 24]);
		$buttonContent .= htmlspecialchars(LocalizationUtility::translate('youtube-preview.accept', 'VierwdBase') ?? '');
		$previewText = LocalizationUtility::translate('youtube-preview.text', 'VierwdBase');
		$result .= '<div class="youtube-preview__overlay">';
		$result .= '<p>' . $previewText . '</p>';
		$result .= '<a href="' . $youtubeLink . '" target="_blank" class="button youtube-preview__accept">' . $buttonContent . '</a>';
		$result .= '</div>'; // youtube-preview__overlay
		$result .= '</div>'; // youtube-preview__preview
		$result .= '<div class="youtube-preview__text-mobile">' . $previewText . '</div>';
		$result .= '</div>'; // youtube-preview

		return $result;
	}

	public static function getPreviewImage(string $videoId): ?FileInterface {
		$resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
		$storage = $resourceFactory->getDefaultStorage();
		assert($storage instanceof ResourceStorage);

		if (!$storage->hasFolder('/YouTube-Preview')) {
			$storage->createFolder('/YouTube-Preview');
		}

		$fileName = '/YouTube-Preview/' . $videoId . '.jpg';

		if ($storage->hasFile($fileName)) {
			return $storage->getFile($fileName);
		}

		$temporaryFileName = self::getTemporaryPreviewImage($videoId);
		if (!$temporaryFileName) {
			return null;
		}

		$targetFolder = $storage->getFolder('YouTube-Preview');
		return $storage->addFile($temporaryFileName, $targetFolder, $videoId . '.jpg', DuplicationBehavior::REPLACE, false);
	}

	private static function getTemporaryPreviewImage(string $videoId): ?string {
		$temporaryFileName = self::getTempFolderPath() . 'youtube_' . $videoId . '.jpg';
		$tryNames = ['maxresdefault.jpg', 'mqdefault.jpg', '0.jpg'];
		foreach ($tryNames as $tryName) {
			$previewImage = GeneralUtility::getUrl(
				sprintf('https://img.youtube.com/vi/%s/%s', $videoId, $tryName)
			);
			if ($previewImage !== false) {
				file_put_contents($temporaryFileName, $previewImage);
				GeneralUtility::fixPermissions($temporaryFileName);
				return $temporaryFileName;
			}
		}

		return null;
	}

	public static function getOEmbedUrl(string $videoId, string $format = 'json'): string {
		return sprintf('https://www.youtube.com/oembed?url=%s&format=%s', rawurlencode(sprintf('https://www.youtube.com/watch?v=%s', rawurlencode($videoId))), rawurlencode($format));
	}

	private static function getTempFolderPath(): string {
		$path = Environment::getVarPath() . '/transient/';
		if (!is_dir($path)) {
			GeneralUtility::mkdir_deep($path);
		}
		return $path;
	}

	private static function getSVG(string $path, array $options = []): string {
		if (self::$svgInliner === null) {
			self::$svgInliner = new SvgInliner(['excludeFromConcatenation' => true]);
		}

		$svgFile = GeneralUtility::getFileAbsFileName($path);
		$value = trim(file_get_contents($svgFile));
		$svg = self::$svgInliner->renderSVG($value, $options + [
			'identifier' => 'svg-' . md5($value),
			'class' => 'svg-' . pathinfo($svgFile, PATHINFO_FILENAME),
		]);
		return $svg;
	}

}
