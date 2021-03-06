<?php
namespace Awb\Core;

use Awb\Core\Config\SettingsAbstract;

abstract class ModuleAbstract {

    /**
     * Current plugin version
     * 
     * @var string 
     */
    private $version;
    
    /**
     * Module unique key
     * 
     * @var string 
     */
    private $key;

    /**
     * LESS Parser
     * 
     * @var LessParser
     */
    private $lessParser;

    /**
     * Base module directory
     * 
     * @var string
     */
    private $baseDir;
    
    /**
     * Base core module directory
     * 
     * @var string
     */
    private $baseCoreDir;    
    
    /**
     * Default template directory
     * 
     * @var string 
     */
    private $defaultTemplateDir;
    

    /**
     * Default core template directory
     * 
     * @var string 
     */
    private $defaultTemplateCoreDir;
    
    
    /**
     * Current template directory
     * 
     * @var string 
     */
    private $templateDir;
    
    
    /**
     * Module name
     * 
     * @var string 
     */
    private $moduleName;
    
    
    /**
     * Default assets directory
     * 
     * @var string
     */
    private $defaultAssetDir;
    
    /**
     * Default assets core directory
     * 
     * @var string
     */
    private $defaultAssetCoreDir;    
    
    /**
     * Current assets directory
     * 
     * @var string 
     */
    private $assetDir;
    
    /**
     * Plugin settings
     * 
     * @var SettingsAbstract
     */
    private $settings;        
    
    /**
     * Constructor
     */
    public function __construct($baseDir = NULL) {

        $this->lessParser = new LessParser();
        
        $this->baseCoreDir = dirname(dirname(__FILE__));
        $this->defaultTemplateCoreDir = $this->baseCoreDir . '/templates';
        $this->defaultAssetCoreDir = $this->baseCoreDir . '/assets';
        
        $this->setBaseDir($baseDir);
        
        add_action( 'init', array($this, 'init' ));       
    }
    
    public function init() {
        $this->applyLessCss();        
    }
    
    public function applyLessCss() {
        $config = array();
        if ( !empty($this->getSettings()->getConfig()->admin->style) ) {
            $config = $this->getSettings()->objectToArray( $this->getSettings()->getConfig()->admin->style );   
        }                        
        
        if (is_admin()) {
            $this->lessParser->registerAdminLessCss( $this->getAssetPath('less/admin/agp-options.less'), array_merge($config, array(
                'key' => $this->getKey(),
            )));
        }
        
        if (is_admin_bar_showing()) {
            $this->lessParser->registerAdminLessCss( $this->getAssetPath('less/admin/admin-toolbar.less'), array_merge($config, array(
                'key' => $this->getKey(),
            )));            
            $this->lessParser->registerLessCss( $this->getAssetPath('less/admin/admin-toolbar.less'), array_merge($config, array(
                'key' => $this->getKey(),
            )));                        
        }        
    }
    
    /**
     * Gets template content
     * 
     * @param string $name
     * @param string|array $params
     * @return string
     */
    public function getTemplate($name, $params = NULL) {
        ob_start();
        $template = $this->templateDir . '/' . $name . '.php';
        $defaultTemplate = $this->defaultTemplateDir . '/' . $name . '.php';
        $defaultTemplateCore = $this->defaultTemplateCoreDir . '/' . $name . '.php';
        if ( file_exists($template) && is_file($template) ) {
            include ($template);
        } elseif (file_exists($defaultTemplate) && is_file($defaultTemplate) ) {
            include ($defaultTemplate);
        } elseif (file_exists($defaultTemplateCore) && is_file($defaultTemplateCore) ) {
            include ($defaultTemplateCore);
        }
        $result = ob_get_clean();
        return $result;
    }    

    /**
     * Get asset path
     * 
     * @param string $name
     * @return string
     */
    public function getAssetPath($name = NULL) {
        $resultPath = $this->baseDir;
        
        if (empty($name)) {
            if (file_exists($this->assetDir) && is_dir($this->assetDir)) {
                $resultPath = $this->assetDir;        
            } elseif (file_exists($this->defaultAssetDir) && is_dir($this->defaultAssetDir)) {
                $resultPath = $this->defaultAssetDir;        
            } elseif (file_exists($this->defaultAssetCoreDir) && is_dir($this->defaultAssetCoreDir)) {
                $resultPath = $this->defaultAssetCoreDir;        
            }
        } else {
            $asset = $this->assetDir . '/' . $name;
            $defaultAsset = $this->defaultAssetDir . '/' . $name;            
            $defaultAssetCore = $this->defaultAssetCoreDir . '/' . $name;            
            if ( file_exists($asset) && is_file($asset) ) {
                $resultPath = $asset;
            } elseif ( file_exists($defaultAsset) && is_file($defaultAsset) ) {
                $resultPath = $defaultAsset;
            } elseif ( file_exists($defaultAssetCore) && is_file($defaultAssetCore) ) {
                $resultPath = $defaultAssetCore;
            }
        }
        
        return $resultPath;
    }
    
