<?php
class Sedo_Dices_Listener
{
	public static function bbmDicesPreCache(array &$preCache, array &$rendererStates, $formatterName)
	{
		if(empty($preCache['bbmDicesPostIds']))
		{
			return false;
		}

		$postIds = array_unique($preCache['bbmDicesPostIds']);
		$bbmDicesPostData = self::_getBbmDicesModel()->getDicesByPostIds($postIds);
		$preCache['bbmDicesPostData'] = $bbmDicesPostData; 
	}

	public static function bbmDices(&$content, array &$options, &$templateName, &$fallBack, array $rendererStates, $parentClass, $bbCodeIdentifier)
	{
		$postId = $parentClass->getPostParam('post_id');
		$options['dices'] = array();
		$options['error'] = false;
	
		if($bbCodeIdentifier != 'bbm_dices')
		{
			return;
		}
		
		if(!$postId)
		{
			$options['error'] = 'noPostId';
			return;		
		}

		if($parentClass->getView()->createOwnTemplateObject()->getParam('controllerName') != 'XenForo_ControllerPublic_Thread')
		{
			$options['error'] = 'invalidController';
			return;		
		}

		/*PreCache section*/
		if(!empty($rendererStates['bbmPreCacheInit']))
		{
			$parentClass->pushBbmPreCacheData('bbmDicesPostIds', $postId);
			return;
         	}

		/*Get data section*/
		$data = false;
		
        	if(!empty($rendererStates['bbmPreCacheComplete']))
		{
			$bbmPreCacheBbmDices = $parentClass->getBbmPreCacheData('bbmDicesPostData');

			if(isset($bbmPreCacheBbmDices[$postId], $bbmPreCacheBbmDices[$postId]['code']))
			{
				$data = $bbmPreCacheBbmDices[$postId]['code'];
			}
		}
		else
		{
			$data = self::_getBbmDicesModel()->getDicesByPostId($postId);
			$data = $data['code'];
		}

		/*Blank dices*/
		if($data == 'no')
		{
			$options['error'] = 'emptyDices';
			return;
		}

		/*Data record section*/
		if(!$data)
		{
			$data = array_map('trim', explode(';', $content));
			$authorizedDiceType = array(4, 6, 8, 10, 12, 20, 40, 100);
			
			$dices = array();
			
			foreach($data as $dice)
			{
				$dice = strtolower($dice);
				
				if(strpos($dice, 'd') === false)
				{
					continue;
				}
				
				$wipDice = explode('d', $dice);
				$diceNumber = 1;
				
				if(isset($wipDice[1]))
				{
					//Dice prefix found
					$diceNumber = filter_var($wipDice[0], FILTER_SANITIZE_NUMBER_INT);
					
					if($diceNumber <= 0)
					{
						$diceNumber = 1;
					}
					
					$diceType = filter_var($wipDice[1], FILTER_SANITIZE_NUMBER_INT);
				}
				else
				{
					$diceType = filter_var($wipDice[0], FILTER_SANITIZE_NUMBER_INT);				
				}
				
				if(!in_array($diceType, $authorizedDiceType))
				{
					//The dice type is not correct
					continue;
				}
				
				while ($diceNumber > 0)
				{
					$dices[] = array(
						't' => $diceType,
						'v' => mt_rand(1, $diceType)
					);
					
					$diceNumber--;
				}
			}
			
			if(empty($dices))
			{
				$dices = 'no';
			}

			$bulkSet = array(
				'postid' => $postId,
				'code' => $dices
			);

			$dw = XenForo_DataWriter::create('Sedo_Dices_DataWriter_Dices');
			$dw->bulkSet($bulkSet);
			$dw->save();
		}
		else
		{
			$dices = unserialize($data);
		}

		/*Data management section*/
		foreach($dices as &$dice)
		{
			$dice['class'] = self::_getDiceShape($dice['t']);
		}
		
		$options['dices'] = $dices;
	}

	protected static $_diceShape = array();
	protected static function _getDiceShape($sides)
	{
		if(!isset(self::$_diceShape[$sides]))
		{
			self::$_diceShape[$sides] = XenForo_Template_Helper_Core::styleProperty("bbm_dices_d{$sides}_shape");
		}
		
		return self::$_diceShape[$sides];
	}
	
	protected static $_bbmDicesModel;
	protected static function _getBbmDicesModel()
	{
		if(!self::$_bbmDicesModel)
		{
			self::$_bbmDicesModel = XenForo_Model::create('Sedo_Dices_Model_Dices');
		}
		 
		return self::$_bbmDicesModel;
	}
}
//Zend_Debug::dump($dices);