<?php

/** 
 *
 * Town Stats Generator.
 *
 *
 */
 
class TownGenerator {
	
	private $MaxPopulation;
	private $Population = 1; //total city Population
	private $PowerCenter;
	private $GPLimit;
	private $TownClasses; // array of all numbers, some will be 'generic' 
	private $TownRaces;
	private $TownAlignment; 
	
	private $dangerlevel;
	
	function __construct($terrain, $climate, $danger = false) {
		
		$this->dangerlevel = $danger;
		
		$this->GeneratePopulation();
		
		for($i=0;$i<$this->PowerCenter['Count'];$i++){
			if($i == 0){
				$this->PowerCenter['Centers'][$i] = new PowerGenerator($this->PowerCenter['Modifier'], false, 100);
			} else {
				$this->PowerCenter['Centers'][$i] = new PowerGenerator($this->PowerCenter['Modifier']);
			}
			if($this->PowerCenter['Centers'][$i]->GetPowerType() == 'Conventional'){
				if(mt_rand(1,20) == 1){
					$this->PowerCenter['Count']++;
					$i++;
					$this->PowerCenter['Centers'][$i] = new PowerGenerator($this->PowerCenter['Modifier'],'Monstrous');
				}
			}
		}
		
		
		
		$this->SetHighAuthority();
		
		$this->SetTownAlignment();
		
		$this->SetCharacterClasses();
		$this->GenerateRacialDemographic($terrain, $climate);
		
	}
	
	private function SetTownAlignment(){
		// determines the numerical value of the overall town alignment, based on power centers. 
		// primary: 10 N: -5 from either direction
		// major: 4 
		// minor: 2
		// support: 1
		
		$this->TownAlignment = array('GE' => array('G' => 0, 'N' => 0, 'E' => 0), 'LC' => array('L' => 0, 'N' => 0, 'C' => 0));
		
		
		foreach( $this->PowerCenter['Centers'] as $powercenter){
			
			if($powercenter->PowerStatus() == 'Destroyed' || $powercenter->PowerStatus() == 'Forming'){
				$pointval = 0;
			} else {
				$pointval = floor($powercenter->GetPowerLevel()/10);
			}


			$this->TownAlignment['GE'][$powercenter->GetGEAlignment()] += $pointval;
			$this->TownAlignment['LC'][$powercenter->GetLCAlignment()] += $pointval;
			
			
			
		}
			
		$this->TownAlignment['GE'] = $this->TownAlignment['GE']['G'] - $this->TownAlignment['GE']['E'];
		$this->TownAlignment['LC'] = $this->TownAlignment['LC']['L'] - $this->TownAlignment['LC']['C'];
		 
		var_dump($this->TownAlignment);
		 
	}
	
	private function GeneratePopulation(){
		// generates the town size as well as the basic information to complete city generation
		$townlimit = mt_rand(1,100);
		
		$townlimit = 10;
		
		if($townlimit < 11){
			$minpop = 20;
			$maxpop = 80;
			$this->PowerCenter = array('Modifier' => -1, 'Count' => 1, 'ClassLevel' => -3, 'ClassPercent' => 50);
		} elseif($townlimit < 31){
			$minpop = 81;
			$maxpop = 400;
			$this->PowerCenter = array('Modifier' => 0, 'Count' => 1, 'ClassLevel' => -2, 'ClassPercent' => 20);
		} elseif($townlimit < 51){
			$minpop = 401;
			$maxpop = 900;
			$this->PowerCenter = array('Modifier' => 1, 'Count' => 1, 'ClassLevel' => -1, 'ClassPercent' => 10);
		} elseif($townlimit < 71){
			$minpop = 901;
			$maxpop = 2000;
			$this->PowerCenter = array('Modifier' => 2, 'Count' => 1, 'ClassLevel' => 0, 'ClassPercent' => 5);
		} elseif($townlimit < 86){
			$minpop = 2001;
			$maxpop = 5000;
			$this->PowerCenter = array('Modifier' => 3, 'Count' => 1, 'ClassLevel' => 3, 'ClassPercent' => 5);
		} elseif($townlimit < 96){
			$minpop = 5001;
			$maxpop = 12000;
			$this->PowerCenter = array('Modifier' => 4, 'Count' => 2, 'ClassLevel' => 6, 'ClassPercent' => 5);
		} elseif($townlimit < 100){
			$minpop = 12001;
			$maxpop = 25000;
			$this->PowerCenter = array('Modifier' => 5, 'Count' => 3, 'ClassLevel' => 9, 'ClassPercent' => 5);
		} else { 
			$minpop = 25000;
			$maxpop = 1000000;
			$this->PowerCenter = array('Modifier' => 6, 'Count' => 4, 'ClassLevel' => 12, 'ClassPercent' => 5);
			
		} 

		$this->Population = mt_rand($minpop, $maxpop);
		$this->MaxPopulation = $this->Population;
		$this->GPLimit = floor($this->Population * (mt_rand(50, 400)/100));
		$this->PowerCenter['Count'] += floor($this->Population / 100000);
		$this->PowerCenter['ClassLevel'] += floor($this->Population / 100000);
	}
	
