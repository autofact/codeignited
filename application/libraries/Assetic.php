<?php
spl_autoload_register(function($class_name) {
    if (strpos($class_name, 'Assetic') === 0)
        require FCPATH .'vendor/kriswallsmith/assetic/src/' . str_replace('\\', '/', $class_name.'.php');
    elseif (strpos($class_name, 'Symfony') === 0)
    	require FCPATH .'vendor/symfony/process/' . str_replace('\\', '/', $class_name.'.php');
    elseif ($class_name === 'CssMin' || $class_name === 'JSMinPlus')
        require FCPATH . "vendor/nitra/php-min/$class_name/$class_name.php";
});

require 'assetic/JsCollection.php';

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetWriter;
use Assetic\Asset\StringAsset;
use Assetic\Filter\StylusFilter;
use Assetic\Asset\AssetCache;
use Assetic\Cache\FilesystemCache;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\JSMinPlusFilter;
use Assetic\Filter\CoffeeScriptFilter;

class CI_Assetic {
    
    private $CI;
    private $config;
    private $writer;
    private $collections = array(
        'js' => array(),
        'css' => array()
    );
    private $cache;
    
    function __construct() {
        $this->CI = & get_instance();
        
        $this->CI->load->helper('url');
        
        // Loads the assetic config (assetic.php under application/config/)
        $this->CI->load->config('assetic');
        $config = & get_config();
        
        $this->config = !empty($config['assetic']) ? $config['assetic'] : array(); 
        
        if (!isset($this->config['static_dir']))
        	$this->config['static_dir'] = 'static/';
        else if (substr($this->config['static_dir'], -1) !== '/')
            $this->config['static_dir'] .= '/';
        
	    if (!isset($this->config['node_modules_path']))
	    	$this->config['node_modules_path'] = array(FCPATH . 'node_modules');
	    
	    if (!isset($this->config['coffescript_path']))
	        $this->config['coffescript_path'] = 'node_modules/coffee-script/bin/coffee';
	    
        $this->writer = new AssetWriter($this->config['static_dir']);
        $this->cache = new FilesystemCache(isset($this->config['cache_dir'])
                ? $this->config['cache_dir']
                : FCPATH . 'cache');
    }
    
    private function add_asset($asset, $type, $group) {
        $group .= '.' . $type;
        if (!isset($this->collections[$type][$group]))
            $this->collections[$type][$group] = $type === 'js'
                ? new JsCollection()
                : new AssetCollection();
        $this->collections[$type][$group]->add($asset);
    }
    
    public function add_file($filename, $type, $group) {
        if(strpos($filename, '://') === false) {
	    	$filters = array();
	        if (ends_with($filename, '.styl'))
	        	$filters[] = new StylusFilter(
	        			$this->config['node_path'], $this->config['node_modules_path']);
	        elseif (ends_with($filename, '.coffee'))
	        	$filters[] = new CoffeeScriptFilter($this->config['coffescript_path']);
            $asset = new FileAsset($filename, $filters);
        } else
            $asset = new HttpAsset($filename);
        $this->add_asset($asset, $type, $group);
    }
    
    public function add_script($content, $group) {
        $this->add_asset(new StringAsset($content), 'js', $group);
    }
    
    public function add_js($filename, $group) {
        $this->add_file($filename, 'js', $group);
    }
    
    public function add_style($content, $group) {
        $this->add_asset(new StringAsset($content), 'css', $group);
    }
    
    public function add_css($filename, $group) {
        $this->add_file($filename, 'css', $group);
    }
    
    private function process_collection(AssetCollection $collection, $type) {
        $result = array();
        foreach ($collection as $item)
            if ($item instanceof AssetCollection)
                $result = array_merge($result, $this->process_collection($item, $type));
            elseif ($item instanceof StringAsset)
                $result[] = array('content' => $item->dump());
            else {
            	if (count($item->getFilters()) > 0) {
            		$url = $item->getTargetPath() . ".$type";
            		$item->setTargetPath($url);
            		$url = $this->config['static_dir'] . $url;
            		$this->writer->writeAsset($item);
            	} else
	                $url = $item->getSourceRoot() . '/' . $item->getSourcePath();
                if(strpos($url, '://') === false)
                    $url = base_url($url);
                $result[] = array('url' => $url);
            }
        
        return $result;
    }
    
    private function get_debug_assets($type) {
        $result = array();
        
        foreach ($this->collections[$type] as $collection)
            $result = array_merge($result, $this->process_collection($collection, $type));
        
        return $result;
    }
    
    private function get_static_assets($type) {
        $result = array();
        
        foreach ($this->collections[$type] as $filename => $collection) {
            $cached = new AssetCache($collection, $this->cache);
            $cached->setTargetPath($filename);
            if ($type === 'css')
                $cached->ensureFilter(new CssMinFilter());
            elseif ($type === 'js')
                $cached->ensureFilter(new JSMinPlusFilter());
            $this->writer->writeAsset($cached);

            $file = $this->config['static_dir'] . $filename;
            $result[] = array('url' => base_url($file));
        }
        
        return $result;
    }
    
    public function get_assets($type, $debug) {
        if ($debug)
            return $this->get_debug_assets($type);
        return $this->get_static_assets($type);
    }
    
    public function get_javascript_assets($debug) {
        return $this->get_assets('js', $debug);
    }
    
    public function get_css_assets($debug) {
        return $this->get_assets('css', $debug);
    }
}