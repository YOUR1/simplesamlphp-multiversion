<?php

namespace WikiXL\MultiVersion;

class MultiVersion {

	/** @var string */
	public const ENV_PROD = 'prod';

	/** @var string */
	public const ENV_TEST = 'test';

	/** @var string */
	public const ENV_DEV = 'dev';

	/** @var MultiVersion */
	private static $instance;

	/**
	 * @var array|mixed
	 */
	private $yamlMainConfig = [];

	/**
	 * @var array|mixed
	 */
	private $yamlAuthsourcesConfig = [];

	/**
	 * @var string
	 */
	private $defaultConfigFile;

	/**
	 * @param string $defaultConfigFile
	 * @param string $yamlConfigDirectory
	 */
	public function __construct( string $defaultConfigFile, string $yamlConfigDirectory ) {

		if ( ! file_exists( $defaultConfigFile ) ) {
			\SimpleSAML\Logger::error(
				"Default configuration file ({$defaultConfigFile}) could not be found."
			);
		}

		if ( ! is_dir( $yamlConfigDirectory ) ) {
			\SimpleSAML\Logger::error(
				"Given directory ({$yamlConfigDirectory}} does not exist."
			);
		}

		$yamlConfigFile = $yamlConfigDirectory . DIRECTORY_SEPARATOR . "config.yaml";
		$yamlAuthsourcesFile = $yamlConfigDirectory . DIRECTORY_SEPARATOR . "authsources.yaml";

		if ( ! file_exists( $yamlConfigFile ) ) {
			\SimpleSAML\Logger::error(
				"Yaml configuration file ({$$yamlConfigFile}) could not be found."
			);
		}

		if ( ! file_exists( $yamlAuthsourcesFile ) ) {
			\SimpleSAML\Logger::error(
				"Yaml authsources configuration file ({$yamlAuthsourcesFile}) could not be found."
			);
		}

		// Require the default configuration file, so the $config variable will be
		// loaded.
		require_once( $defaultConfigFile );

		// Compile file contents
		$yamlMainFileContents = @file_get_contents( $yamlConfigFile );
		$yamlAuthSourcesContents = @file_get_contents( $yamlAuthsourcesFile );

		// If the inheritance object are "blank" (e.g. only comments), fall back
		$this->yamlMainConfig = \Symfony\Component\Yaml\Yaml::parse( $yamlMainFileContents ) ?? [];
		$this->yamlAuthsourcesConfig = \Symfony\Component\Yaml\Yaml::parse( $yamlAuthSourcesContents ) ?? [];

		// Set default config
		$this->defaultConfigFile = $defaultConfigFile;
	}

	/**
	 * The YAML configuration can't be empty, otherwise there isn't anything
	 * to configure.
	 *
	 * This checks both the main and auth sources config files.
	 */
	private function validateYamlConfigState() : void {
		if ( empty( $this->yamlMainConfig ) || empty ( $this->yamlAuthsourcesConfig ) ) {
			\SimpleSAML\Logger::error(
				"Configuration missmatch. The YAML configuration container is empty."
			);
		}
	}

	/**
	 * Returns the compiled main config object.
	 *
	 * @param string|null $environment
	 * @global $config
	 *
	 * @return array
	 */
	public function getMainConfig( string $environment = null ) : array {
		// Validate config state
		$this->validateYamlConfigState();

		// If no environment parameter was passed, fall back to prod
		if ( $environment == null ) {
			$environment = self::ENV_PROD;
		}

		$config = include $this->defaultConfigFile;

		// We want to override the default configuration with the custom
		// added configuration.
		if ( isset ( $this->yamlMainConfig[ 'config' ] ) ) {
			$flattenedConfigObject = $this->flattenYamlConfigArray( $this->yamlMainConfig[ 'config' ] );
			$config = array_merge( $config, $flattenedConfigObject );
		}

		// Environment specific configuration
		if ( isset ( $this->yamlMainConfig[ 'config_' . $environment ] ) ) {
			$flattenedConfigObject = $this->flattenYamlConfigArray( $this->yamlMainConfig[ 'config_' . $environment ] );
			$config = array_merge( $config, $flattenedConfigObject );
		}

		// Override some base configuration keys
		$config['baseurlpath'] = $this->getBaseUrlPath( $environment );

		return $config;

	}

	/**
	 * Returns the compiled metaRefresh configuration object.
	 *
	 * @param string|null $environment
	 *
	 * @return array|array[]
	 */
	public function getMetaRefreshConfig( string $environment = null ) : array {
		// Validate config state
		$this->validateYamlConfigState();

		// If no environment parameter was passed, fall back to prod
		if ( $environment == null ) {
			$environment = self::ENV_PROD;
		}

		$config = [
			'sets' => [ ]
		];

		$authsources = $this->yamlAuthsourcesConfig[ 'authsources' ] ?? [];
		$defaultOutputDir = $this->yamlAuthsourcesConfig[ 'metarefresh'][ 'basedir' ] ?? 'metadata/federation';

		// Loop over all auth sources to retrieve the identifier and config object itself
		foreach ( $authsources as $authsourceIdentifier => $authsourceConfigObjects ) {
			// The auth source must have the metarefersh attribute
			// otherwise it's not needed to refresh
			if ( ! isset ( $authsourceConfigObjects[ 'metarefresh' ]) ) {
				continue;
			}

			$metaRefreshConfiguration = $authsourceConfigObjects[ 'metarefresh' ];

			// If only one value was passed to the cron string; make it an array
			$cronConfiguration = is_string($metaRefreshConfiguration[ 'cron' ])
				? [ $metaRefreshConfiguration[ 'cron' ] ]
				: $metaRefreshConfiguration[ 'cron' ];

			// Finally build up the set
			$config[ 'sets' ][ $authsourceIdentifier ] = [
				'cron' => $cronConfiguration,
				'sources' => [
					[
						'src' => $metaRefreshConfiguration[ $environment ]
					]
				],
				'expireAfter' => 60*60,
				'outputDir' => $defaultOutputDir . '/' . $authsourceIdentifier
			];
		}

		return $config;

	}