	private function SetHighAuthority(){
		
		$this->PowerCenter['Centers'][count($this->PowerCenter['Centers'])] = new PowerGenerator($this->PowerCenter['Modifier'],'Authority','Authority');
		
		
	}
	
	public function SetGPLimit($limit){
		$this->GPLimit = $limit;
	}
	
	protected function SetCharacterClasses($military=0){
	// this may change based on edition. For Now we'll assume 3.5, until we are ready for versioning.	
		
		$this->TownClasses = array();
		
		//echo $this->Population . ' | ' . $this->dangerlevel . '<br />';
		
		$this->TownClasses['Soldiers'] = ceil($this->Population / (100 - (23 * $this->dangerlevel)));
		$this->TownClasses['Conscripts'] = ceil($this->Population / (20 - (4.5 * $this->dangerlevel)));
		
		
		$sql = new sqlControl();
		$stmt = 'SELECT ClassName, Roll, DiceCount, LowPopBonus, DangerBoost FROM Classes WHERE Version = "3.5e"';
		$value = array();
		$sql->sqlCommand($stmt, $value);
		
		$vals = $sql->returnAllResults();
		
		//var_dump($vals);
		
		
		
		for($i=0;$i<$this->PowerCenter['Count'];$i++){
			
			foreach($vals as $v){
				// iterate through each value and generate the class.
				$smallboost = 0;
				
				if($v['LowPopBonus'] > 0){
					if($this->Population < 101){
						if(mt_rand(1,20) == 1){
							$smallboost = $v['LowPopBonus'];
							//echo 'Boost<br /><br />';
						}
					}
				}
				
				$this->TownClasses[$v['ClassName']][RollDice($v['Roll'], $v['DiceCount']) + $this->PowerCenter['ClassLevel'] + $smallboost + ($v['DangerBoost'] * ($this->dangerlevel + $military))] += 1;
				
			}
			
		}
		
		//var_dump($this->TownClasses);
		
		$LevelCount = 0;
		$totalpopcount = 0;
		
		foreach($this->TownClasses as $class => $value){
			if($class == 'Soldiers' || $class == 'Conscripts'){
				continue;
			}
			foreach($value as $k => $v){
				if($k < 1){
					unset($this->TownClasses[$class]);
				} else {
					$LevelCount += $k;
					$totalpopcount++;
				}
			}
		}
		
		
		foreach($this->TownClasses as $class => $value){
			if($class == 'Soldiers' || $class == 'Conscripts'){
				continue;
			}
			foreach($value as $k => $v){
				$thislevel = $k - 1;
				
				$totalchars = floor(($k / $LevelCount) * (($this->PowerCenter['ClassPercent'] + (5 * ($this->dangerlevel + $military)))/100) * $this->Population);
				if($totalchars == 0){
					$totalchars = 1;
				}
				if($totalchars > 1){
					
					for($i = 0;$i < $totalchars;$i++){
						
						$lv = floor($thislevel * pow((mt_rand(1,1000000)/1000000), 3)) + 1;
						
						$this->TownClasses[$class][$lv] += 1;
						$totalpopcount++;
					}
					krsort($this->TownClasses[$class]);
				}
			}		
		}
		
		// remaining people will be calculated here.
		$totalpopcount = $this->Population - $totalpopcount;
		$remwarrior = floor($totalpopcount * (0.05 + ((5 * ($this->dangerlevel + $military))/100) + (($military + $this->dangerlevel) / 24)));
		$remexperts = floor($totalpopcount * (0.03 + ((3 * ($this->dangerlevel + $military))/100) + ($military / 100)));
		$remaristocrats = round($totalpopcount * 0.005);
		$remadepts = round($totalpopcount * 0.005);
		$remcommoners = $totalpopcount - $remwarrior - $remexperts - $remaristocrats - $remadepts;
		//echo $totalpopcount;
		if($remadepts > 0){
			$this->TownClasses['Adept'][1] += $remadepts;
		}
		if($remaristocrats > 0){
			$this->TownClasses['Aristocrat'][1] += $remaristocrats;
		}
		if($remcommoners > 0){
			$this->TownClasses['Commoner'][1] += $remcommoners;
		}
		if($remexperts > 0){
			$this->TownClasses['Expert'][1] += $remexperts;
		}
		if($remwarrior > 0){
			$this->TownClasses['Warrior'][1] += $remwarrior;
		}
		
	}
	
