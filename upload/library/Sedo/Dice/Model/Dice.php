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

    protected $authorizedDiceSize = array(4 => true, 6 => true, 8 => true, 10 => true, 12 => true, 20 => true, 40 => true, 100 => true);

    public function isValidDieSize($die)
    {
        return isset($this->authorizedDiceSize[$die]);
    }

    public function rollDie($die, $dieLimit, array &$dice)
    {
        if(strpos($die, 'd') === false)
        {
            throw new Sedo_Dice_Exception('invalid_dice_format');
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
            
            if ($dieNumber > $dieLimit)
            {
                throw new Sedo_Dice_Exception('too_many_dice');
            }
            
            $dieType = filter_var($wipDice[1], FILTER_SANITIZE_NUMBER_INT);
        }
        else
        {
            $dieType = filter_var($wipDice[0], FILTER_SANITIZE_NUMBER_INT);
        }

        if(!$this->isValidDieSize($dieType))
        {
            //The dice size is not correct
            throw new Sedo_Dice_Exception('invalid_sized_die_x', array('die' => $dieType));
        }

        $diceRolled = $dieNumber;
        while ($dieNumber > 0)
        {
            $dice[] = array(
                't' => $dieType,
                'v' => mt_rand(1, $dieType)
            );

            $dieNumber--;
        }
        return $diceRolled;
    }

    public function rollDice($commands, $dieLimit = 100)
    {
        $commands = strtolower($commands);
        $data = array_map('trim', explode(';', $commands));

        $dice = array();

        foreach($data as $die)
        {
            $dieRolled = $this->rollDie($die, $dieLimit, $dice);
            $dieLimit -= $dieRolled;
            if ($dieLimit < 0)
            {
                throw new Sedo_Dice_Exception('tooManyDice');
            }
        }

        return $dice;
    }
}
//Zend_Debug::dump($code);