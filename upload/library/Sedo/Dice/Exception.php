<?php

class Sedo_Dice_Exception extends XenForo_Exception
{
	public function __construct($message, array $args = array())
	{
        $message = new XenForo_Phrase('bbm_dice_error_' . $message, $args);
        parent::__construct($message, true);
    }
}