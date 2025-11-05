<?php

/**
 * PHPUnit Bootstrap File
 *
 * This file creates mock SimpleSAML classes for testing purposes
 * since SimpleSAMLphp is not installed in the test environment.
 */

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Create SimpleSAML namespace if it doesn't exist
if (!class_exists('\SimpleSAML\Logger')) {
    /**
     * Mock Logger class for testing
     * This prevents errors when MultiVersion tries to log messages
     */
    class SimpleSAML_Logger {
        public static function error($message) {
            // Mock implementation - do nothing in tests
            error_log('[SimpleSAML Mock] ERROR: ' . $message);
        }

        public static function warning($message) {
            error_log('[SimpleSAML Mock] WARNING: ' . $message);
        }

        public static function info($message) {
            error_log('[SimpleSAML Mock] INFO: ' . $message);
        }
    }

    // Create namespace alias for the mock
    if (!class_exists('\SimpleSAML\Logger')) {
        class_alias('SimpleSAML_Logger', '\SimpleSAML\Logger');
    }
}
