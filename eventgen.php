<?php

/** 
 *
 * Event Generator.
 *


Timeline generation - will store for the city in citygen. 

This will create a timeline of X days - then simulate the changes over time. 
Significant events - 
Supply - Boon or shortage
	This either improves reproduction rate or lowers reproduction rate by % given
	affects danger
	
War - Active, Beseiged, Supply point
	active - Town is attacked and many are killed. % gives amount killed. 
	beseiged - Town supplies dwindle in this time. 
	supply point - Adds people to the town. these are always warriors and support.
	increases danger. 
	
Population - Immigration or emigration. 
	Species - Add or remove
	Immigration increases population by %
	emmigration decreases by percent. 
	
	
Power Center - Add or remove
	Add - New center, higher percent increases change of minor major or support. 
	remove - one is destroyed for a reason. merge falls into this category. 
	increases or decreased danger by alignment match
	
Weather/Disaster.
	reduces population, reduces supply.

New Threat
	adds a threat, % determines strength. 
	increases danger. 
	
Non-significant
	something non-significant. 
	
	

Each event has a severity score, which effects how much occurs from 1% to 100% 

Danger Meter determines the "type" that can occur. 

Historical Events will only be "storyline" and won't have active effects on the game. 

recent events are completed events will have in-game applications, without increasing current situation

current events are existing issues. 

so you'll have: 
Historic
Recent
Current.


 
 *
 */
 
class EventGenerator {
	
	private $TimelineType;
	private $EventType;
	private $EventSeverity;
	private $GoodBad;
	private $year;
	private $OutputChanges;
	private $ID;
	
	private $EventResults = array();
	
	function __construct($TimelineType, $danger, $year) {
		
		$this->TimelineType = $TimelineType;
		
		if($TimelineType == 'Historical'){
			$this->year = floor(($year * pow((mt_rand(1,1000000)/1000000), .25)/2)) + floor($year / 2);
		} elseif($TimelineType == 'Recent'){
			$this->year = floor(50 * pow((mt_rand(1,1000000)/1000000), .25)) + ($year - 50);
		} elseif($TimelineType == 'Current'){
			$this->year = $year;
		} else {
			$this->year = floor(5 * pow((mt_rand(1,1000000)/1000000), 1.5)) + $year;
		}
		$this->SetTimelineStats($danger);
		
		$this->OutputChanges = $this->SetImportantInformation($this->EventType, $this->GoodBad);
	}
	
	private function SetTimelineStats($danger = false){
		$number = mt_rand(1,100) - 45 - ($danger * 7);
		
		if($number > 10) {
			$this->GoodBad = 'G';
		} elseif($number < -10) {
			$this->GoodBad = 'B';
		} else {
			$this->GoodBad = 'N';
		}
		
		$number = floor(100 * pow((mt_rand(1,1000000)/1000000), 1.5)) + 1;
		
		if($this->TimelineType == 'Historical'){
			$number += 50;
		} 
		
		if($number > 100){
			$number = floor($number / 1.5);
		}
		
		$this->EventSeverity = $number;
		
		$this->EventType = $this->GetEvent($this->GoodBad);
		
		
		
		
		
		
	}
	
	private function GetEvent($align) { 
		
		if($align == 'G'){
			$arr = array('Immigration', 'Emigration', 'War Supply Point', 'Power Center', 'Non-Significant', 'Threat');
		} elseif($align == 'B') { 
			$arr = array('Immigration', 'Emigration', 'War Active', 'War Beseiged', 'War Supply Point', 'Power Center', 'Weather', 'Threat');
		} else {
			$arr = array('Non-Significant');
		}	
		
		return $arr[array_rand($arr)];	
		
	}
	
