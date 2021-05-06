<?php

/** 
 *
 * Threat Generator.
 *

leadership
race
class

*/
 
class ThreatGenerator {
	
	private $GroupType;
	private $UnitCount = 0;
	private $race;
	private $strength;
	private $leadership;
	private $location;
	private $status;
	private $origin;
	
	function __construct($TownPopulation, $strength, $status='Active', $origin='Random', $size=false, $location=false) {
		
		$this->GeneratePopulation($TownPopulation, $strength, $size, $location);
		$this->strength = $strength;
		$this->GenerateLeadership();
		$this->status = $status;
		$this->origin = $origin;
	}
	
	public function SetStatus($status){
		// statuses - Active, Forming, Destroyed
		$this->status = $status;
	}
	
	public function ThreatStatus(){
		return $this->status;
	}
	
	public function ThreatStrength(){
		return $this->strength;
	}
	
	private function GeneratePopulation($TownPopulation, $strength, $size, $location){
		
		$groupings = array(
			'Single' => 1, 
			'Pair' => 2, 
			'Gang' => '3-6', 
			'Group' => '40%', 
			'Horde' => '100%', 
			'Army' => '1000%',
			'Grand Army' => '10000%'
			);
		
		if($size == 'Army'){
			$groupings = array(
				'Horde' => '100%', 
				'Army' => '1000%',
				'Grand Army' => '10000%'
			);
		}
		
		$this->GroupType = array_rand($groupings);
		
		switch($this->GroupType){
			case 'Single':
				$this->UnitCount = 1;
				$races = 1;
				$this->GenerateLocation('Small', $location);
				break;
			case 'Pair':
				$this->UnitCount = 2;
				$races = 1;
				$this->GenerateLocation('Small', $location);
				break;
			case 'Gang':
				$min = 3;
				$max = 6;
				$races = mt_rand(1,2);
				$this->GenerateLocation('Medium', $location);
				break;
			case 'Group':
				$min = ceil(6 * ($strength / 100));
				$max = ceil($TownPopulation * 0.4 * ($strength / 100));
				$races = mt_rand(1,3);
				$this->GenerateLocation('Medium', $location);
				break;
			case 'Horde':
				$min = ceil($TownPopulation * 0.4 * ($strength / 100));
				$max = ceil($TownPopulation * ($strength / 100));
				$races = mt_rand(1,4);
				$this->GenerateLocation('Large', $location);
				break;
			case 'Army':
				$min = ceil($TownPopulation * ($strength / 100));
				$max = $TownPopulation * ($strength / 10);
				$races = mt_rand(1,6);
				$this->GenerateLocation('Large', $location);
				break;
			case 'Grand Army':
				$min = ceil($TownPopulation * ($strength / 100));
				$max = $TownPopulation * $strength;
				$races = mt_rand(1,8);
				$this->GenerateLocation('Large', $location);
				break;
		}
		
		for($i=0;$i<$races;$i++){
			$this->race[$i] = $this->GenerateRace();
			$this->race = array_unique($this->race);
		}
		
		$this->race = array_unique($this->race);
		
		
		
		if($this->UnitCount == 0){
			//echo 'count here';
			$this->UnitCount = floor($max * pow((mt_rand(1,1000000)/1000000), 2)) + $min;
		}
	}
	
	private function GenerateRace(){
		
		$number = mt_rand(0,99);
		
		//echo $number . '<br />';
		
		$sql = new sqlControl();
		$stmt = 'SELECT Race FROM RaceTypeInteractions WHERE Priority > :Priority';
		$value = array(':Priority' => $number);
		
		
		
		
		foreach($this->race as $race){
			if($race != ''){
				$stmt .= ' AND ' . $race . ' = 1';
			}
		}
		
		$sql->sqlCommand($stmt, $value);
		
		$vals = $sql->returnAllResults();
		
		return $vals[array_rand($vals)]['Race'];

	}
	
	private function GenerateLeadership(){
		// generate the number of leaders and what they fall into. 
		// this may use the additional class information. 
		$max = $this->UnitCount;
		if($max > 12){
			$max = 12;
		}
		
		$this->leadership['count'] = floor($max * pow((mt_rand(1,1000000)/1000000), 6)) + 1;
		
	}
	
	private function GenerateLocation($size, $location=false){
		// generates the location of the threat, and how it lives. 
		
		if($location != false){
		
			$this->location = $location;
		} else {
			$sql = new sqlControl();
			$stmt = 'SELECT Location FROM ThreatLocation WHERE ' . $size . ' = 1';
			$value = array();
			$sql->sqlCommand($stmt, $value);
		
			$vals = $sql->returnAllResults();
	
			$this->location = $vals[array_rand($vals)]['Location'];
		}
		
	}
	
	public function GetThreatOverview() { 
	
		$output = $this->GroupType . ': ' . $this->UnitCount . '<br /> Races: ';
		foreach($this->race as $r){
			$output .= $r . ', ';
		}
		$output .= '<br /> Strength: ' . $this->strength . '<br />';
		$output .= 'Leadership: ' . $this->leadership['count'] . '<br />';
		$output .= 'Location: ' . $this->location . '<br />';
		$output .= 'Status: ' . $this->status . '<br />';
		$output .= 'Origin: ' . $this->origin . '<br /><br />';
		return $output;
	}
	
}

?>