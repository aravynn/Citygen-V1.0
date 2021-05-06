<?php

/** 
 *
 * character Generator.
 *
 *
 */
 
class CharacterGenerator {
	
	private $stats = array('str' => 0 , 'dex' => 0 , 'con' => 0 , 'int' => 0 , 'wis' => 0 , 'cha' => 0);
	
	private $age;
	private $race;
	private $gender;
	private $FirstName;
	private $LastName;
	
	private $charclass;
	private $subclass;
	private $level;
	
	private $parentage = array();
	
	private $GEAlignment;
	private $LCAlignment;
	
	private $CharStatus;
	
	// CharacterGenerator($key, $age, $gender, $alignment, $parents, $statbonuses);
	
	//function __construct($gealign, $lcalign, $age, $parents, $race, $lastname=false, $firstname=false) {
	function __construct($race, $age, $gender, $alignment, $parents, $statbonuses, $initialStatus = 'Alive'){
		
		$pcount = 0;
		foreach($parents as $key => $data){
			$parents[$pcount] = $data;
			$this->parentage[$pcount] = $key;
			$pcount++;
		}
		
		$this->GenerateStats($statbonuses, $parents);
		
		$this->setFirstName();
		$this->setLastName($parents[0]);
		
		$this->CharStatus = $initialStatus;
		
		$this->age = $age;
		$this->race = $race;
		$this->gender = $gender;
		
		$this->setAlignment($alignment, $parents);
		
	}
	
	private function GenerateStats($statbonuses, $parents){
		if(!empty($parents)){	
			$stat1 = $parents[0]->GetStat();
			$stat2 = $parents[1]->GetStat();
			
			foreach($this->stats as $key => $stat){
				$astat = round(($stat1[$key] + $stat2[$key] - ($statbonuses[$key] * 2)) / 2);
				if($astat > 14){
					$this->stats[$key] = RollStat(5) + $statbonuses[$key];
				} elseif ($astat < 7) { 
					$this->stats[$key] = RollStat(3) + $statbonuses[$key];
				} else {
					$this->stats[$key] = RollStat(4) + $statbonuses[$key];
				}
			}	
		} else {
			foreach($this->stats as $key => $val){
				if($val == 0){
					$this->stats[$key] = RollStat(4) + $statbonuses[$key];
				}	
			}
		}
	}
	
	public function SetCharClass($charclass, $level, $sub = false){
		
		//echo $charclass . ' ' . $level;
		
		$this->charclass = $charclass;
		$this->level = $level;
		
		if(!$sub){
		
			$sql = new sqlControl();
		
			$priority = array(0,9);
		
			$stmt = 'SELECT Subclass, RestrictionType, Restriction FROM Subclasses WHERE Class = :class AND Version = "3.5e" AND Priority > :priority';
			
			$value = array(':class' => $charclass, ':priority' => $priority);
			$sql->sqlCommand($stmt, $value);
			
			$vals = $sql->returnAllResults();
		
			//var_dump($vals);
		
			$finallist = array();
		
			foreach($vals as $val){
				//highstat
				//alignment
				//race
				if($val['RestrictionType'] == 'race'){
			
					$race = ucwords($val['Restriction']);
					//echo $race . '<br />';
				
					if(strpos($race, $this->race)){
					//	echo $race . ' ' . $this->race . '<br />';
						$finallist[] = $val['Subclass'];
					}
				
				} elseif($val['RestrictionType'] == 'highstat'){
					if($val['Restriction'] == 'str' && $this->stats['str'] > $this->stats['dex'] ){
						$finallist[] = $val['Subclass'];
					}elseif($val['Restriction'] == 'dex' && $this->stats['dex'] > $this->stats['str'] ){
						$finallist[] = $val['Subclass'];
					}
				} elseif($val['RestrictionType'] == 'alignment'){
					$aligns = explode('', $val['Restriction']);
					if(in_array($this->GEAlignment, $aligns) && in_array($this->LCAlignment, $aligns)){
						$finallist[] = $val['Subclass'];
					}
				} else {
					$finallist[] = $val['Subclass'];
				}
			
			}
			
			$sql = '';
		
			$this->subclass = $finallist[array_rand($finallist)];
		} else {
			$this->subclass = $sub;
		}
	}
	
