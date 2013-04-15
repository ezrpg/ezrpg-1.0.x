<?php
defined('IN_EZRPG') or exit;

/*
Class: Menu
A class to handle the menu system
*/
class Menu
{
    /*
    Variable: $db
    Contains the database object.
    */
    protected $db;
    
    /*
    Variable: $tpl
    The smarty template object.
    */
    protected $tpl;
    
    /*
    Variable: $player
    The currently logged in player. Value is 0 if no user is logged in.
    */
    protected $player;
    
    /*
    Variable: $menu
    An array of all menus.
    */
    protected $menu;
    
    /*
    Function: __construct
    The constructor takes in database, template and player variables to pass onto any hook functions called.
    
    Parameters:
    $db - An instance of the database class.
    $tpl - A smarty object.
    $player - A player result set from the database, or 0 if not logged in.
    */
    public function __construct(&$db, &$tpl, &$player = 0)
    {
        $this->db =& $db;
        $this->tpl =& $tpl;
        $this->player =& $player;
        $query      = $this->db->execute('SELECT * FROM `<ezrpg>menu`');
        $this->menu = $db->fetchAll($query);
    }
    
    /*
    Function: add_menu
    Adds menu to database.
    
    Returns:
    Inserted ID of menu added
    
    Parameters:
    $pid (Optional) Represents the Parent ID of the Menu this Menu belongs to.
    $name (Mandatory) Sets the 1word Name of the Menu.
    $title (Mandatory) Sets the User-Friendly Name of the menu.
    &alttitle (Optional) Sets the Alternative Title for the menu.
    $uri (Optional) Sets the uri that the menu will go to.
    
    Example Usage:
    $this->menu->add_menu('AdminMenu', 'Plugins', 'Plugins', 'Plugin Manager', 'index.php?mod=Plugins'); //As seen in admin/Plugins/index.php
    $bid = $this->menu->add_menu($this->menu->get_menu_id_by_name('City'),'Bank','Empire Bank', '', 'index.php?mod=Bank'); Gets id of 'City' Menu and adds a Menu named 'Bank' with a Title named 'Empire Bank'
    $this->menu->add_menu ($bid, 'Deposit', 'Deposit Money', '', 'index.php?mod=Bank&act=Deposit'); //Adds a submenu to Bank named 'Deposit'
    */
    
    function add_menu($pid = NULL, $name, $title = '', $alttitle = '', $uri = '')
    {
		if(is_numeric($pid)){
        $item['parent_id'] = $pid;
		}else{
		$item['parent_id'] = $this->get_menu_id_by_name($pid);
        }
		$item['AltTitle'] = $alttitle;
		$item['name']      = $name;
        $item['title']     = $title;
        $item['uri']       = $uri;
        return $this->db->insert("menu", $item);
    }
    
	/*
    Function: delete_menu
    Deletes a menu to database.
    
    Parameters:
	$id (Optional
    $pid (Optional) Represents the Parent ID of the Menu this Menu belongs to.
    
    Example Usage:
	$this->menu->delete_menu(6); //Deletes MenuID #6
    $this->menu->delete_menu(6,2); //Deletes MenuID #6 ONLY IF it's parent is MenuID #2 
	or
	$this->menu->delete_menu(get_menu_id_by_name('Bank'), get_menu_id_by_name('City')); //Deletes Menu named Bank ONLY IF it's parent is Menu named City
	$this->menu->delete_menu(get_menu_id_by_name('Bank')); Deletes Menu named "Bank"
	*/
	
