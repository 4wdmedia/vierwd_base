<?php

namespace Vierwd\VierwdBase\Hooks;

class BrowserSync {
	public function enable() {
		if (empty($_SERVER['4WD_CONFIG'])) {
			return '';
		}

		// check if the port 3000 is open
		if (!trim(`lsof -i :3000 -P | grep "^node.*3000"`)) {
			return '';
		}

		return '<script async src="http://' . $_SERVER['SERVER_NAME'] . ':3000/browser-sync/browser-sync-client.js"></script>';
	}
}