	public function GenerateRacialDemographic($terrain, $climate, $population=false, $racelimit=false, $future = false, $military = false){
		//Generate percentages of each race, do so by using degrading algorithm 
		
		if($population){
			$total = $this->Population;
			$last = $population;
			
			$AddGP = floor($population * (mt_rand(10, 400)/100));
			
			if(!$future){
				$this->GPLimit += $AddGP;
				$output['GP'] = $AddGP;
				
				$this->Population += $population;
				$this->MaxPopulation += $population;
			}
			
			
			
		} else {
			$last = $this->Population;
			$total = 0;
		}
		
		
		
		
		$min = floor($this->Population * 0.3);
		$firstrun = true;
		$chosengroup = 'AllRaces';
		do{
			
			$raceprob = mt_rand(1,10000);
			$priority = floor(200 * pow((mt_rand(1,1000000)/1000000), 1));
			
		//	echo $priority .'<br />';
			
			if($last > $this->Population - $total){
				$last = $this->Population - $total;
			}
			
			$min = floor($last * 0.01);
		
			if($min < 1){
				$min = 1;
			}
			
			if($this->TownAlignment['GE'] > 1){
				$alignGE = 'E';
			} elseif($this->TownAlignment['GE'] < -1){
				$alignGE = 'G';
			} else {
				$alignGE = 'N';
			}
			
			if($racelimit){ // this overrides the random. returns 100% of the population add.
				$last = $population;
			} else {
				$last = mt_rand($min, $last);
			}
			
			$sql = new sqlControl();
			
			
			/* max pop needs to be set to "per capita" */
			$percap = ceil(($last / $this->Population) * 100000);
			
			
			
				$stmt = 'SELECT Race FROM Races WHERE MaxPopPerCapita >= :Pop AND Priority >= :pri AND AlignmentGE != :align AND ' . $terrain . ' = 1 AND ' . $chosengroup . ' = 1 AND ' . $climate . ' = 1 AND Edition = "3.5e"';
			
			//echo $stmt . '<br /><br />';
			//echo 'Pri ' . $priority . '<br /><br />';
			$value = array(':Pop' => $percap, ':pri' => $priority, ':align' => $alignGE);
			$sql->sqlCommand($stmt, $value);
			
			$vals = $sql->returnAllResults();
			
			//var_dump($vals);
			//echo '<br />' . $last . ' ' . $this->Population . ' ' . $percap . '<br /><br />';
			
			if(count($vals) > 0){
				// only set a new race if a valid option is chosen. Otherwise reuse previous value.
				$race = $vals[array_rand($vals)]['Race'];
			} else {
				if($race == ''){
					$race = 'Human';
				}
			}
			
			
			
			
			
			if($firstrun){
				$stmt = 'SELECT Humanoid, Beast, FeyLike, Goodlike, EvilLike, Giant, CoreRace, AllRaces, DrowOnly, YuanTi FROM Races WHERE Race = :race LIMIT 1';
				$value = array(':race' => $race);
				$sql->sqlCommand($stmt, $value);
				$results = $sql->returnResults();
				
				foreach($results as $key => $res){
					if(gettype($key) != 'string' || $res != 1){
						// remove invalid responses. 
						unset($results[$key]);
					}
					if($key == 'AllRaces'){
						unset($results[$key]);
					}
				}
				
				$chosengroup = array_rand($results);
				
				//var_dump($chosengroup);
				$firstrun = false;
			}
			
			if(!$future){
				$this->TownRaces[$race] += $last;
			}
			$output[$race] += $last;
			
			$total += $last;
	
		} while ($total < $this->Population);
		
		//var_dump($output);
		
		if($military){
			$this->SetCharacterClasses(2);
		} else {
			$this->SetCharacterClasses();
		}
		
		return $output;
		
		//var_dump($this->TownRaces);
	}
	
