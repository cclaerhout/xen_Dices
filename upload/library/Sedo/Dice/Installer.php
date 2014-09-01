<?php
class Sedo_Dice_Installer
{
	public static function install($addon)
	{
		$db = XenForo_Application::get('db');
		
		if(empty($addon))
		{
			//Force uninstall on fresh install
			self::uninstall();

			if(self::tableExists('bbm_dices'))
			{
				$db->query("RENAME TABLE bbm_dices TO bbm_dice;");
			}
			else
			{
				$db->query("CREATE TABLE IF NOT EXISTS bbm_dice (             
				        		postid INT NOT NULL,
	      						code TEXT NOT NULL,
							PRIMARY KEY (postid)
						)
			                	ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;"
				);
			}
		}
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
		$db->query("DROP TABLE IF EXISTS bbm_dice");
	}

	public static function tableExists($tableName){
		$db = XenForo_Application::get('db');
		return ($db->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0) ? true : false;
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