	/**
	 * Returns the compiled authsources.php configuration object.
	 *
	 * @param string|null $environment
	 *
	 * @return array
	 */
	public function getAuthSourcesConfig( string $environment = null ) : array {
		// Validate config state
		$this->validateYamlConfigState();

		// If no environment parameter was passed, fall back to prod
		if ( $environment == null ) {
			$environment = self::ENV_PROD;
		}

		$config = [];
		$authsources = $this->yamlAuthsourcesConfig[ 'authsources' ] ?? [];

		// Loop over all auth sources to retrieve the identifier and config object itself
		foreach ( $authsources as $authsourceIdentifier => $authsourceConfigObjects ) {
			$authSourceConfig = $authsourceConfigObjects['config'] ?? false;
			// The IDP key needs to have the current environment as value; otherwise we can't properly
			// configure this authsource. Thus check if it's set, and also validate the config
			// object itself.
			if ( isset ( $authSourceConfig[ 'idp' ][ $environment ] ) && $authSourceConfig ) {
				// Override the IDP key with the current environment.
				$authSourceConfig[ 'idp' ] = $authSourceConfig[ 'idp' ][ $environment ];
				// Finally append the config object.
				$config[ $authsourceIdentifier ] = $authSourceConfig;
			}
		}

		return $config;

	}

	/**
	 * Returns the compiled module_cron.php configuration object.
	 *
	 * @return array
	 */
	public function getCronConfig() : array {
		// Validate config state
		$this->validateYamlConfigState();

		// We need an actual config object to make this thing work
		$cronConfig = $this->yamlMainConfig[ 'modules' ][ 'cron' ] ?? false;
		if ( ! $cronConfig ) {
			\SimpleSAML\Logger::error(
				"There was no cron configuration."
			);
		}

		$cronKey = $cronConfig[ 'key' ] ?? false;
		$allowedTags = $cronConfig[ 'allowed_tags' ] ?? false;
		$sendmail = $cronConfig[ 'sendmail' ] ?? false;
		$debug_message = $cronConfig[ 'debug_message'] ?? false;

		// The key and allowed_tags are mandatory for the cron module to work
		// so lets require them
		if ( ! $cronKey || ! $allowedTags ) {
			\SimpleSAML\Logger::error(
				"Cron configuration is missing key or allowed_tags."
			);
		}

		return [
			'key' => $cronKey,
			'allowed_tags' => $allowedTags,
			'sendmail' => $sendmail,
			'debug_message' => $debug_message
		];
	}

	/**
	 * @param string $environment
	 *
	 * @return string
	 */
	private function getBaseUrlPath( string $environment ) : string {

		if ( ! isset( $this->yamlMainConfig[ 'environments' ][ $environment ] ) ) {
			\SimpleSAML\Logger::error(
				"Environment: $environment is not configured."
			);
		}

		$useSsl = $this->yamlMainConfig[ 'use_ssl' ] ?? true;
		$environment = $this->yamlMainConfig[ 'environments' ][ $environment ];
		$topDomain = $this->yamlMainConfig[ 'config_' . $environment ]['top_domain'] ?? $this->yamlMainConfig[ 'top_domain' ];

		return ( $useSsl ? "https" : "http" ) . "://{$environment}.$topDomain";

	}

	/**
	 * Flattens the YAML Config array.
	 * So ['auth' => [ 'adminpassword' => 'test' ] ] becomes ['auth.adminpassword' => 'test']
	 * To keep in line with the SimpleSamlPHP configuration
	 *
	 * @param array $array
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function flattenYamlConfigArray( array $array, $prefix = '' ) : array {
		$result = [];
		foreach ( $array as $key => $value ) {
			if ( is_int( $key ) ) {
				$result[ $prefix ] = $array;
			} else {
				$new_key = $prefix . ( empty( $prefix ) ? '' : '.' ) . $key;
				if ( is_array( $value ) ) {
					$result = array_merge( $result,
						$this->flattenYamlConfigArray( $value,
							$new_key ) );
				} else {
					$result[$new_key] = $value;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $defaultConfig
	 * @param string $yamlConfigurationDirectory
	 *
	 * @return MultiVersion
	 */
	public static function factory( string $defaultConfig, string $yamlConfigurationDirectory ) : MultiVersion {
		if ( !isset ( self::$instance ) ) {
			self::$instance = new self( $defaultConfig, $yamlConfigurationDirectory );
		}
		return self::$instance;
	}
}

