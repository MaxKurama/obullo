<?php
  

  
Class Blog extends Controller {
    
    function __construct()
    {
        parent::__construct();
        parent::__global();
    }
    
    
    // A hmvc function output must be return to string
    // like echo view('blabla');
    function write($arg = '')
    {        
        echo 'Hello HMVC !<br />';
        echo 'POST: '. print_r($_POST, true).'<br />';
        echo 'argument:'. $arg;
    }
    
    // If outout function exist
    // you can add extra output inside to current
    function _output($output)
    {
        echo 'sdsdssssssss';
        echo $output;
    }
    
    function read()
    {
        echo 'READ !!! WORKS  !! NOTZZS !!';
    }
    
}
