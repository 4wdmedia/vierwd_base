<?php
declare(strict_types = 1);

namespace Vierwd\VierwdBase\Tests\Unit\Backend\Unit\Backend;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

use Vierwd\VierwdBase\Backend\GetButtonsHook;

class GetButtonsHookTest extends UnitTestCase {

	use ProphecyTrait;

	private GetButtonsHook $subject;

	private Icon $iconMock;

	public function setUp(): void {
		parent::setUp();

		$iconMock = $this->prophesize(Icon::class);

		$iconFactoryMock = $this->prophesize(IconFactory::class);
		$iconFactoryMock->getIcon(Argument::any(), Argument::any())->willReturn($iconMock->reveal());
		$this->iconMock = $iconMock->reveal();

		$languageServiceMock = $this->prophesize(LanguageService::class);
		$languageServiceMock->sL(Argument::any())->willReturn('title');

		$languageServiceFactoryMock = $this->prophesize(LanguageServiceFactory::class);
		$languageServiceFactoryMock->createFromUserPreferences(Argument::any())->willReturn($languageServiceMock->reveal());

		$this->subject = new GetButtonsHook($iconFactoryMock->reveal(), $languageServiceFactoryMock->reveal());
	}

	public function testAdjustSaveAndCloseWithoutLeftButtons(): void {
		$arrayWithoutLeftButtons = ['right' => 'ignore'];
		$event = new ModifyButtonBarEvent($arrayWithoutLeftButtons, $this->prophesize(ButtonBar::class)->reveal());
		call_user_func($this->subject, $event);
		self::assertEquals($arrayWithoutLeftButtons, $event->getButtons());
	}

	public function testAdjustSaveAndCloseWithoutSavedOkButton(): void {
		$saveButtonMock = $this->prophesize(InputButton::class);
		$saveButtonMock->getName()->willReturn('_savebutton');
		$buttons = [
			'left' => [
				'group' => [$saveButtonMock->reveal()],
			],
		];
		$event = new ModifyButtonBarEvent($buttons, $this->prophesize(ButtonBar::class)->reveal());
		call_user_func($this->subject, $event);
		self::assertEquals($buttons, $event->getButtons());
	}

	public function testAdjustSaveAndCloseWithSavedOkButton(): void {
		$saveButtonMock = new InputButton();
		$saveButtonMock->setName('_savedok');
		$buttons = [
			'left' => [
				'group' => [
					'button1' => $saveButtonMock,
				],
			],
		];
		$event = new ModifyButtonBarEvent($buttons, $this->prophesize(ButtonBar::class)->reveal());
		call_user_func($this->subject, $event);
		$buttons = $event->getButtons();
		$lastButton = array_pop($buttons['left']['group']);
		self::assertEquals('_saveandclosedok', $lastButton->getName());
		self::assertEquals('1', $lastButton->getValue());
		self::assertEquals(false, $lastButton->getShowLabelText());
		self::assertEquals('title', $lastButton->getTitle());
		self::assertEquals($this->iconMock, $lastButton->getIcon());
	}

}
