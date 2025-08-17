<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides the configuration manager for this plugin.
 *
 * @package   tool_mergeusers
 * @author    Jordi Pujol-Ahulló <jordi.pujol@urv.cat>
 * @copyright 2013 onwards to Universitat Rovira i Virgili (https://www.urv.cat)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mergeusers\local;

/**
 * Wrapper class for the configuration settings of the merge user utility.
 *
 * This class loads the standard settings from the file:
 * 1. <code>lib/config.php</code> and then loads the local settings from the file:
 * 2. Web setting <code>tool_mergeusers/customdbsettings</code>. Note this custom
 *  settings overwrite the default settings. When upgraded the plugin, it loads
 *  the existing content from <code>lib/config.local.php</code> if it exists.
 *
 * Important: The reference to any table name must be present without the $CFG->prefix.
 *
 * These settings, once merged, must have a content similar to this PHP structure:
 * <pre>
 * [
 *     'gathering' => 'ClassName',
 *     'exceptions' => ['tablename1', 'tablename2'],
 *     'compoundindexes' => [
 *         'tablename' => [
 *             'userfield' => 'user-related_fieldname_on_tablename',
 *             'otherfield' => 'other_fieldname_on_tablename',
 *             ['both' => true,]
 *         ],
 *     ],
 *     'userfieldnames' => [
 *         'tablename' => ['user-realted-fieldname1', 'user-related-fieldname2'],
 *     ],
 * ]
 * </pre>
 *
 * If the key 'both' appears, means that both columns are user-related and must be searched for
 * both.
 *
 * @property-read array gathering Gathering instance to use for the CLI script.
 * @property-read array exceptions List of tables that are excluded from processing.
 * @property-read array compoundindexes List of tables with compound indexes, including both from database schema and also
 * from PHP Moodle code.
 * @property-read array userfieldnames List of tables and "default" one, with the list of column names within that table
 * to consider a user-related field. "default" table name applies to any table not listed explicitly on this list.
 * @property-read array tablemergers List of table mergers, and a "default" one. "default" table merger applies to any table
 * not explicitly listed on this list.
 * @property bool alwaysrollback Proceed with the merge but rollback it at the very last moment.
 * @property bool debugdb Show database debug output.
 */
class config {
    /** @var string Defines what is expected to be an empty setting. */
    private const EMPTY_SETTING = '{}';
    /** @var config singleton instance. */
    private static $instance = null;

    /** @var array the whole set of settings, with the default and custom ones. */
    private array $config;
    /** @var array the settings from the config/config.php. */
    private array $defaultconfig;
    /** @var ?array the custom settings from the admin setting. */
    private ?array $customconfig;

    /**
     * Singleton method.
     *
     * @return config singleton instance.
     */
    public static function instance(): config {
        if (is_null(self::$instance) || defined('PHPUNIT_TEST') || defined('BEHAT_SITE_RUNNING')) {
            self::$instance = new config();
        }

        return self::$instance;
    }

    /**
     * Private constructor for the singleton.
     *
     * @throws \dml_exception
     */
    private function __construct() {
        $this->defaultconfig = $this->get_default_config();
        $this->customconfig = $this->get_custom_config();
        $this->config = array_replace_recursive($this->defaultconfig, $this->customconfig);
    }

    /**
     * Informs the default config settings from config/config.php file.
     *
     * @return array
     */
    private function get_default_config(): array {
        return include(dirname(__DIR__, 2) . '/config/config.php');
    }

    /**
     * Informs the custom settings. If empty, it informs an empty instance.
     *
     * @return array
     * @throws \dml_exception
     */
    private function get_custom_config(): array {
        return jsonizer::from_json(get_config('tool_mergeusers', 'customdbsettings') ?: self::EMPTY_SETTING) ?: [];
    }

    /**
     * Provides the JSON string from the config/config.php file.
     *
     * @return string
     */
    public function json_from_default_config(): string {
        return jsonizer::to_json($this->defaultconfig);
    }

    /**
     * Provides the JSON string from the config/config.local.php file, if exists, or "{}" otherwise.
     *
     * @return string
     */
    public function json_from_config_local_php_file(): string {
        $oldconfiglocalphpfile = dirname(__DIR__, 2) . '/config/config.local.php';
        if (!file_exists($oldconfiglocalphpfile)) {
            return self::EMPTY_SETTING;
        }
        $oldconfig = include($oldconfiglocalphpfile);
        return jsonizer::to_json($oldconfig);
    }

    /**
     * Provides the JSON expression for the calculated configuration, including the default and custom settings.
     *
     * The old config/config.local.php is not considered here.
     *
     * @return string
     */
    public function json_from_calculated_config(): string {
        return jsonizer::to_json($this->config);
    }

    /**
     * Accessor to properties from the current config as attributes of an standard object.
     *
     * @param string $name name of attribute.
     * @return mixed null if $name is not a valid property name of the current configuration;
     * string or array having the value of the $name property.
     */
    public function __get(string $name): mixed {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    /**
     * Updates the setting for alwaysrollback and debug, only.
     *
     * @param string $name setting name.
     * @param mixed $value value to set.
     * @return void
     */
    public function __set(string $name, mixed $value) {
        switch ($name) {
            case 'alwaysrollback':
            case 'debugdb':
                $this->config[$name] = $value;
                break;
        }
    }
}
