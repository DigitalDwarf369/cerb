<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class CerbEval_UI_Setup extends CerbTestBase {
	function testLoginKina() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->logInAs('kina@cerb.example', 'cerb');
		
		$this->assertTrue(true);
	}
	
	function testScreenshotHome() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('table.worklistBody'))
		);
		
		usleep(500000);
		
		$driver->takeScreenshot("screenshots/home.png");
		
		$this->assertTrue(true);
	}
	
	function testScreenshotTicket() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/profiles/ticket/1');
		
		$driver->findElement(WebDriverBy::tagName('body'));
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('conversation'))
		);
		
		$driver->getKeyboard()->sendKeys('a');
		
		usleep(500000);
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('conversation'))
		);
		
		usleep(500000);
		
		$driver->takeScreenshot("screenshots/profile.png");
		
		$this->assertTrue(true);
	}
	
	function testScreenshotPluginLibrary() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/plugins/library');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('viewplugin_library'))
		);
		
		usleep(500000);
		
		$driver->takeScreenshot("screenshots/plugin_library.png");
		
		$this->assertTrue(true);
	}
	
	public function testLogoutKina() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
		
		$this->assertTrue(true);
	}
};
