<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Hooks;

class FrontendLoginHook {

	/**
	 * Hooks to the felogin extension to provide additional code for FE login
	 *
	 * @return array 0 => onSubmit function, 1 => extra fields and required files
	 */
	public function loginFormHook(): array {
		$result = [0 => '', 1 => ''];

		if (isset($GLOBALS['TSFE']->additionalHeaderData['rsaauth_js'])) {
			unset($GLOBALS['TSFE']->additionalHeaderData['rsaauth_js']);
		}
		return $result;
	}

}
