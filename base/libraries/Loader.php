<?php                      
if( !defined('BASE') ) exit('Access Denied!');

/* SVN FILE: $Id: Loader.php $Rev: 15 27-09-2009 22:31 develturk $ */

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 Minimalist software for PHP 5.2.4 or newer
 *
 * @package         Obullo
 * @author          Obullo.com  
 * @subpackage      Base.libraries        
 * @copyright       Copyright (c) 2009 Ersin Güvenç.
 * @license         http://www.opensource.org/licenses/gpl-3.0.html GPL  
 * @filesource
 */ 
 
/**
 * Loader Class (Obullo Loader Pattern) (c) 2009
 *
 * Load obullo library,model,view.. files
 *
 * @package         Obullo 
 * @subpackage      Base.libraries     
 * @category        Loader
 * @version         0.1
 * @version         0.2 added model and load db function
 * @version         0.3 added static properties for database(),model(),library()
 *                      added load_DB variable (Model database load on/off).
 *                      added asn_to_models(),helper(),dir()
 * @version         0.4 renamed static functions ob::instance(),ob::register()..
 *                  added static param to library func.Added __construct support
 *                  to library.
 * @version         0.5 Changed direcory structure added $GLOBALS['d'] (directory)
 */


 
Class LoaderException extends CommonException {}

require(APP.'extends'.DS.'Ob_user'.EXT);
                
