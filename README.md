# SimpleSamlPHP MultiVersion
This composer package enables to have multiple configurations on the same SimpleSamlPHP codebase.

# How to install
* Require this package into your SimpleSamlPHP root
* Copy sample_conf/ to config/ directory. 
* Create your configuration. 
  * You can use tests/data/conf as a template.
  * Rename conf to match the SAML_DOMAIN environment variable
* Set environment the SAML_ENV and SAML_DOMAIN in your webserver
  * Please note; if you are using the cron module; make sure you set the environment variables before running the actual command. 
  * E.g.: `SAML_ENV=test SAML_DOMAIN=domain php modules/cron/bin/cron.php -t hourly`

# Running tests
Running library tests can be done through composer by running `composer test-multiversion`

# Todo
Support more modules to be configured through YAML

# Contribute
Feel free to contribute to this project by using pull-requests.
