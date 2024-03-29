<?php
declare(strict_types = 1);

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName

defined('LF') ?: define('LF', chr(10));
defined('CR') ?: define('CR', chr(13));
defined('CRLF') ?: define('CRLF', CR . LF);

defined('FILE_DENY_PATTERN_DEFAULT') ?: define('FILE_DENY_PATTERN_DEFAULT', '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$');

defined('TYPO3_MODE') ?: define('TYPO3_MODE', '');
defined('TYPO3_mainDir') ?: define('TYPO3_mainDir', 'typo3/');
defined('TYPO3_version') ?: define('TYPO3_version', '');
defined('TYPO3_branch') ?: define('TYPO3_branch', '');

defined('PHPUNIT_COMPOSER_INSTALL') ?: define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/vendor/autoload.php');