Class loader extends user {  

    /**
    * Obullo Models
    * Store all loaded Models
    * 
    * @var array
    */
    public $mods = array();
    
    /**
    * Obullo Libraries
    * Store all loaded Libraries
    * 
    * @var array
    */
    public $libs = array();
    
    
    // Allow user access to SSC Pattern.
    function __construct(){}
    
    /**
    * loader::library();
    * 
    * Load user or system classes
    * from application/libraries or base/libraries
    * directories.
    * 
    * @author Ersin Güvenç
    * @author you..
    * @param string $class class name
    * @param mixed $static instantiate switch load class not declare it 
    *                      Booelan or Array (provide __CONSTRUCT params)
    * @version 0.1
    * @version 0.2  added register_static functions
    *               added OB_Library
    * @return void
    */
    public static function library($class, $static_or_params = NULL)
    {
        if ($class == '')
        return FALSE;
        
        if($static_or_params === TRUE)
        {   
            // if someone want to load static class
            // like Myclass::mymethod, include it.
            register_static($class);
            return;
        }

        // Instantiate the Super Object.        
        $OB = ob::instance();
        
        // Lazy Loading
        if (class_exists($class) AND isset($OB->$class) AND is_object($OB->$class))
        return FALSE;  
        
        $params = $static_or_params;
        $OB->$class = register($class,$params);
        
        if($OB->$class === NULL)
        throw new LoaderException('Unable to locate the library file: '.$class);
    
        $OB->$class->_asn_lib();   // __construct load db support.
        
        // assign all libraries to all models
        // support for loader::libray() func. inside from
        // public model functions
        OB_Library::asn_to_models();
        
        // assign all base libraries to all libraries
        OB_Library::asn_to_libraries();
        
        $OB->libs[] = $class;
    }
    
    /**
    * loader::view();
    * 
    * Load view files
    * Don't declare this func as static
    * because of ability to use $this
    *  
    * @author Ersin Güvenç
    * @author you..
    * @param string $view filename
    * @param array $data view data
    * @param boolean $string fetch as string
    * @version 0.1
    * @version 0.2 Changed $GLOBALS['c'] as $GLOBALS['d']
    * @return void
    */
    public function view($view, $data = array(), $string = FALSE)
    {            
        $file = VIEW . $GLOBALS['d']. DS . $view . EXT;
        
        if(sizeof($data) > 0)
        extract($data, EXTR_SKIP);

        if (file_exists($file))
        {
                if($string) {

                //get file as a srtring.
                ob_start();

                include($file);

                $content = ob_get_contents();
                ob_end_clean();

                return $content;

                } else {

                // just include file.
                include($file);
                }

        } else {
            
            throw new LoaderException('Unable to locate the view: '.$file);
        }

    }
    
    /**
    * loader::model();
    * Obullo Model Pattern
    * 
    * @author Ersin Güvenç
    * @author you..
    * @copyright obullo.com
    * @param string $model
    * @version 0.1
    * @version 0.2 added directory support
    * @version 0.3 changed $GLOBALS['c'] as $GLOBALS['d']
    * @return void
    */
    public static function model($model)
    {        
        if ($model == '')
        return;
        
        $model_name = strtolower($model);
        
        // Controller directory support for model
        // if user provide path separator like this  loader::model(blog/model_blog)
        
        if (strpos($model_name, '/') == TRUE)
        {
            $paths = explode('/',$model_name); // path[0] = controller name
            $model_name = array_pop($paths);
            $path = implode('/',$paths).'/';
            
            // if requested path in same controller
            if($path[0] == $GLOBALS['d'])
            {
                // Load user called current path like current_controller/model_test
                $MODEL_PATH = MODEL.$GLOBALS['d'].DS.$path.$model_name.EXT;  
            
            // if requested path from another controller
            } else {
                
                // Load user called another_controller/model_test
                $MODEL_PATH = CONTROLLER.$path.$model_name.EXT;
            }

             
        } else {
                // Load current controller model
                $MODEL_PATH = MODEL.$GLOBALS['d'].DS.$model_name.EXT;
        }
        
        if ( ! file_exists($MODEL_PATH))
        throw new LoaderException('Unable to locate the model: '.$model_name);
        
        $OB = ob::instance();  
        
        if (isset($OB->$model_name))
        throw new LoaderException('This model already loaded before: '.$model_name);
        
        require($MODEL_PATH);
        $model = ucfirst($model_name);   

        if( ! class_exists($model_name))
        throw new LoaderException('Model name is not correct in file: '.$model_name);
        
        $OB->$model_name = new $model();    //register($class); we don't need it   

        // assign all loaded libraries inside to current model
        // loader::library() support for Model_x { function __construct() { loader::library() }}
        $OB->$model_name->_asn_lib();
        
        // store loaded obullo models
        $OB->mods[] = $model_name;
        
    }
    
    /**
    * loader::database();
    * 
    * Database load.
    * This function just load database to $OB
    * 
    * @author Ersin Güvenç
    * @author you..
    * @copyright obullo.com
    * @version 0.1
    * @version 0.2 multiple models load::database func. support
    *              loading model inside again model bug fixed.
    * @version 0.3 Deprecated debug_backtrace();
    * @return void
    */
    public static function database()
    {
        $OB = ob::instance();
        
        if (class_exists('DB') AND isset($OB->db) AND is_object($OB->db))
        return FALSE;  
        
        require(BASE.'database'.DS.'DB'.EXT);
        require(BASE.'database'.DS.'DBFactory'.EXT);    
        
        // Extends to PDO.
        $OB->db = ob::dbconnect();

        // assign db object to all models
        OB_Library::asn_to_models(); 
        
        // assign db object to all libraries
        OB_Library::asn_to_libraries();    // function load db support.
        // echo 'DB class initalized one time!';

    }        

    /**
    * loader::helper();
    * 
    * We have three helper directory unlike CI
    *   o Base/helpers  : system helpers
    *   o App/helpers   : common application helpers
    *   o Controllers/helper_$controller : controller helpers
    * 
    * @author Ersin Güvenç
    * @author you..
    * @param string $helper
    * @version 0.1
    * @version 0.2 Changed $GLOBALS['c'] as $GLOBALS['d']
    * @return void
    */
    public static function helper($helper)
    { 
        // if user provide path separator like this loader::helper(blog/helper_blog)
        if (strpos($helper, '/') == TRUE)
        {
            $paths = explode('/',$helper); // path[0] = controller name
            $helper_name = array_pop($paths);
            $path = implode('/',$paths).'/';
            
            $helper_name = strtolower('helper_'.str_replace('helper_', '', $helper_name)).EXT;
            
            if(file_exists(CONTROLLER.$path.$helper_name))
            {
                include(CONTROLLER.$path.$helper_name);
    
            } else
            {
                throw new LoaderException('Unable to locate the helper: '.$helper_name);
            }
            return;
        }
        
        $helper = strtolower('helper_'.str_replace('helper_', '', $helper)).EXT;
        
        if(file_exists(BASE.'helpers'.DS.$helper)) 
        {
            include(BASE.'helpers'.DS.$helper);

        } elseif(file_exists(APP.'helpers'.DS.$helper))
        {
            include(APP.'helpers'.DS.$helper);
   
        } elseif(file_exists(CONTROLLER.$GLOBALS['d'].DS.$helper))
        {
            include(CONTROLLER.$GLOBALS['d'].DS.$helper);

        } else
        {
            throw new LoaderException('Unable to locate the helper: '.$helper);
        }

    }   
    
    /**
    * loader::dir();
    * Show directory list of current controller
    * 
    * @author Ersin Güvenç
    * @author you..
    * @version 0.1
    * @version 0.2 Changed $GLOBALS['c'] as $GLOBALS['d']
    * @return booelan
    */
    public static function dir()
    {
        $dir = CONTROLLER.$GLOBALS['d'].DS;
        
        if(is_readable($dir))
        {
            // opendir function
            $handle = opendir($dir);
            echo '<br />Directory Listing of <b>'.$dir.'</b><br/>';

            // running the while loop
            while ($file = readdir($handle)) 
            echo $file.'<br/>';

            // close dir
            closedir($handle);
            
            return TRUE;              
        } 
        
        throw new LoaderException($GLOBALS['d']. DS . ' directory is not readable! ');
    }
    
    /**
    * Load directly PEAR libraries.
    *    
    * @param string e.g. 'Class', 'Mail/mime', 'Spreadsheet/Excel/Writer'
    * @author Ersin Güvenç
    * @author you..
    * @version 0.1
    * @return boolean
    */
    public static function pear($class)
    {
        if(file_exists($class))
        {
            require($class.EXT);
            
            return TRUE;
        }
        
        throw new LoaderException('Unable to locate the pear library:'. $class.EXT);
        
    } //end func.
    
    
    public static function js(){}
    public static function script(){}
    public static function form(){}
    public static function extend(){}
   
    
} //end class.

          
?>
