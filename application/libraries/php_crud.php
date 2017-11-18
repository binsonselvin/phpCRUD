<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/*
 * @Author: Vinod Selvin
 * @Desc: Php Crud entire logic to be written here
 */
class Php_crud{

    public function __construct() {

    }
    
    public $select_column = array();
    
    public $table_name = FALSE;
    
    public $theme_root = "phpCrud_themes/";
    
    public $theme = "default";

    public $add_graph = array('type' => false, 'cols' => array());

    /*
     * @Author: Vinod Selvin
     * @Desc: Returns Selected table. 
     * @Params: $table_name-> Name of the Table.
     */
    public function select_table($table_name) {
        
        $this->table_name = $table_name;
    }
    
    public function _select(){
        
        if(empty($this->table_name)){
            echo "Please Select Table, see doc for more info";
        }
        
        $table_name = $this->table_name;
        
        $CI = & get_instance();

        $CI->load->model('Php_crud_model');

        $select = '';
        
        $exists = $CI->Php_crud_model->tableExists($table_name);
        
        if(!$exists){
            
            $final_result['error'] = 'true';
            
            $final_result['message'] = 'Error: Selected Table Doesn\'t Exists in the Database.';
            
            return $final_result;
        }
        else{
            
            if (empty($this->select_column)) {
            
                $select = '*';
            } 
            else {
                
                $select = $this->select_column;
                
                $exists = $CI->Php_crud_model->columnExists($table_name, $select);
                
                if(!$exists){
            
                    $final_result['error'] = 'true';

                    $final_result['message'] = 'Error: Selected Column Doesn\'t Exists in the Table.';

                    return $final_result;
                }
                
                $final_select = $this->_primaryKeyExistInSelect($table_name, $select, $final_result['primary_key']);

                $select = $final_select['select'];

                $final_result['primary_key_hidden'] = $final_select['hidden'];
                
            }
           
           $final_result['primary_key'] = $this->_getPrimaryKey($table_name);
            
        }

        $result = $CI->Php_crud_model->select_table($table_name, $select, $final_result['primary_key']);

        if (empty($result['data'])) {

            $final_result['error'] = 'true';

            $final_result['message'] = 'No records found (or) Table Name might be missing/ wrong..';
            
            return $final_result;
        }

        $final_result['data_type'] = $result['data_type'];
        
        $final_result['table_data'] = $result['data'];
        
        $final_result['table_name'] = $table_name;

        return ($final_result);
    }
    
    function _getPrimaryKey($table_name){
        
        $CI = & get_instance();

        $CI->load->model('Php_crud_model');
        
        return $CI->Php_crud_model->get_primary_key($table_name);
    }
    
    function _primaryKeyExistInSelect($table_name, $select, $primary_key){
        
        $final_select;
        
        if(in_array($primary_key, $select)){

            $final_select['hidden'] = 'false';
        }
        else{
            
            array_unshift($select, $primary_key);
            
            $final_select['hidden'] = 'true';
        }
        
        $final_select['select'] = $select;
        
        return $final_select;
    }
    
    function set_new_theme($theme){
        
        if(!empty($theme)){
            if(file_exists($this->theme_root.$this->theme)){
                $this->theme = $theme;
            }
            else{
                exit("Specified theme doesnt Exist");
            }
        }
        else{
            exit("Please specify Valid theme");
        }
    }
    
    function render_output(){
        
        $browser_output = "";
        
        $data['result'] = $this->_select();
        
        $theme['dir'] = $this->theme_root . $this->theme;

        $theme['js'] = array();
        $theme['css'] = array();
        $theme['cdn'] = array();

        if(!empty($this->add_graph['type'])){
            
            $graph = $this->_generateGraph($data['result']);

            $theme['js'] = array_merge($theme['js'], $graph['js']);
            $theme['css'] = array_merge($theme['css'], $graph['css']);
            $theme['cdn'] = array_merge($theme['cdn'], $graph['cdn']);

            $data['graph'] = $this->add_graph;
            $data['graph_data'] = $graph;
        }

        $browser_output .= $this->_phpcrud_view($theme['dir'] . '/views/header', $theme, true);
        $browser_output .= $this->_phpcrud_view($theme['dir'] . '/views/index', $data, true);
        $browser_output .= $this->_phpcrud_view($theme['dir'] . '/views/footer', $theme, true);
        $browser_output .= $this->_phpcrud_view($theme['dir'] . '/views/custom_footer', $data, true);

        return $browser_output;
    }

    function _generateGraph($data){

        $graph_requirement = $this->add_graph;

        $type = $graph_requirement['type'];

        $graph_data = array();

        switch ($type) {
            case 'multiline':
                    $graph_data['js'] = array(
                        // '/assets/d3/d3.v3.min.js',
                        '/assets/d3/multiline/multiline.js');
                    $graph_data['css'] = array(
                        '/assets/d3/multiline/multiline.css');
                    $graph_data['multiline'] = $this->_getMultilineGraph($data['table_data'], $graph_requirement['cols']);
                    
                break;
            
            default:
                break;
        }

        return $graph_data;
    }

    function _getMultilineGraph($data, $cols){

        if(!empty($cols)){
            if(is_string($cols)){
                $cols = explode(",", $cols);
            }

            if(is_array($cols)){

                $csv = implode(",", $cols) . "\n";

                foreach ($data as $key => $value) {

                    $ecsv = array();

                    foreach ($cols as $key2 => $value2) {

                        $ecsv[] = $value[$value2];
                    }

                    $csv .= implode(",", $ecsv) . "\n";

                }
            }
        }

        return $csv;
    }
    
    protected function _phpcrud_view($view, $vars = array(), $return = FALSE){
        
		$vars = (is_object($vars)) ? get_object_vars($vars) : $vars;

		$file_exists = FALSE;

		$ext = pathinfo($view, PATHINFO_EXTENSION);
		$file = ($ext == '') ? $view.'.php' : $view;

		if (file_exists($file))
		{
			$path = $file;
			$file_exists = TRUE;
		}

		if ( ! $file_exists)
		{
			throw new Exception('Unable to load the requested file: '.$file, 16);
		}

		extract($vars);

		#region buffering...
		ob_start();

		include($path);

		$buffer = ob_get_contents();
		@ob_end_clean();
		#endregion

		if ($return === TRUE)
		{
                    return $buffer;
		}

		$this->views_as_string .= $buffer;
	}


}
