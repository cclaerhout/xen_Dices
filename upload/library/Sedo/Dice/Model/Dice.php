<?php

class Sedo_Dice_Model_Dice extends XenForo_Model
{	
	public function getDiceByPostId($postid)
	{
		return $this->_getDb()->fetchRow('
			SELECT code
			FROM bbm_dice
			WHERE postid = ?
		', $postid);
	}

	public function getDiceByPostIds(array $postids)
	{
		$postIdClause = substr(str_repeat("?, ", count($postids)), 0, -2);
		
		return $this->fetchAllKeyed("
			SELECT postid, code
			FROM bbm_dice
			WHERE postid IN ($postIdClause)
		", 'postid', $postids);
	}
}
//Zend_Debug::dump($code);