	function delete_menu($id = NULL, $pid= NULL){
		if(isset($id) && isset($pid)){
			return $this->db->execute('DELETE FROM `<ezrpg>menu` WHERE parent_id='. $pid . ' AND id='. $id);
		}
		else
		{ 
			if(isset($id)){
				return $this->db->execute('DELETE FROM `<ezrpg>menu` WHERE id='. $id);
			}
			elseif(isset($pid)
			{
				return $this->db->execute('DELETE FROM `<ezrpg>menu` WHERE id='. $id);
			}else{
				return FALSE;
			}
		}
	}
	
    /*
    Function: get_menus
    Preforms the inital grab of the Parent menus.
    
    Parameters:
    $parent (Optional): Sets the ParentID, if Null then it's a Group, if a string then finds ID
	$begin (BOOLEAN Optional) Determines if we should auto-include a Home menu item.
    $endings (BOOLEAN Optional) Determines if we should auto-include a Logout menu item.
    $menu (Optional) Initializes the $menu array variable    
    */
	
    function get_menus($parent = NULL, $args = 0, $begin = TRUE, $endings = TRUE, $title = "", $customtag = "", $showchildren = TRUE)
    {
		if ($args != 0){
		(isset($args['begin'])? $begin = $args['begin'] : '');
		(isset($args['endings'])? $endings = $args['endings'] : '');
		(isset($args['title'])? $title = $args['title'] : '');
		(isset($args['customtag'])? $customtag = $args['customtag'] : '');
		(isset($args['showchildren']) ? $showchildren = $args['showchildren'] : '');
		}
		$result = '';
        $menu = $this->menu;
        if (LOGGED_IN != "TRUE") {
            $menu = "<ul>";
            $menu .= "<li><a href='index.php'>Home</a></li>";
            $menu .= "<li><a href='index.php?mod=Register'>Register</a></li>";
            $menu .= "</ul>";
            $this->tpl->assign('TOP_MENU_LOGGEDOUT', $menu);
        } else {
            foreach ($menu as $item => $ival) {
                $result = ($begin ? $this->get_menu_beginnings() : "<ul>");
                if ($parent != null || !is_null($ival->parent_id)) {
                    if (!is_numeric($parent)) {
                        if ($ival->name == $parent) {
                            $result .= $this->get_children($ival->id, $title, $showchildren);
                            $result .= ($endings ? $this->get_menu_endings() : "</ul>");
                            $this->tpl->assign('MENU_' . (($customtag == "") ? $ival->name : $customtag), $result);
                        }
                    } else {
                        $result .= $this->get_children($ival->id, $title, $showchildren);
                        $result .= ($endings ? $this->get_menu_endings() : "</ul>");
                        $this->tpl->assign('MENU_' . (($customtag == "")? $ival->name : $customtag), $result);
                    }
                } else {
                    if (is_null($ival->parent_id)) //it's a group
                        {
                        $result .= $this->get_children($ival->id, $title, $showchildren);
                        $result .= ($endings ? $this->get_menu_endings() : "</ul>");
                        $this->tpl->assign('TOP_MENU_' . (($customtag != 0)? $customtag : $ival->name), $result);
                    }
                }
            }
        }
        $result .= "</ul>";
        return $result;
    }
    
	/*
	Function: Get_Children
	Gets the submenus of a Menu's Parent_ID
    
	Parameters:
	$parent (Optional): Sets the ParentID, if Null then it's a Group
	$menu (Optional): Initializes the use of the $menu array variable
	$title (Optional): Determines if you want to use Title(0), Alt Title (1) 
	$showchildren (Optional): Determines if we're displaying any children menus.
	*/
	
    function get_children($parent = NULL, $title = 0, $showchildren = TRUE, $menu = 0)
    {
        $result = "";
        if ($menu == 0) {
            $menu = $this->menu;
        }
        foreach ($menu as $item => $ival) {
            if (!is_numeric($parent)) {
                if ($ival->name == $parent) {
                    $this->get_children($ival->id);
                    break;
                }
            } else {
                if ($ival->parent_id == $parent) {
                    $result .= "<li><a href='" . $ival->uri . "'>";
					$result .= ($title == 0 ? $ival->title : $this->get_title($ival)) . "</a>";
                    if ( $this->has_children($ival->id) && $showchildren == TRUE)
                    {
					$result .= "<ul>";
                    $result .= $this->get_children ( $ival->id);
                    $result .= "</ul>";
					}
                    $result .= "</li>";
                }
            }
        }
        return $result;
    }
    
	function get_title($menu){
	if(!is_null($menu->AltTitle)){
	return $menu->AltTitle;
	}else{
	return $menu->title;
	}
	}
	
	/*
	Function: has_children
	BOOLEAN returns T/F if is a Parent Element
    
	Parameters:
	$parent (Optional): Sets the ParentID, if Null then it's a Group
	$menu (Optional): Initializes the use of the $menu array variable
	*/
	
	function has_children($parent = null, $menu = 0){
	if ($menu == 0) {
            $menu = $this->menu;
    }
	foreach ($menu as $item => $ival){
			if ($ival->parent_id == $parent)
				return TRUE;
	}
	return false;
	}
	
	
	function get_menu_id_by_name($pid){
		foreach ($this->menu as $item => $ival) {
			if ($ival->name == $pid) {
				return $ival->id;
			}
		}
	}
	
	
    /*
    Function: add_menu_beginnings and add_menu_endings
    Sets up the start and end of the menu.
    
    Parameters:
    Dont Set the parameters
    
    Example Usage:
    Only used in $get_menus
    */
    
    function get_menu_beginnings($menu = "")
    {
        $menu .= "<ul>"; //Start HTML list
        $menu .= "<li><a href='index.php'>" . (defined('IN_ADMIN') ? "Admin" : "Home") . "</a></li>";
        return $menu;
    }
    
    function get_menu_endings($menu = "", $pre = "")
    {
        if (defined('IN_ADMIN')) {
            $pre = "../";
            $menu .= "<li><a href='../index.php'>To Game</a></li>";
        } else {
            if ($this->player->rank > 5) {
                $menu .= "<li><a href='admin/'>Admin</a></li>";
            }
        }
        $menu .= "<li><a href='" . $pre . "index.php?mod=" . (LOGGED_IN == 'TRUE' ? 'Logout' : 'Register') . "'>" . (LOGGED_IN == 'TRUE' ? 'Logout' : 'Register') . "</a></li>";
        $menu .= "</ul>";
        return $menu;
    }
}
?>
