<?php
/*
 * Plugin Name: User Meta Lite
 * Plugin URI: http://user-meta.com
 * Description: A well designed, features reached and easy to use user management plugin.
 * Version: 1.2
 * Author: Khaled Hossain
 * Author URI: http://khaledsaikat.com
 * Text Domain: user-meta
 * Domain Path: /helpers/languages
 */
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>User Meta Lite plugin requires <strong>  PHP 5.4.0</strong> or above. Current PHP version: ' . PHP_VERSION . '</p></div>';
    });
    
    return;
}

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    exit('Please don\'t access this file directly.');
}

require __DIR__ . '/vendor/autoload.php';

if (! class_exists('userMeta')) {

    class userMeta extends UserMeta\pluginFramework
    {

        public $title;

        public $version;

        public $name = 'user-meta';

        public $prefix = 'um_';

        public $prefixLong = 'user_meta_';

        public $website = 'http://user-meta.com';

        public function __construct()
        {
            $this->pluginSlug = plugin_basename(__FILE__);
            $this->pluginPath = dirname(__FILE__);
            $this->file = __FILE__;
            $this->modelsPath = $this->pluginPath . '/models/';
            $this->controllersPath = $this->pluginPath . '/controllers/';
            $this->viewsPath = $this->pluginPath . '/views/';
            
            $this->pluginUrl = plugins_url('', __FILE__);
            $this->assetsUrl = $this->pluginUrl . '/assets/';
            
            $pluginHeaders = array(
                'Name' => 'Plugin Name',
                'Version' => 'Version'
            );
            
            $pluginData = get_file_data($this->file, $pluginHeaders);
            
            $this->title = $pluginData['Name'];
            $this->version = $pluginData['Version'];
            
            // Load Plugins & Framework modal classes
            global $pluginFramework, $userMetaCache;
            $this->cacheName = 'userMetaCache';
            $userMetaCache = new stdClass();
            
            $this->loadModels($this->modelsPath);
            $this->loadModels($pluginFramework->modelsPath);
        }
    }
}

global $userMeta;
$userMeta = new userMeta();
$userMeta->init();
