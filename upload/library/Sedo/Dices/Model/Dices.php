<?php

class Sedo_Dices_Model_Dices extends XenForo_Model
{	
	public function getDicesByPostId($postid)
	{
		return $this->_getDb()->fetchRow('
			SELECT code
			FROM bbm_dices
			WHERE postid = ?
		', $postid);
	}

	public function getDicesByPostIds(array $postids)
	{
		$postIdClause = substr(str_repeat("?, ", count($postids)), 0, -2);
		
		return $this->fetchAllKeyed("
			SELECT postid, code
			FROM bbm_dices
			WHERE postid IN ($postIdClause)
		", 'postid', $postids);
	}
}
//Zend_Debug::dump($code);