	public function RemoveRaces($population, $races, $timeline){
		
		//echo 'Pop: ' . $population . ' Max Pop: ' . $this->MaxPopulation . '<br />';
		
		//var_dump($this->TownRaces);
		
		if($population > 0){
			if($timeline != 'Historical'){
				//$this->MaxPopulation -= $population;
				$this->Population -= $population;
				foreach($races as $k => $r){
			
					$this->TownRaces[$k] -= $r;
			
					if($this->TownRaces[$k] <= 0){
				
						$this->TownRaces[$k] = 0;
				//		unset($this->TownRaces[$k]);
					}
				}
			} else {
				$this->MaxPopulation += $population;
			}
		}
	}
	
	public function AddPowerCenter($history, $alignment, $strength, $origin='Random'){
		
		if($history == 'Historical'){
			$status = 'Destroyed';
		} elseif ($history == 'Future'){
			$status = 'Forming';
		} else {
			$status = 'Active';
		}
		
		$align = array();
		$gec = array('G','N','E');
		$lcc = array('L', 'N', 'C');

		if($this->TownAlignment['GE'] > 3){
			$good = 'G';
			$evil = 'E';
		} elseif($this->TownAlignment['GE'] < -3){
			$good = 'N';
			$evil = 'E';
		} else {
			$good = 'E';
			$evil = 'G';
		}
		
		if($this->TownAlignment['LC'] > 3){
			$goodb = 'L';
			$evilb = 'C';
		} elseif($this->TownAlignment['LC'] < -3){
			$goodb = 'N';
			$evilb = 'N';
		} else {
			$goodb = 'C';
			$evilb = 'L';
		}

		if($alignment == 'Good'){
			for($i=0;$i<5;$i++){
				$gec[] = $good;
				$lcc[] = $goodb;
			}
		} else {
			for($i=0;$i<5;$i++){
				$gec[] = $evil;
				$lcc[] = $evilb;
			}
		}
		
		$align['GE'] = $gec[array_rand($gec)];
		$align['LC'] = $lcc[array_rand($lcc)];
		
		$count = count($this->PowerCenter['Centers']);
		
		
		//echo '<br />' . $origin;
		
		
		$this->PowerCenter['Centers'][$count] = new PowerGenerator($this->PowerCenter['Modifier'], false, $strength, $status, $align, $origin, $this->MaxPopulation, $this->TownClasses);
		
		
		return array('PowerCenter' => $count, 'Status' => $status);
		
		//var_dump($this->PowerCenter['Centers'][$count]);
		$this->SetTownAlignment();
		//echo '<br />';
	}
	
