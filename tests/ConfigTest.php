<?php
use PHPUnit\Framework\TestCase;

require_once dirname( __FILE__ ) . "/../vendor/autoload.php";

class ConfigTest extends TestCase {

	/**
	 * @return \WikiXL\MultiVersion\MultiVersion
	 * @throws Exception
	 */
	public function getMultiVersionClass() {
		$dataDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data/';
		$defaultConfigFile = $dataDir . 'default-config.php';
		$configDir = $dataDir . 'conf';

		return \WikiXL\MultiVersion\MultiVersion::factory( $defaultConfigFile, $configDir );
	}

	public function testMainConfig() {
		$configObjectRetrieved = $this->getMultiVersionClass()->getMainConfig();
		$this->assertIsArray( $configObjectRetrieved );
		$this->assertArrayHasKey( 'baseurlpath', $configObjectRetrieved );
		$this->assertEquals( 'https://saml.test.nl', $configObjectRetrieved['baseurlpath'] );
		$this->assertEquals( 'qwerty12345790', $configObjectRetrieved['auth.adminpassword'] );
		$this->assertEquals( '.test.nl', $configObjectRetrieved['session.cookie.domain'] );
		$this->assertCount(3, $configObjectRetrieved['metadata.sources']);
	}

	public function testMetaRefreshProdConfig() {
		$configObjectRetrieved = $this->getMultiVersionClass()->getMetaRefreshConfig();
		$this->assertIsArray( $configObjectRetrieved );
		$this->assertArrayHasKey( 'sets', $configObjectRetrieved );
		$this->assertCount(2, $configObjectRetrieved[ 'sets' ] );
		$this->assertEquals([
			'cron' => 'hourly',
			'sources' => [
				'https://metadata.prod.test.nl/idp-metadata.xml'
			],
			'expireAfter' => 60*60,
			'outputDir' => 'metadata/federation/some-sp'
		], $configObjectRetrieved[ 'sets' ]['some-sp']);
	}

	public function testCronConfig() {
		$configObjectRetrieved =  $this->getMultiVersionClass()->getCronConfig();
		$this->assertIsArray( $configObjectRetrieved );
		$this->assertArrayHasKey( 'key', $configObjectRetrieved );
		$this->assertArrayHasKey( 'allowed_tags', $configObjectRetrieved );
		$this->assertArrayHasKey( 'sendmail', $configObjectRetrieved );
		$this->assertArrayHasKey( 'debug_message', $configObjectRetrieved );
		$this->assertEquals([
			'key' => 'asdbnasdasd',
			'allowed_tags' => [
				'daily', 'hourly', 'frequent'
			],
			'sendmail' => false,
			'debug_message' => true
		],
		$configObjectRetrieved);
	}

}
