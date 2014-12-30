<?php
use Zend\Crypt\Password\Bcrypt;
 
class Authex{
	
function __construct()
{
	$this->ci =& get_instance();
	//load libraries
	$this->ci->load->library('session');
	$this->ci->load->database();
	spl_autoload_register( array( $this, 'autoload') );
 
}
	function autoload($className)
	{
		$className = ltrim($className, '\\');
		$fileName  = '';
		$namespace = '';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	
		require $fileName;
	}
	

	function get_user_id()
	{
		return $this->ci->session->userdata('user_id');
	}
	

 	function get_user_name()
 	{
		 return $this->ci->session->userdata('user_name');
 	}
 	
 	function get_left_menus()
 	{
 		return $this->ci->session->userdata('left_menus');
 	}
 
 
 function logged_in()
 {
     $CI =& get_instance();
     return ($CI->session->userdata("user_id")) ? true : false;
 }

 function login($email, $password)
 {
     $CI =& get_instance();

     $query = $CI->db->select('user_id,user_password, role_id, employee_name, branch_id')
     		->from('cx_users')
     		->join('cx_employees', 'cx_employees.employee_id = cx_users.employee_id')
     		->where('user_email', $email)     		
     		->where('user_status', 1)
     	    ->get('');	     

     if($query->num_rows() !== 1)
     {
         return false;
     }
     else
     {    	
        // Verify Password        
     	$bcrypt = new Bcrypt();   
     	     	
     	if($bcrypt->verify($password, $query->row()->user_password ))
     	{
     		//update the last login time
     		$data = array(
     				"last_login" => date("Y-m-d H-i-s")
     		);
     		$CI->db->where('user_id', $query->row()->user_id);
     		$CI->db->update("cx_users", $data);
     		
     		// Get Menu Items for the user
     		$sql = "SELECT menu_name, menu_link, section_id FROM cx_permissions
					INNER JOIN cx_menus ON cx_permissions.menu_id = cx_menus.menu_id
					WHERE role_id = ?";     		
     		$result = $CI->db->query($sql, array( $query->row()->role_id  ) );
     		
     		$menu_items = array();
     		     		
     		if ($result->num_rows() > 0)
     		{
     			foreach ($result->result() as $row)
     			{
     				// Get Module Names
     				$sql = "SELECT module_name FROM cx_sections
							LEFT JOIN cx_modules ON cx_modules.module_id = cx_sections.parent_module_id
							WHERE section_id = ?";
     				$QueryResult = $CI->db->query($sql, array( $row->section_id  ) );
     				
     				// Generate the array for Navigation Menu for the user 
     				if ($QueryResult->num_rows() > 0)
     				{
     					foreach ($QueryResult->result() as $rows)
     					{
     						$menu_items[$rows->module_name ][] = array("menuName" => $row->menu_name, "menuLink" => $row->menu_link);  															
     					}
     				}     				
     			}
     		}
     		 		
     		//store user information in the session
     		$CI->session->set_userdata("user_id", $query->row()->user_id);
     		$CI->session->set_userdata("role_id", $query->row()->role_id);
     		$CI->session->set_userdata("user_name", $query->row()->employee_name);
     		$CI->session->set_userdata("branch_id", $query->row()->branch_id);
     		$CI->session->set_userdata("left_menus", $menu_items);
     		
     		return TRUE;
     		   		
     	} 
     	else 
     	{
     		return FALSE; // Password did not match
     	}
        
     }
 }

 function logout()
 {
     $CI =& get_instance();
     $CI->session->unset_userdata("user_id");
     $CI->session->unset_userdata("role_id");
     $CI->session->unset_userdata("user_name");
     $CI->session->unset_userdata("branch_id");    
	 $this->ci->session->sess_destroy();
 }

  
}