	//$this->theCity->RemovePowerCenter($data['Type'], $data['Strength'], 'Event');
	public function RemovePowerCenter($alignment, $strength, $origin='Random'){

		if($this->TownAlignment['GE'] > 3){
			$align = 'G';
		} elseif($this->TownAlignment['GE'] < -3){
			$align = 'E';
		} else {
			$align = 'N';
		}


		//echo 'Power Center Destroyed';
		
							
		//$this->PowerCenter['Centers'][$count]->$status();
		$remkey = -1;
		if(count($this->PowerCenter['Centers']) > 1){
		
			foreach($this->PowerCenter['Centers'] as $key => $center){
				
				if($alignment == 'Good'){
					
					if($center->GetGEAlignment() != $align && $center->PowerStatus() !== 'Destroyed' && $center->PowerStatus() !== "Forming" && $center->GetPowerLevel() < $strength && $remkey < $strength){
						
						$remkey = $key;
						//$center->SetStatus('Destroyed');	
				
					}
				} else {
					
					if($center->GetGEAlignment() == $align && $center->PowerStatus() !== 'Destroyed' && $center->PowerStatus() !== "Forming" && $center->GetPowerLevel() < $strength && $remkey < $strength){
						
						$remkey = $key;
						//$center->SetStatus('Destroyed');
					}
				}
			}
			
			if($remkey == -1 && $strength > 50){
				$rtop = count($this->PowerCenter['Centers']) - 1;
				$remkey = mt_rand(0, $rtop);
				
			//	echo 'Destroy: ' . $remkey . '<br />';
				
			}
			
			if($remkey != -1){
				$this->PowerCenter['Centers'][$remkey]->SetStatus('Destroyed');
			}
			
		}
			
				
		return array('PowerCenter' => $remkey, 'Status' => 'Destroyed');
		
		$this->SetTownAlignment();
	}
	
	public function GetTownRaces(){
		return $this->TownRaces;
	}
	
	public function GetPopulation($max=false){
		if($max){
			return array('Pop' => $this->Population, 'Max' => $this->MaxPopulation);
		} else { 
			return $this->Population;
		}
	}
	
	public function GetRaces(){
		return $this->TownRaces;
	}
	
	public function GetGPLimit(){
		return $this->GPLimit;
	}
	
	public function GetPowerCenterTypes(){
		// this will only return the most basic of information. 
		foreach($this->PowerCenter['Centers'] as $key => $pc){
			//var_dump($pc);
			if($pc->GetPowerName() != ''){
				$powers[] = $key . ': ' . $pc->GetPowerType() . ': ' . $pc->GetPowerName() . ', Alignment: ' . $pc->GetAlignment() . ', Level: ' . $pc->GetPowerLevel() . ', Status: ' . $pc->PowerStatus() . ', Origin: ' . $pc->PowerOrigin();
			} else {
				$powers[] = $key . ': ' . $pc->GetPowerType() . ', Alignment: ' . $pc->GetAlignment() . ', Level: ' . $pc->GetPowerLevel() . ', Status: ' . $pc->PowerStatus() . ', Origin: ' . $pc->PowerOrigin();
			}
		}
		return $powers;	
	}
	
	public function GetTownSoldiers() {
		return array(
			'Soldiers' => $this->TownClasses['Soldiers'], 
			'Conscripts' => $this->TownClasses['Conscripts']
		);
	}
	
	public function GetClassBreakdowns(){
		// this will temporary. This gets all of each class for output. 
		
		foreach($this->TownClasses as $class => $value){
			if($class == 'Soldiers' || $class == 'Conscripts'){
				continue;
			}
			$output[$class] = $value;
		}
		return $output;
	}
	
	public function GetTownAlignment($Character = false){
		if($Character){
			$output = array();
			
			if($this->TownAlignment['GE'] > 6){
				$output['GE'] = 'g';
			} elseif ($this->TownAlignment['GE'] < -6){
				$output['GE'] = 'e';
			} else {
				$output['GE'] = 'n';
			}
			
			if($this->TownAlignment['LC'] > 6){
				$output['LC'] = 'l';
			} elseif ($this->TownAlignment['LC'] < -6){
				$output['LC'] = 'c';
			} else {
				$output['LC'] = 'n';
			}
			
			return $output;
		} else {
			return $this->TownAlignment;
		}
	}
}

?>