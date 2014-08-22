<?php
class Sedo_Dices_Installer
{
	public static function install($addon)
	{
		$db = XenForo_Application::get('db');
		
		if(empty($addon))
		{
			//Force uninstall on fresh install
			self::uninstall();

			$db->query("CREATE TABLE IF NOT EXISTS bbm_dices (             
			        		postid INT NOT NULL,
      						code TEXT NOT NULL,
						PRIMARY KEY (postid)
					)
		                	ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;"
			);
		}
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
		$db->query("DROP TABLE IF EXISTS bbm_dices");
	}
	
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
		if ($db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}
	 
		return $db->query("ALTER TABLE $table ADD $field $attr");
	}
	
	public static function changeColumnValueIfExist($db, $table, $field, $attr)
	{
		if (!$db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}

		return $db->query("ALTER TABLE $table CHANGE $field $field $attr");
	}
}
//Zend_Debug::dump($code);