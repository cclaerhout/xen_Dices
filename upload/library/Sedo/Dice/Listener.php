<?php
class Sedo_Dice_Listener
{
	public static function bbmDicePreCache(array &$preCache, array &$rendererStates, $formatterName)
	{
		if(empty($preCache['bbmDicePostIds']))
		{
			return false;
		}

		$postIds = array_unique($preCache['bbmDicePostIds']);
		$bbmDicePostData = self::_getBbmDiceModel()->getDiceByPostIds($postIds);
		$preCache['bbmDicePostData'] = $bbmDicePostData; 
	}

	public static function bbmDice(&$content, array &$options, &$templateName, &$fallBack, array $rendererStates, $parentClass, $bbCodeIdentifier)
	{
		$oPostId = $postId = $parentClass->getPostParam('post_id');

		$options['dice'] = array();
		$options['error'] = false;
	
		if($bbCodeIdentifier != 'bbm_dice')
		{
			return;
		}
		
		if(!$postId)
		{
			$options['error'] = 'noPostId';
			return;		
		}

		if($parentClass->bbmGetControllerName() != 'XenForo_ControllerPublic_Thread')
		{
			$options['error'] = 'invalidController';
			return;		
		}

		/*Quote management*/
		$quotedPostId = null;
		if($parentClass->bbmGetParentTag() == 'quote')
		{
			$quotedPostId = self::getQuotedPostId($rendererStates['tagDataStack'][0]);

			if($quotedPostId)
			{
				$postId = $quotedPostId;
			}
		}

		/*PreCache section*/
		if(!empty($rendererStates['bbmPreCacheInit']))
		{
			$parentClass->pushBbmPreCacheData('bbmDicePostIds', $oPostId);
			
			if($quotedPostId)
			{
				$parentClass->pushBbmPreCacheData('bbmDicePostIds', $quotedPostId);
			}
			return;
         	}

		/*Check if previous results are available in the view*/
		$previousResults = $parentClass->getTagExtra("results_{$postId}");

		/*Get data section*/
		$data = false;
		$noMatchedValue = false;

		if($previousResults)
		{
			$data = $previousResults;
		}
		else
		{
	        	if(!empty($rendererStates['bbmPreCacheComplete']))
			{
				$bbmPreCacheBbmDice = $parentClass->getBbmPreCacheData('bbmDicePostData');
				
				if(isset($bbmPreCacheBbmDice[$postId], $bbmPreCacheBbmDice[$postId]['code']))
				{
					$data = @unserialize($bbmPreCacheBbmDice[$postId]['code']);
					$parentClass->addTagExtra("results_{$postId}", $data);
				}
				else
				{
					$noMatchedValue = true;
				}
			}
			else
			{
				$data = self::_getBbmDiceModel()->getDiceByPostId($postId);
				if(isset($data['code']))
				{
					$data = @unserialize($data['code']);
				}
			}
		}

		/*Data record section*/
		if(!$data && !$quotedPostId)//what about fake quote? => error
		{
			$data = array_map('trim', explode(';', $content));
			$authorizedDiceType = array(4, 6, 8, 10, 12, 20, 40, 100);
			
			$dice = array();
			
			foreach($data as $die)
			{
				$die = strtolower($die);
				
				if(strpos($die, 'd') === false)
				{
					continue;
				}
				
				$wipDice = explode('d', $die);
				$dieNumber = 1;
				
				if(isset($wipDice[1]))
				{
					//Dice prefix found
					$dieNumber = filter_var($wipDice[0], FILTER_SANITIZE_NUMBER_INT);
					
					if($dieNumber <= 0)
					{
						$dieNumber = 1;
					}
					
					$dieType = filter_var($wipDice[1], FILTER_SANITIZE_NUMBER_INT);
				}
				else
				{
					$dieType = filter_var($wipDice[0], FILTER_SANITIZE_NUMBER_INT);				
				}
				
				if(!in_array($dieType, $authorizedDiceType))
				{
					//The dice type is not correct
					continue;
				}
				
				while ($dieNumber > 0)
				{
					$dice[] = array(
						't' => $dieType,
						'v' => mt_rand(1, $dieType)
					);
					
					$dieNumber--;
				}
			}
			
			if(empty($dice))
			{
				$dice = null;
			}

			$bulkSet = array(
				'postid' => $postId,
				'code' => $dice
			);
			
			$previousResults = $parentClass->getTagExtra("results_{$postId}");

			if(!$previousResults)
			{
				$dw = XenForo_DataWriter::create('Sedo_Dice_DataWriter_Dice');
				$dw->bulkSet($bulkSet);
				$dw->save();
	
				$parentClass->addTagExtra("results_{$postId}", $dice);
				$noMatchedValue = false;
			}
			else
			{
				$dice = $previousResults;
			}
		}
		else
		{
			$dice = $data;
		}

		if($noMatchedValue)
		{
			$options['error'] = 'cantRenderDice';
			return;	
		}

		/*Blank dice*/
		if($dice === NULL)
		{
			$options['error'] = 'emptyDice';
			return;
		}		

		/*Data management section*/
		foreach($dice as &$die)
		{
			$die['class'] = self::_getDiceShape($die['t']);
		}

		$options['dice'] = $dice;
		$options['diceNumber'] = count($dice);
	}

	protected static $_diceShape = array();
	protected static function _getDiceShape($sides)
	{
		if(!isset(self::$_diceShape[$sides]))
		{
			self::$_diceShape[$sides] = XenForo_Template_Helper_Core::styleProperty("bbm_dice_d{$sides}_shape");
		}
		
		return self::$_diceShape[$sides];
	}
	
	protected static $_bbmDiceModel;
	protected static function _getBbmDiceModel()
	{
		if(!self::$_bbmDiceModel)
		{
			self::$_bbmDiceModel = XenForo_Model::create('Sedo_Dice_Model_Dice');
		}
		 
		return self::$_bbmDiceModel;
	}
	
	public static function getQuotedPostId($parentTagInfo)
	{
		if(!isset($parentTagInfo['option']))
		{
			return null;
		}
		
		$postId = null;
		$quoteOptions = explode(',', $parentTagInfo['option']);

		foreach($quoteOptions as $quoteOption)
		{
			if(strpos($quoteOption, 'post:') === false)
			{
				continue;
			}
			
			$postInfo = explode(':', $quoteOption);

			if(isset($postInfo[1]))
			{
				$postId = (int) filter_var($postInfo[1], FILTER_SANITIZE_NUMBER_INT);
			}
		}
		
		return $postId;
	}
}
//Zend_Debug::dump($dice);