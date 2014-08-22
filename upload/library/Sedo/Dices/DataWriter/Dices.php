<?php

class Sedo_Dices_DataWriter_Dices extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'bbm_dices' => array(
				'postid' => array('type' => self::TYPE_UINT),
				'code' => array('type' => self::TYPE_SERIALIZED, 'required' => true, 'maxLength' => 1000)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$postid = $this->_getExistingPrimaryKey($data, 'postid'))
		{
			return false;
		}

		return array('bbm_dices' => $this->_getBbmDicesModel()->getDicesByPostId($postid));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'postid = ' . $this->_db->quote($this->getExisting('postid'));
	}

	protected function _getBbmDicesModel()
	{
		return $this->getModelFromCache('Sedo_Dices_Model_Dices');
	}
}