	private function SetImportantInformation($type, $align){
		
		$TownActions = array();
		
		//$TownActions[''] = array('Strength' => $this->EventSeverity);
		
		switch($type){
			case 'Immigration':
				// add people to area either friendly or bad
				// either generic or specific species. 
				// add pop (g or n)
				// add threat (group) (e)
				if($align != 'B'){
					$TownActions['AddThreat'] = array(
						'Strength' => $this->EventSeverity,
						'Location' => 'In Town'
					);
					$TownActions['AddDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				} else {
					$TownActions['AddPop'] = array(
						'Strength' => $this->EventSeverity,
						'Race' => mt_rand(1,2)
					);
					$TownActions['AddWealth'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
					$TownActions['AddSupply'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				}
				break;
			case 'Emigration':
				// remove people from area 1(general) 2(species)
				// either generic or specific species.
				// remove pop (gnb)
				$losstype = array('Emigration', 'Death', 'Abduction');
				$TownActions['RemovePop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => mt_rand(1,2),
					'Type' => $losstype[array_rand($losstype)]
				);
				if($align == 'B'){
					$TownActions['ReduceSupply'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
					$TownActions['ReduceWealth'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
					$TownActions['AddDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				}
				break;
			case 'War Active':
				// remove people, 
				// remove pop (nb)
				// remove wealth (nb)
				// destroy buildings
				$TownActions['RemovePop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => 2,
					'Type' => 'Death',
					'Optional' => false
				);
				$TownActions['DestroyBuildings'] = array(
					'Strength' => $this->EventSeverity
				);
				$TownActions['ReduceWealth'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				$TownActions['AddDanger'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				$TownActions['AddThreat'] = array(
					'Strength' => $this->EventSeverity,
					'Type' => 'Army',
					'Location' => 'Camp'
				);
				break;
			case 'War Beseiged':
				// remove people, reduce supply level
				// remove pop (nb)
				// reduce supply (nb)
				// add threat(group) (nb)
				$TownActions['AddThreat'] = array(
					'Strength' => $this->EventSeverity,
					'Type' => 'Army',
					'Location' => 'Camp'
				);
				$TownActions['ReduceSupply'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => false
				);
				$TownActions['RemovePop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => 2,
					'Type' => 'Death',
					'Optional' => true
				);
				$TownActions['AddDanger'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				break;
			case 'War Supply Point':
				// change pop and supply
				// add pop(military) supply (g)
				// add pop(military) reduce supply (nb)
				$TownActions['AddPop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => mt_rand(1,2),
					'MatchRace' => false,
					'Class' => 'Military'
				);
				$TownActions['ReduceDanger'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				if($align != 'B'){
					$TownActions['AddSupply'] = array(
						'Strength' => $this->EventSeverity
					);
				} else {
					$TownActions['ReduceSupply'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => false
					);
				}
				break;
			case 'Power Center':
				// add or remove
				// add good or remove bad ( g)
				// add bad or remove good (b)
				// add alternate (n)
				$rand = mt_rand(1,2);
				
				if($align != 'B'){
					if($rand == 1){ 
						$TownActions['AddPowerCenter'] = array(
							'Strength' => $this->EventSeverity,
							'Type' => 'Good'
						);
					} else {
						$TownActions['RemovePowerCenter'] = array(
							'Strength' => $this->EventSeverity,
							'Type' => 'Bad'
						);
					}
					$TownActions['ReduceDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				} else {
					if($rand == 1){ 
						$TownActions['AddPowerCenter'] = array(
							'Strength' => $this->EventSeverity,
							'Type' => 'Bad'
						);
					} else {
						$TownActions['RemovePowerCenter'] = array(
							'Strength' => $this->EventSeverity,
							'Type' => 'Good'
						);
					}
					$TownActions['AddDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				}
				break;
			case 'Weather':
				// reduce population, reduce supply , destroy building
				$TownActions['ReduceSupply'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				$TownActions['DestroyBuildings'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				$TownActions['RemovePop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => 2,
					'Type' => 'Death',
					'Optional' => true
				);
				break;
			case 'Threat':
				// add threat (b)
				// remove threat (g)
				if($align != 'B'){
					$TownActions['RemoveThreat'] = array(
						'Strength' => $this->EventSeverity
					);
					$TownActions['ReduceDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				} else {
					$TownActions['AddThreat'] = array(
						'Strength' => $this->EventSeverity
					);
					$TownActions['AddDanger'] = array(
						'Strength' => $this->EventSeverity,
						'Optional' => true
					);
				}
				break;
			case 'Non-Significant':
				// storyline only.
				$TownActions['AddWealth'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				); 
				$TownActions['AddSupply'] = array(
					'Strength' => $this->EventSeverity,
					'Optional' => true
				);
				break;
		}
		
		/*$TownActions['Time'] = array(
			'Time' => $this->TimelineType
		);*/
		
		return $TownActions;	
	}
	
	public function returnChanges($ret){
		
	//	$this->EventResults = $ret;
		
		foreach($ret as $k => $r){
			$this->EventResults[key($r)] = $r;
		}
		
		
		//echo '<br /><br />';
	}
	
	public function GetPopLoss(){
		
		//var_dump($this->EventResults);
		
		if(isset($this->EventResults['PopLoss'])){
			return $this->EventResults['PopLoss'];
		} else {
			return false;
		}
		
	}
	
	public function GetOverview(){
		$output = 'Year: ' . $this->year . ' ' . $this->EventType . ' ' . $this->EventSeverity . ' ' . $this->GoodBad;
		
		
		foreach($this->EventResults as $key => $arr){
			foreach($arr as $k => $a){
				
				
				if(is_array($a)){
					$output .= '<br />' . $k;
					// . ': ' . $a;
					foreach($a as $kk => $aa){
						if($aa != 0){
							$output .= '<br />--' . $kk . ': ' . $aa;
						}
					}
					
				} else {
				
				
					$output .= '<br />' . $k . ': ' . $a;
				
				}
				
			}
			
		}
		$output .= '<br />';
		return $output;
	}
	
	public function OutputActions(){
		// release a list of actions to output and do. 
		return $this->OutputChanges;
	}
}

?>