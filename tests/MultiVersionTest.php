<?php

use PHPUnit\Framework\TestCase;

class MultiVersionTest extends TestCase {

	/**
	 * @return \WikiXL\MultiVersion\MultiVersion
	 * @throws Exception
	 */
	public function getMultiVersionClass() {
		$dataDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data/';
		$defaultConfigFile = $dataDir . 'default-config.php';
		$configDir = $dataDir . 'conf';

		return \WikiXL\MultiVersion\MultiVersion::factory($defaultConfigFile, $configDir);
	}

	public function testMainConfigProduction() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_PROD);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('baseurlpath', $configObjectRetrieved);
		$this->assertEquals('https://saml.test.nl', $configObjectRetrieved['baseurlpath']);
		$this->assertEquals('qwerty12345790', $configObjectRetrieved['auth.adminpassword']);
		$this->assertEquals('.test.nl', $configObjectRetrieved['session.cookie.domain']);
		$this->assertCount(3, $configObjectRetrieved['metadata.sources']);
	}

	public function testMainConfigTest() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_TEST);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('baseurlpath', $configObjectRetrieved);
		$this->assertEquals('https://samltest.test.nl', $configObjectRetrieved['baseurlpath']);
		$this->assertEquals('qwerty12345790', $configObjectRetrieved['auth.adminpassword']);
		$this->assertEquals('.test.nl', $configObjectRetrieved['session.cookie.domain']);
	}

	public function testMainConfigDevelopment() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_DEV);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('baseurlpath', $configObjectRetrieved);
		$this->assertEquals('https://samldev.test.nl', $configObjectRetrieved['baseurlpath']);
	}

	public function testMetaRefreshProdConfig() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getMetaRefreshConfig(\WikiXL\MultiVersion\MultiVersion::ENV_PROD);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('sets', $configObjectRetrieved);
		$this->assertCount(2, $configObjectRetrieved['sets']);

		$this->assertEquals([
			'cron' => ['hourly'],
			'sources' => [
				['src' => 'https://metadata.prod.test.nl/idp-metadata.xml']
			],
			'expireAfter' => 60 * 60,
			'outputDir' => 'metadata/federation/some-sp'
		], $configObjectRetrieved['sets']['some-sp']);
	}

	public function testMetaRefreshTestConfig() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getMetaRefreshConfig(\WikiXL\MultiVersion\MultiVersion::ENV_TEST);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('sets', $configObjectRetrieved);

		$this->assertEquals([
			'cron' => ['hourly'],
			'sources' => [
				['src' => 'https://metadata.test.test.nl/idp-metadata.xml']
			],
			'expireAfter' => 60 * 60,
			'outputDir' => 'metadata/federation/some-sp',
			'outputFormat' => 'flatfile'
		], $configObjectRetrieved['sets']['some-sp']);
	}

	public function testAuthSourcesConfigProduction() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getAuthSourcesConfig(\WikiXL\MultiVersion\MultiVersion::ENV_PROD);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('some-sp', $configObjectRetrieved);
		$this->assertArrayHasKey('another-sp', $configObjectRetrieved);

		// Verify IDP is correctly set for production
		$this->assertEquals('https://some-sp-prod-url.com', $configObjectRetrieved['some-sp']['idp']);
		$this->assertEquals('https://sts.windows.net/bcea53ca-527b-486b-a115-a33c3db3cc9e/', $configObjectRetrieved['another-sp']['idp']);
	}

	public function testAuthSourcesConfigTest() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getAuthSourcesConfig(\WikiXL\MultiVersion\MultiVersion::ENV_TEST);

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('some-sp', $configObjectRetrieved);

		// Verify IDP is correctly set for test
		$this->assertEquals('https://some-sp-test-url.com', $configObjectRetrieved['some-sp']['idp']);
	}

	public function testCronConfig() {
		$multiVersion = $this->getMultiVersionClass();
		$configObjectRetrieved = $multiVersion->getCronConfig();

		$this->assertIsArray($configObjectRetrieved);
		$this->assertArrayHasKey('key', $configObjectRetrieved);
		$this->assertArrayHasKey('allowed_tags', $configObjectRetrieved);
		$this->assertArrayHasKey('sendmail', $configObjectRetrieved);
		$this->assertArrayHasKey('debug_message', $configObjectRetrieved);

		$this->assertEquals([
			'key' => 'asdbnasdasd',
			'allowed_tags' => [
				'daily', 'hourly', 'frequent'
			],
			'sendmail' => false,
			'debug_message' => true
		], $configObjectRetrieved);
	}

	public function testFactoryReturnsSameInstance() {
		$dataDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data/';
		$defaultConfigFile = $dataDir . 'default-config.php';
		$configDir = $dataDir . 'conf';

		$instance1 = \WikiXL\MultiVersion\MultiVersion::factory($defaultConfigFile, $configDir);
		$instance2 = \WikiXL\MultiVersion\MultiVersion::factory($defaultConfigFile, $configDir);

		$this->assertSame($instance1, $instance2, 'Factory should return the same instance (singleton pattern)');
	}

	public function testConfigOverrideWithEnvironments() {
		$multiVersion = $this->getMultiVersionClass();

		// Test that environment-specific base URL paths are correctly generated
		$prodConfig = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_PROD);
		$testConfig = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_TEST);
		$devConfig = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_DEV);

		$this->assertNotEquals($prodConfig['baseurlpath'], $testConfig['baseurlpath']);
		$this->assertNotEquals($prodConfig['baseurlpath'], $devConfig['baseurlpath']);
		$this->assertNotEquals($testConfig['baseurlpath'], $devConfig['baseurlpath']);

		// Verify all have the same domain suffix
		$this->assertStringContainsString('test.nl', $prodConfig['baseurlpath']);
		$this->assertStringContainsString('test.nl', $testConfig['baseurlpath']);
		$this->assertStringContainsString('test.nl', $devConfig['baseurlpath']);
	}

	public function testMetadataSourcesArray() {
		$multiVersion = $this->getMultiVersionClass();
		$config = $multiVersion->getMainConfig(\WikiXL\MultiVersion\MultiVersion::ENV_PROD);

		$this->assertIsArray($config['metadata.sources']);
		$this->assertGreaterThan(0, count($config['metadata.sources']));

		foreach ($config['metadata.sources'] as $source) {
			$this->assertIsArray($source);
			$this->assertArrayHasKey('type', $source);
		}
	}
}
