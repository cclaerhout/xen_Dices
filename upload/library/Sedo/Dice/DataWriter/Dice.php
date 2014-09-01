<?php

class Sedo_Dice_DataWriter_Dice extends XenForo_DataWriter
{
	protected function _getFields()
	{
		return array(
			'bbm_dice' => array(
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

		return array('bbm_dice' => $this->_getBbmDiceModel()->getDiceByPostId($postid));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'postid = ' . $this->_db->quote($this->getExisting('postid'));
	}

	protected function _getBbmDiceModel()
	{
		return $this->getModelFromCache('Sedo_Dice_Model_Dice');
	}
}