	private function setAlignment($alignment, $parents){
		// Use Town skew, racial alignment, and parental alignment
		
		if(!empty($parents)){
			$alignment['GE'][] = $parents[0]->GetAlignment('GE');
			$alignment['GE'][] = $parents[1]->GetAlignment('GE');
			$alignment['LC'][] = $parents[0]->GetAlignment('LC');
			$alignment['LC'][] = $parents[1]->GetAlignment('LC');
		}	
		
		$perGE = array(100,100,100);
		$perLC = array(100,100,100);

		foreach($alignment['GE'] as $k => $a){
			switch($a){
				case 'g':
					$perGE[0] *= 2;
					$perGE[2] /= 2;
					
					break;
				case 'e':
					$perGE[0] /= 2;
					$perGE[2] *= 2;
					break;
				case 'n':
				default :
					$perGE[1] *= 2;
					break;
			}
		}
		foreach($alignment['LC'] as $k => $a){
			switch($a){
				case 'l':
					$perLC[0] *= 2;
					$perLC[2] /= 2;
					break;
				case 'c':
					$perLC[2] *= 2;
					$perLC[0] /= 2;
					break;
				case 'n':
				default :
					$perLC[1] *= 2;
					break;
			}
		}
		
		
		//var_dump($perGE);
		//var_dump($perLC);
		
		$gestat = mt_rand( 0, array_sum($perGE) );
		
		//echo $gestat . '<br />';
		
		if($gestat <= $perGE[0]){
			$this->GEAlignment = 'g';
		} elseif($gestat <= $perGE[0] + $perGE[1]){
			$this->GEAlignment = 'n';
		} elseif($gestat <= $perGE[0] + $perGE[1] + $perGE[2]){
			$this->GEAlignment = 'e';
		} else {
		//	echo 'ERROR: ' . $gestat . ' > Maximum for GE Max: ' . array_sum($perGE);
			$this->LCAlignment = 'n';
		}
		
		
		$lcstat = mt_rand( 0, array_sum($perLC) );
		
		//echo $lcstat . '<br />';
		
		if($lcstat < $perLC[0]){
			$this->LCAlignment = 'l';
		} elseif($lcstat < $perLC[0] + $perLC[1]){
			$this->LCAlignment = 'n';
		} elseif($lcstat < $perLC[0] + $perLC[1] + $perLC[2]){
			$this->LCAlignment = 'c';
		} else {
		//	echo 'ERROR: ' . $lcstat . ' > Maximum for LC';
			$this->LCAlignment = 'n';
		}
		
	}
	
	private function setLastName($parent = false){
		if(!$parent){
			$this->LastName = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
		} else {
			$this->LastName = $parent->GetLastName();
		}
	}
	
	private function setFirstName(){
		$this->FirstName = substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
	}
	
	public function GetLastName(){
		return $this->LastName; 
	}
	
	public function GetAlignment($thisalign){
		if($thisalign == 'GE'){
			return $this->GEAlignment;
		} elseif($thisalign == 'LC'){
			return $this->LCAlignment;
		}
	}
	
	public function GetStat($choice = false){
		
		if(!$choice){
		return $this->stats;
		} else {
			switch($choice){
				case 'Strongest':
					$maxs = array_keys($this->stats, max($this->stats));
					return $maxs[0];
					break;
				case 'Weakest':
					$mins = array_keys($this->stats, min($this->stats));
					return $mins[0];
					break;
				
			}
			
		}
	}
	
	public function GetCharClass($type = 'full'){
		
		// returns class array
		switch($type){
			case 'Full':
			case 'full':
				return array('class' => $this->charclass, 'subclass' => $this->subclass);
				break;
			case 'Class':
			case 'class': 
				return $this->charclass;
				break;
			case 'subclass':
			case 'Subclass':
			case 'SubClass':
				return $this->subclass;
				break;
		}
		
	}
	
	public function OutputDetails(){
	
		$output = $this->FirstName . ' ' . $this->LastName . '<br />';
		$output .= $this->gender . ' ' . $this->race . ' Age: ' . $this->age . '<br />';
		$output .= 'Alignment: ' . $this->LCAlignment . $this->GEAlignment . '<br />';
		$output .= ' Str: ' . $this->stats['str'] . ' | Dex: ' . $this->stats['dex'] . ' | Con: ' . $this->stats['con'] . ' | Int: ' . $this->stats['int'] . ' | Wis: ' . $this->stats['wis'] . ' | Cha: ' . $this->stats['cha'] . '<br />';
		$output .= $this->CharStatus . ' ' . $this->charclass . ' (' . $this->subclass .') , Level: ' . $this->level . '<br />';
		$output .= $this->parentage[0] . ' ' . $this->parentage[1] . '<br />';
		return $output;
	}
}

?>