    /**
     * Get asset Url
     * 
     * @param string $name
     * @return string
     */
    public function getAssetUrl($name = NULL) {
        return $this->toUrl( $this->getAssetPath($name) );
    }    
    
    /**
     * Gets debug information
     * 
     * @param all $var
     */
    static public function debug ($var, $echo = true) {
        if (!$echo) {
            ob_start();
        }
        print_r('<pre>');
        print_r($var);
        print_r('</pre>');
        if (!$echo) {
            $result = ob_get_clean();
            return $result;
        }        
    }

    /**
     * Gets url for the specified file path
     * 
     * @param string $file
     * @return string
     */    
    public function toUrl($file = '') {
        
        // Get correct URL and path to wp-content
        $content_url = content_url();
        $content_dir = untrailingslashit( dirname( dirname( get_stylesheet_directory() ) ) );    

        // Fix path on Windows
        $sfile = str_replace( '\\', '/', $file );
        $content_dir = str_replace( '\\', '/', $content_dir );
        
        $result = str_replace( $content_dir, $content_url, $sfile );
        if ( $result == $sfile ) {
            $result = plugin_dir_url($file) . basename($file);
        }
        
        return $result;   
    }
    
    /**
     * Gets curent URL
     * 
     * @global type $wp
     * @return type
     */
    public function getCurrentUrl() {
        global $wp;
        return add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );         
    }    
    
    /**
     * Gets base URL
     * 
     * @return type
     */
    public function getBaseUrl() {
        return $this->toUrl($this->baseDir);
    }
    
    /**
     * Getters and Setters
     */ 

    public function getBaseDir() {
        return $this->baseDir;
    }

    public function getDefaultTemplateDir() {
        return $this->defaultTemplateDir;
    }

    public function getTemplateDir() {
        return $this->templateDir;
    }

    public function getModuleName() {
        return $this->moduleName;
    }

    public function setBaseDir($baseDir) {
        $this->moduleName = NULL;
        $this->defaultTemplateDir = NULL;
        $this->defaultAssetDir = NULL;
        $this->templateDir = NULL;
        $this->assetDir = NULL;        

        $this->baseDir = $baseDir;
        if (!empty($this->baseDir)) {
            $this->moduleName = basename( $this->baseDir );        
            $this->defaultTemplateDir = $this->baseDir . '/templates';
            $this->defaultAssetDir = $this->baseDir . '/assets';
            $this->templateDir = get_stylesheet_directory() . '/templates/'. $this->moduleName;
            $this->assetDir = $this->templateDir . '/assets';                    
        }
        return $this;
    }

    public function setDefaultTemplateDir($defaultTemplateDir) {
        $this->defaultTemplateDir = $defaultTemplateDir;
        return $this;
    }

    public function setTemplateDir($templateDir) {
        $this->templateDir = $templateDir;
        return $this;
    }

    public function setModuleName($moduleName) {
        $this->moduleName = $moduleName;
        return $this;
    }

    public function getDefaultAssetDir() {
        return $this->defaultAssetDir;
    }

    public function getAssetDir() {
        return $this->assetDir;
    }

    public function setDefaultAssetDir($defaultAssetDir) {
        $this->defaultAssetDir = $defaultAssetDir;
        return $this;
    }

    public function setAssetDir($assetDir) {
        $this->assetDir = $assetDir;
        return $this;
    }
    
    public function getBaseCoreDir() {
        return $this->baseCoreDir;
    }

    public function getDefaultAssetCoreDir() {
        return $this->defaultAssetCoreDir;
    }

    public function setBaseCoreDir($baseCoreDir) {
        $this->baseCoreDir = $baseCoreDir;
        return $this;
    }

    public function setDefaultAssetCoreDir($defaultAssetCoreDir) {
        $this->defaultAssetCoreDir = $defaultAssetCoreDir;
        return $this;
    }

    public function getDefaultTemplateCoreDir() {
        return $this->defaultTemplateCoreDir;
    }

    public function setDefaultTemplateCoreDir($defaultTemplateCoreDir) {
        $this->defaultTemplateCoreDir = $defaultTemplateCoreDir;
        return $this;
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }
    
    public function getLessParser() {
        return $this->lessParser;
    }

    public function getSettings() {
        return $this->settings;
    }

    public function setSettings(SettingsAbstract $settings) {
        $this->settings = $settings;
        return $this;
    }
    
    public function getVersion() {
        return $this->version;
    }

    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

}
