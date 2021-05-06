<?php

/** 
 * main class dependencies
 *
 *
 *
 */

	require_once('townstats.php');
	require_once('charactergen.php');
	require_once('eventgen.php');
	require_once('powergen.php');
	require_once('sqlcontrol.php');
	require_once('genfunctions.php');
	require_once('threatgen.php');
	
	function make_seed(){
		list($usec, $sec) = explode(' ', microtime());
		return $sec + $usec * 1000000;
	}
	
	
	
	$townseed = make_seed();

//	$townseed = 1554661266;
	
//	$townseed = 1550701133;
	
	mt_srand($townseed);
	srand($townseed);

	class CityGenerator {
	
		private $theCity;
	
		private $climate;
		private $terrain;
		
		private $dangerlevel;
		//private $dangerpoints = 0;
		
		private $year;
		private $supplylevel;
		
		private $timeline = array();
		private $threats = array();
		
		private $people = array();
		private $PAges = array();
		
		private $buildings = array('Ruins' => array('Old' => 0, 'Current' => 0, 'Future' => 0));
	
		function __construct(){
			$this->GenerateClimate();
			$this->GenerateTerrain();
			
			$this->SetDangerLevel();
			
			
			
			$this->setSupplyLevel();
			
			$this->theCity = new TownGenerator($this->terrain, $this->climate, $this->dangerlevel['Number']);
			
			$this->GenerateThreats();
			$this->GenerateTimeline();
			
			$this->GeneratePeople();
			
		}
		
		private function setSupplyLevel($change=false){
			if($change > 0){
				// change the value up or down depending on the existing levels. 
	
				$this->supplylevel += floor((100 - $this->supplylevel) * ($change / 100));
	
			} elseif($change < 0){
					
				$this->supplylevel -= floor((-100 - $this->supplylevel) * ($change / 100));
								
			} else {
			
				// sets a supply level for the town between -100 and 100. 
				// andything over 0 is creating more supply than is actually needed. 
				$this->supplylevel = floor(200 * pow((mt_rand(1,1000000)/1000000), .5)) - 100;			
			
			}
			
		}

		private function SetDangerLevel($preset = false, $plusminus = false){
			// Safe Calm Mild Moderate Hazardous Dangerous Deadly
			
			if(!$preset){
				$this->dangerlevel['BaseNumber'] = mt_rand(1,7);
			} else { 
				if($plusminus == 1) { 
					$this->dangerlevel['BaseNumber']++;
				} else { 
					$this->dangerlevel['BaseNumber']--;
				}
			}
			switch ($this->dangerlevel['BaseNumber']){
				case 1:
					$this->dangerlevel['Name'] = 'Safe';
					break;
				case 2:
					$this->dangerlevel['Name'] = 'Calm';
					break;
				case 3:
					$this->dangerlevel['Name'] = 'Mild';
					break;
				case 4:
					$this->dangerlevel['Name'] = 'Moderate';
					break;
				case 5:
					$this->dangerlevel['Name'] = 'Hazardous';
					break;
				case 6:
					$this->dangerlevel['Name'] = 'Dangerous';
					break;
				case 7:
				default:
					$this->dangerlevel['Name'] = 'Deadly';
					$this->dangerlevel['BaseNumber'] = 7;
					break;
			}
			
			$this->dangerlevel['Number'] = $this->dangerlevel['BaseNumber'] - 3;
			if($this->dangerlevel['Number'] < 0){
				$this->dangerlevel['Number'] = 0;
			}
			
			
		}
		
		private function GenerateThreats(){
			
			//$this->dangerlevel['Number']
			$count = mt_rand(0,4) + $this->dangerlevel['Number'];
			
			
			
			for($i=0;$i<$count;$i++){
				$threatdanger = mt_rand(1,60) + ($this->dangerlevel['Number'] * 10);
				$this->threats[$i] = new ThreatGenerator($this->theCity->GetPopulation(), $threatdanger);
			}
			//echo '<br />Threats: ';
			//var_dump($this->threats);
			//echo '<br /><br />';
			
		}
		
		private function GenerateTimeline(){
			// generate a full timeline of events, start historical and work up to current. 
			//$this->dangerlevel['Number']
			//$this->theCity->GetPopulation()
			
			//generate a year for a starter point. 
			$this->year = mt_rand(1000,5000); 
			
			// historical
			// these events are major, and make up talking points in history. 
			$this->timeline['Historical']['Count'] = RollDice(4,1) + ceil($this->theCity->GetPopulation() / 100000);
			for($i=0;$i<$this->timeline['Historical']['Count'];$i++){
				$this->timeline['Historical'][$i] = new EventGenerator('Historical', $this->dangerlevel['Number'], $this->year);
			}
			unset($this->timeline['Historical']['Count']);
			
			$this->RunHistoryUpdates('Historical');
			
			// recent
			// these can be any, but add to flavour and reactions in the town. 
			$this->timeline['Recent']['Count'] = RollDice(4,2) + ceil($this->theCity->GetPopulation() / 100000);
			for($i=0;$i<$this->timeline['Recent']['Count'];$i++){
				$this->timeline['Recent'][$i] = new EventGenerator('Recent', $this->dangerlevel['Number'], $this->year);
			}
			unset($this->timeline['Recent']['Count']);
			
			$this->RunHistoryUpdates('Recent');
				
			// current
			// these are happening now. active events 
			$this->timeline['Current']['Count'] = RollDice(4,1) + $this->dangerlevel['Number'];
			for($i=0;$i<$this->timeline['Current']['Count'];$i++){
				$this->timeline['Current'][$i] = new EventGenerator('Current', $this->dangerlevel['Number'], $this->year);
			}
			unset($this->timeline['Current']['Count']);
			
			$this->RunHistoryUpdates('Current');
			
			// near future
			// these are future events for the job board, storyline material!!!
			$this->timeline['Future']['Count'] = RollDice(6,1) + $this->dangerlevel['Number'];
			for($i=0;$i<$this->timeline['Future']['Count'];$i++){
				$this->timeline['Future'][$i] = new EventGenerator('Future', $this->dangerlevel['Number'], $this->year);
			}
			unset($this->timeline['Future']['Count']);
			
			$this->RunHistoryUpdates('Future');
			
			
		}
		
		private function RunHistoryUpdates($type){
			// run through existing data. 
			
			foreach($this->timeline[$type] as $key => $event){
				
				$ret = array();
				
				foreach($event->OutputActions() as $action => $data){
					// manage the inividual actions. 
					//var_dump($action);
					
						//echo $action;
					
					$ret[] = $this->$action($type, $data);
				}
				//echo '<br />';
				
				$this->timeline[$type][$key]->returnChanges($ret);
			}
			
			
			
			
		}
		
		private function GeneratePeople(){
			
			$races = $this->theCity->GetRaces();
			
			//var_dump($races);
			
			$sql = new sqlControl();
			
			$raceevents = array();
			
			//echo '<br /><br />';	
			
			foreach($this->timeline as $timetypekey => $timetype){
				foreach($timetype as $timekey => $event){
					
					$lossevent = $event->GetPopLoss();
					
					if($lossevent){
						//var_dump($lossevent);
						//echo '<br />';
						
						foreach($lossevent['PopLoss'] as $racename => $loss){
							
							//echo 'Race: ' . $racename . ' Loss: ' . $loss .  ' type: ' . $lossevent['losstype'] . '<br />';
						
							if($racename != '' && $lossevent['losstype'] != '' && $loss > 0){
								$raceevents[$racename][$timetypekey][$timekey] = array('type' => $lossevent['losstype'], 'count' => $loss);
							}
						}	
					}
				}
			}
			
			//var_dump($raceevents);
			
		//	echo '<br />';	
			
			foreach($races as $key => $race){
				
				$stmt = 'SELECT Adulthood, AgeLimit, AlignmentGE, AlignmentLC, MaxWives, MaxHusbands, Strength, Dexterity, Constitution, Intelligence, Wisdom, Charisma, MaxChildren FROM Races WHERE Race = :race AND Edition = "3.5e" LIMIT 1';
			
				$value = array(':race' => $key);
				$sql->sqlCommand($stmt, $value);
			
				$vals = $sql->returnAllResults();
			
				//var_dump($vals);
				
				$majority = $vals[0]['Adulthood'];
				$maxage = $vals[0]['AgeLimit'];
				$maxcount = ceil($maxage/$majority);
			
				$topcount = 0;
				//echo '<br />';
				//$unlimcount = 0;
				
				for($i=1;$i<$maxcount;$i++){
					$topcount += $i ** ($this->dangerlevel['BaseNumber'] + 1);
				//	$unlimcount += $i ** 2;
					//var_dump($this->dangerlevel);
					//echo $topcount . '<br />';
				}
				
				$total = 0;
			
				//echo '<br />' . $key . '<br />';
				for($i=0;$i<$maxcount;$i++){
					
					$this->PAges[$key][$i]['youngest'] = $majority * $i;
					$this->PAges[$key][$i]['oldest'] = $majority * ($i + 1);
					$this->PAges[$key][$i]['max'] = ceil($race * ((($maxcount - $i) ** ($this->dangerlevel['BaseNumber'] + 1))) / $topcount);
					$this->PAges[$key][$i]['min'] = floor($this->PAges[$key][$i]['max'] * ($this->supplylevel / 100));
					
					$this->PAges[$key][$i]['AlignmentGE'] = $vals[0]['AlignmentGE'];
					$this->PAges[$key][$i]['AlignmentLC'] = $vals[0]['AlignmentLC'];
					$this->PAges[$key][$i]['MaxWives'] = $vals[0]['MaxWives'];
					$this->PAges[$key][$i]['MaxHusbands'] = $vals[0]['MaxHusbands'];
					$this->PAges[$key][$i]['Strength'] = $vals[0]['Strength'];
					$this->PAges[$key][$i]['Dexterity'] = $vals[0]['Dexterity'];
					$this->PAges[$key][$i]['Constitution'] = $vals[0]['Constitution'];
					$this->PAges[$key][$i]['Intelligence'] = $vals[0]['Intelligence'];
					$this->PAges[$key][$i]['Wisdom'] = $vals[0]['Wisdom'];
					$this->PAges[$key][$i]['Charisma'] = $vals[0]['Charisma'];
					$this->PAges[$key][$i]['MaxChildren'] = $vals[0]['MaxChildren'];
					
					if($this->PAges[$key][$i]['max'] < 10 && $this->PAges[$key][$i]['max'] > -10) {
						$this->PAges[$key][$i]['min'] -= 10;
					}
					
					if($race > 0){
						$this->PAges[$key][$i]['Count'] = mt_rand($this->PAges[$key][$i]['min'], $this->PAges[$key][$i]['max']);
					} else {
						$this->PAges[$key][$i]['Count'] = 0;
					}
					
					$this->PAges[$key][$i]['Dead'] = 0;
					$this->PAges[$key][$i]['Away'] = 0;
					
					if($this->PAges[$key][$i]['Count'] <= 0){
						$this->PAges[$key][$i]['Count'] = 0;
					} else {				
						$total += $this->PAges[$key][$i]['Count'];
						
						//echo $key . ' Total ' . $total . ' count: ' . $this->PAges[$key][$i]['Count'] . '<br />';
					}
					
					
				///\	echo 'Count b ' . $this->PAges[$key][$i]['Count'] . '<br />';
					
					$left = $race - $total;
					
					if($this->PAges[$key][$i]['Count'] > $left){
						$this->PAges[$key][$i]['Count'] = $left;
						$left = 0;
					}
					 //echo 'count: ' . $this->PAges[$key][$i]['Count'] . '<br />';
				}
			
				
				
				
			//	echo $key . ' Left: ' . $left . '<br />';
				
				$total = 0;
				foreach($this->PAges[$key] as $datx => $num){
					if($datx != 0){
						if($num['Count'] > 0){
							$total += $num['Count'];
						}
					}
				}
				
				$left = $race - $total;
			//	echo $key . ' Left: ' . $left . ' Race: ' . $race . ' Total: ' . $total . '<br />';
				
				 //while($left > 0) {
				for($r=0;$r<$left;$r++){
					$m = count($this->PAges[$key]) - 1;
					$choice = floor($m * pow((mt_rand(1,1000000)/1000000), $this->dangerlevel['BaseNumber'] + 1) + 1);
					$this->PAges[$key][$choice]['Count']++;
				}
				
				$themissingmax = $race * ($this->dangerlevel['Number'] + 1);
				$themissingmin = $race * -1;
				$missingcount = mt_rand($themissingmin, $themissingmax);
				
				if($missingcount < 1){
					$missingcount = 0;
				}
				
				for($r=0;$r<$missingcount;$r++){
					$m = count($this->PAges[$key]) - 1;
					$choice = floor($m * pow((mt_rand(1,1000000)/1000000), $this->dangerlevel['BaseNumber'] + 1) + 1);
					$this->PAges[$key][$choice]['Away']++;
				}
				
				if($this->supplylevel > 1){
					$deadsupply = (($this->supplylevel * -1) / 100) + 1;
				} else {
					$deadsupply = 1;
				}
				
				$thedeadmax = round($themissingmax * $deadsupply);
				$deadcount = mt_rand($themissingmin, $thedeadmax);
				
				for($r=0;$r<$deadcount;$r++){
					$m = count($this->PAges[$key]) - 1;
					$choice = floor($m * pow((mt_rand(1,1000000)/1000000), $this->dangerlevel['BaseNumber'] + 1) + 1);
					$this->PAges[$key][$choice]['Dead']++;
				}
				
				
				//////////////////////////
				//run a foreach here    //
				//to set event losses   //
				//////////////////////////
				
				foreach($raceevents[$key] as $EventRaceTypeData) {
					//$raceevents[$racename][$timetypekey][$timekey] = array('type' => $lossevent['losstype'], 'count' => $loss);
					foreach($EventRaceTypeData as $EventData){
						if($EventData['type'] == 'Emigration' || $EventData['type'] == 'Abduction'){
							
							//echo $key . ' Missing: ' . $EventData['count'] . '<br />';
						
							for($r=0;$r<$EventData['count'];$r++){
								$m = count($this->PAges[$key]) - 1;
								$choice = floor($m * pow((mt_rand(1,1000000)/1000000), $this->dangerlevel['BaseNumber'] + 1) + 1);
								$this->PAges[$key][$choice]['Away']++;
							}
						
						} elseif($EventData['type'] == 'Death') {
								
							//echo $key . ' Dead: ' . $EventData['count'] . '<br />';
						
							for($r=0;$r<$EventData['count'];$r++){
								$m = count($this->PAges[$key]) - 1;
								$choice = floor($m * pow((mt_rand(1,1000000)/1000000), $this->dangerlevel['BaseNumber'] + 1) + 1);
								$this->PAges[$key][$choice]['Dead']++;
							}
							
						}
					}
				}		
			}
			
			
			
			//var_dump($this->PAges);
			
			$personnum = 0;
			
			//////////////////////////////
			// Begin Person Development //
			//////////////////////////////

			$stmt = 'SELECT ClassName, Stat1, Stat2, Stat3 FROM Classes WHERE Version = "3.5e"';			
			$value = array();
			$sql->sqlCommand($stmt, $value);
			
			$vals = $sql->returnAllResults();
			
			$classes = $this->theCity->GetClassBreakdowns();
//var_dump($classes);
			$classstats = array();
			
			foreach ($vals as $vclass){
				if($classes[$vclass['ClassName']] != null){
				//	$classstats[$vclass['ClassName']] = array( $vclass['Stat1'], $vclass['Stat2'], $vclass['Stat3']);
					
					
					if($vclass['Stat1'] != ''){
						$classstats[$vclass['Stat1']][] = $vclass['ClassName'];
					} else {
						$classstats['str'][] = $vclass['ClassName'];
						$classstats['dex'][] = $vclass['ClassName'];
						$classstats['con'][] = $vclass['ClassName'];
						$classstats['int'][] = $vclass['ClassName'];
						$classstats['wis'][] = $vclass['ClassName'];
						$classstats['cha'][] = $vclass['ClassName'];
					}
					if($vclass['Stat2'] != ''){
						$classstats[$vclass['Stat2']][] = $vclass['ClassName'];
					}
					if($vclass['Stat3'] != ''){
						$classstats[$vclass['Stat3']][] = $vclass['ClassName'];
					}
				}
			}
			
			$sql = '';
			
			foreach($this->PAges as $key => $data){
				//echo '<br />' . $key . '<br />';
				
				$racetotal = $race;
				$max = count($data);
				
				$ParentArray = array();
				
				//foreach($d as $kk => $dd){
				
				//echo '<br /><br />Race: ' . $key . '<br />';
				//var_dump($data);
				//echo '<br />';
				
				for($i = $max;$i >= 0;$i--){	
					
					$racecounts = array('Dead' => $this->PAges[$key][$i]['Dead'], 'Away' => $this->PAges[$key][$i]['Away'], 'Alive' => $this->PAges[$key][$i]['Count']);
					
					if($racecounts['Alive'] < 0){
						$racecounts['Alive'] = 0;
					}
					
					foreach($racecounts as $racestat => $statcount){
						for($c = 0; $c < $statcount; $c++){
							
							$PersonNo = count($this->people);
							
							
							//echo 'Person: '.  $PersonNo . '<br />';
							
							//$RacePassdownData = array(); //lastname, childcount, 
							/*
							if($this->PAges[$key][$i]){
								var_dump($this->PAges[$key][$i]);
								echo '<br />';
							}
							*/
					
							/*ChildrenPerBrood, MaxChildren 
							parents
							spouse(s)
							get last name , maiden
					
					
							
							*/
						
						// 	Each person Setup: 
						//	Determine Age, between 2 numbers
							$age = mt_rand($this->PAges[$key][$i]['youngest'], $this->PAges[$key][$i]['oldest']);
					
						//	Determine Gender. Male/Female/Other
							$gd = array('Male','Female','Male','Female','Male','Female');
							$gender = $gd[array_rand($gd)];
					
						//	Determine Alignment - Use Town skew, racial alignment, and parental alignment.
							$al = $this->theCity->GetTownAlignment(true);
					
							$alignment = array(
									'GE' => array($al['GE'], $this->PAges[$key][$i]['AlignmentGE']), 
									'LC' => array($al['LC'], $this->PAges[$key][$i]['AlignmentLC'])
									);
							
						//	Determine Parents (if any)
							if($i != $max){
						
								// existing couple or new family line. 
								$parentorsolo = mt_rand(1,10); //this'll be tied to danger + supply. 
								$chancenewdanger = $this->dangerlevel['Number'] + 1;
								$changenewsuply = ceil($this->supplylevel / 10);
						
								$chancenew = floor(($changenewsuply - $chancenewdanger + 1) / 2);
								
								//echo '<br /><br />Chance New: ' . $chancenew . ' array: '  . count($ParentArray[$i + 1]) . ' parent: ' . $parentorsolo . '<br />';
								
								if($chancenew < 1){
									$chancenew = 1; // always a 10% chance. 
								}
								
								//echo 'Parent Array: ';
								//var_dump($ParentArray[$i + 1]);
								//echo '<br />';
						
								if($parentorsolo > $chancenew && count($ParentArray[$i + 1]) > 0){
							
									$pcchoice = array_rand($ParentArray[$i + 1]);
									$pc = $ParentArray[$i + 1][$pcchoice];
									
									//echo 'Parents Chosen <br />';
									
									//if('MaxChildren'
									
									$childmax = count($pc[0]) * count($pc[1]) * $this->PAges[$key][$i]['MaxChildren'];
									
									$parentfail = false;
									
									if(count($ParentArray[$i + 1][$pcchoice][2]) > $childmax){
										$pcchoice = array_rand($ParentArray[$i + 1]);
										$childmax = count($pc[0]) * count($pc[1]) * $this->PAges[$key][$i]['MaxChildren'];
										
										if(count($ParentArray[$i + 1][$pcchoice][2]) > $childmax){
											$pcchoice = array_rand($ParentArray[$i + 1]);
											$childmax = count($pc[0]) * count($pc[1]) * $this->PAges[$key][$i]['MaxChildren'];
											
											if(count($ParentArray[$i + 1][$pcchoice][2]) > $childmax){
												$pcchoice = array_rand($ParentArray[$i + 1]);
												$childmax = count($pc[0]) * count($pc[1]) * $this->PAges[$key][$i]['MaxChildren'];
												
												if(count($ParentArray[$i + 1][$pcchoice][2]) > $childmax){
													// parent fail.
													$parentfail = true;
													
												}
											}
										}	
									} 
									
									if(!$parentfail){
										
										$father = $pc[0][array_rand($pc[0])];
										$mother = $pc[1][array_rand($pc[1])];
									
										$parents = array( //// FYI 0 = men, 1 = women
														$father => $this->people[$father], 
														$mother => $this->people[$mother]
														);
													
										$ParentArray[$i + 1][$pcchoice][2][] = $PersonNo;
									} else {
										$parents = array();
									}
									
								} else {
								//	echo 'Not Chosen: <br />';
									
									$parents = array(); // return an empty array for new family. 
								}
						
							} else {
					
								$parents = array(); // return an empty array for new family. 
					
							}
				
						//	Determine Spouse (If any) - This may be valid for couples already coupled, depending on race.
							if($i != 0){
								$IsMarried = mt_rand(1,10);
								$IsMarried = 1;
								if($IsMarried < 7){
					
									$valcouples = array();
						
									foreach($ParentArray[$i] as $karr => $parr){ // FYI 0 = men, 1 = women
									
										if(count($parr[0]) < $this->PAges[$key][$i]['MaxHusbands'] && $gender == 'Male'){
											$valcouples[] = $karr;
										} 
									
										if(count($parr[1]) < $this->PAges[$key][$i]['MaxWives'] && $gender == 'Female'){
											$valcouples[] = $karr;
										} 
									}
								
									// HERE - Determine spousal number using array rand, and add this person to the list
									// where it would be appropriate. also add this person as a listed spouse to
									// the existing spouses. 
								
									//$PersonNo
							
									if(count($valcouples) > 0){
										// only do if actual couples exist. 
									
										$chosenspouses = $valcouples[array_rand($valcouples)];
									
										if($gender == 'Male'){
											$ParentArray[$i][$chosenspouses][0][] = $PersonNo;
										} else {
											$ParentArray[$i][$chosenspouses][1][] = $PersonNo;
										}
									} else {
										// create a new couple
										if($gender == 'Male'){
											$ParentArray[$i][count($ParentArray[$i])][0][] = $PersonNo;
										} else {
											$ParentArray[$i][count($ParentArray[$i])][1][] = $PersonNo;
										}
									}
						
									$spouse = true; // will be determined from the list of parents. 
						
					
								} else {
									$spouse = false;
								}
							}
					
							//	Generate person. Generated stats will determine the remainder. 
							/*echo $key . ': <br /> Age:';
							var_dump($age);
							echo '<br /> Gender: ';
							var_dump($gender);
							echo '<br /> Alignment: ';
							var_dump($alignment);
							echo '<br /> Parents: ';
							var_dump($parents);
							echo '<br /> Spouse: ';
							var_dump($spouse);
							echo '<br />';
							*/
							$statbonuses = array(
												'Strength' => $this->PAges[$key][$i]['Strength'],
												'Dexterity' => $this->PAges[$key][$i]['Dexterity'],
												'Constitution' => $this->PAges[$key][$i]['Constitution'],
												'Intelligence' => $this->PAges[$key][$i]['Intelligence'],
												'Wisdom' => $this->PAges[$key][$i]['Wisdom'],
												'Charisma' => $this->PAges[$key][$i]['Charisma']
												);
							
							// function __construct($gealign, $lcalign, $age, $parents, $race, $lastname=false, $firstname=false) {
							//  = new CharacterGenerator('L','G', $age, array(), $key, $lname);
							// echo 'LastName: ' . $this->people[$personnum]->GetLastName() . '<br />';
					
							
							$this->people[$PersonNo] = new CharacterGenerator($key, $age, $gender, $alignment, $parents, $statbonuses, $racestat);
							
							
							
							if($i != 0){ // DO NOT DO THIS FOR KIDS
							
							
							//	Determine class. This needs to be based on person's stats as well. 
								
								//$classes
								//$classstats
								
								$chosenclassoption = mt_rand(0,3);
								
								$parentcount = 0;
								if(!empty($parents)){
									foreach($parents as $keyp => $parent){
										
										$pclass[$parentcount] = $parent->GetCharClass();	
										
										//var_dump($pclass[$parentcount]['class']);
										
										//var_dump($classes[$pclass[$parentcount]['class']]);
										
										if(empty($classes[$pclass[$parentcount]['class']])){
											
											unset($pclass[$parentcount]);
										
										}
										
										$parentcount++;
									}
								}
								
								//var_dump($pclass);
								
								if(!isset($pclass[$chosenclassoption])){
									$chosenclassoption = 2;
								}
								
								$topstat = $this->people[$PersonNo]->GetStat('Strongest');
								$thisClass = '';
								$subClass = false;
								
								$output .= '[' . $chosenclassoption . ']';
								
								if($chosenclassoption < 2){
									
									// THIS IS WHERE PARENTS OCCUR
									$thisClass = $pclass[$chosenclassoption]['class'];
									$subClass = $pclass[$chosenclassoption]['subclass'];
									$classno = array_rand($classes[$thisClass]);
									
									
									if($classno == ''){
										$classno = 1;
									}
									$classes[$thisClass][$classno]--;
								
									if($classes[$thisClass][$classno] < 1 || empty($classes[$thisClass])){
										if($racestat == 'Alive'){
											unset($classes[$thisClass][$classno]);
									
											if(empty($classes[$thisClass])){
												unset($classes[$thisClass]);
												unset($classstats[$topstat][$classstatno]);
											}
										}
									}
								
								} elseif(!empty($classstats[$topstat])){
								
									$classstatno = array_rand($classstats[$topstat]);
									$thisClass = $classstats[$topstat][$classstatno];
									$classno = array_rand($classes[$thisClass]);
									
									
									if($classno == ''){
										$classno = 1;
									}
									$classes[$thisClass][$classno]--;
								
									if($classes[$thisClass][$classno] < 1 || empty($classes[$thisClass])){
										if($racestat == 'Alive'){
											unset($classes[$thisClass][$classno]);
									
											if(empty($classes[$thisClass])){
												unset($classes[$thisClass]);
												unset($classstats[$topstat][$classstatno]);
											}
										}
									}

								} elseif ( !empty( $classes ) ) {
	
									$thisClass = array_rand($classes);
									
								//	var_dump($classes[$thisClass]);
									
									$classno = array_rand($classes[$thisClass]);
									
									if($classno == ''){
										$classno = 1;
									}
									
									if($classes[$thisClass][$classno] < 1){
										unset($classes[$thisClass][$classno]);
									
										if(empty($classes[$thisClass])){
											unset($classes[$thisClass]);
											
										//	var_dump($classstats);
										}
									}
									
								} else {
									
									$thisClass = 'Commoner';
									$classno = 1;
								
								}
								
								
								
								$this->people[$PersonNo]->SetCharClass($thisClass, $classno, $subClass);
								
								
								
								//echo $thisClass . ', Level: ' . $classno . '<br /><br />';
							
							//	Determine relations to power centers
							//	Determine relations to events 
							//	Determine relations to threats
							//	Determine backstory
							//	Determine relations to other people. 
						
						
						
						
							} else {
								// only a child.
								$this->people[$PersonNo]->SetCharClass('Child', 1);
							}
							$output .= $PersonNo . ': ';
							$output .= $this->people[$PersonNo]->OutputDetails();
							/* Unknown Where it'll go:  */
							// 	Tie in all family members to each other - siblings, spouse,
						
						}
				
						///////////////////////////////
						//run a foreach here    	 //
						//to people for each event   //
						///////////////////////////////
							
							
							
							
						}
						
						
						//////////////////////////
						// parent array cleanup //
						//////////////////////////
					
						foreach($ParentArray[$i] as $parentagegroup => $parentgroupdata){
							if(count($parentgroupdata[0]) < 1 || count($parentgroupdata[1]) < 1){
								unset($ParentArray[$i][$parentagegroup]);
							}
						}

					}
				//	var_dump($ParentArray);
					
			}
			
		//	echo $output;
		}
		
		private function AddThreat($type, $data){ 
			
			$count = count($this->threats);
			
			//'Type' => 'Army',
			//'Location' => 'Camp'
			
			//echo $type;
			
			if($type == 'Historical'){
				$status = 'Destroyed';
			} elseif($type == 'Future') { 
				$status = 'Forming';
			} else {
				$status = 'Active';
			}
			
			if($data['Type']){
				$army = $data['Type'];
			} else {
				$army = false;
			}
			
			if($data['Location']){
				$locale = $data['Location'];
			} else {
				$locale = false;
			}
			
			$this->threats[$count] = new ThreatGenerator(500, $data['Strength'], $status, $type . ' Event', $army, $locale);
			
			return array('Threat' => $count, 'Status' => $status);
			
		}
		private function RemoveThreat($type, $data){ //echo 'two'; 
			
			$strength = 0;
			$threatz = -1;
			
			if(count($this->threats) > 0){
				foreach($this->threats as $key => $threat){
				
					if($threat->ThreatStatus() != 'Destroyed'){
						$thisstr = $threat->ThreatStrength();
					
						if($thisstr > $strength && $thisstr <= $data['Strength']){
							$strength = $thisstr;
							$threatz = $key;
						}
					
					}
				
				}
			
				$this->threats[$key]->SetStatus('Destroyed');
			}
			return array('Threat' => $threatz, 'Status' => 'Destroyed');
			
		}
		private function AddDanger($type, $data){ //echo 'three'; 
			
			//echo 'Add Danger: ' . $this->dangerlevel['points'] . '<br />';
			
			if($type == 'Historical' || $type == 'Future'){
				return array('Danger' => 0);
			}
			
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
				$this->dangerlevel['points'] += $random;
			} else { 
				$this->dangerlevel['points'] += $data['Strength'];
				$random = $data['Strength'];
			} 
			if($this->dangerlevel['points'] > 99){
				$this->dangerlevel['points'] -= 100;
				
				$this->SetDangerLevel(true, 1); //<- reprogram for the app.
				
				// echo 'Danger Level Increased<br />';
			}
			
			return array('Danger' => $random);
		
		}
		private function ReduceDanger($type, $data){ //echo 'four';
		
			//echo 'Reduce Danger: ' . $this->dangerlevel['points'] . '<br />';
			 
			if($type == 'Historical' || $type == 'Future'){
				return array('Danger' => 0);
				
			}
			 
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
				$this->dangerlevel['points'] -= $random;
			} else { 
				$this->dangerlevel['points'] -= $data['Strength'];
				$random = $data['Strength'];
			} 
			if($this->dangerlevel['points'] < -99){
				$this->dangerlevel['points'] += 100;
				
				$this->SetDangerLevel(true, -1); //<- reprogram for the app.
				
				// echo 'Danger Level Decreased<br />';
			}
			
			return array('Danger' => $random);
		}
		private function AddPop($type, $data){ //echo 'five'; 
			
			/*
				$TownActions['AddPop'] = array(
					'Strength' => $this->EventSeverity,
					'Race' => mt_rand(1,2),
					'MatchRace' => false,
					'Class' => 'Military'
				);
			*/
			
			if($data['Race'] == 1){
				// single race 
				$race = true;
			} else {
				// multi race
				$race = false;
			}
			
			if($data['Class'] == 'Military'){
				// single race 
				$military = true;
			} else {
				// multi race
				$military = false;
			}
			
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			$hrand = $random;
			
		
			if($random < 50){//1-49
				$random = ($random * 2) + mt_rand(0, 2);
			} elseif ($random < 80){//50-79
				$random = (($random - 50) * 6) + mt_rand(0, 20);
			} elseif ($random < 95){//80-95
				$random = (($random - 80) * 25) + mt_rand(0, 25);
			} else { // 96-100
				$random = (($random - 95) * 160) + mt_rand(0, 200);
			}
		
		
			$count = ceil($this->theCity->GetPopulation() * ($random / 100));
	
			if ($type == 'Historical') {
				// this will return an abstract number. 
				//echo 'PAST TO BE COMPLETED';
				
				$count = ceil($this->theCity->GetPopulation() * ($hrand/100));
				
				$races = $this->theCity->GetRaces();
				
				$rcount = -1;
				$rpick = '';
				if($race){
				
					foreach($races as $k => $c){
						if($c >= $rcount && $c <= $count){
							$rpick = $k;
							$rcount = $c;
						}
					}
					
					$return['Addpop'] = array($rpick => $rcount, 'GP' => floor($this->theCity->GetGPLimit() * ($hrand/100)));
					
				} else {
					$rcount = 0;
					
					$return = array();
					
					do {
						$crace = array_rand($races);
						
						$mpop = mt_rand(1, $races[$crace] - $return['Addpop'][$crace]);
						
						if($popleft < $mpop){
							$mpop = $popleft;
						}
						
						$rcount += $mpop;
						
						$popleft = $count - $rcount;
						
						$return['Addpop'][$crace] += $mpop;
						
						if($races[$crace] == $return['Addpop'][$crace]){
							
							unset($races[$crace]);
							
						}
						
						
					} while ($rcount < $count);
					
					$return['Addpop']['GP'] = floor($this->theCity->GetGPLimit() * ($hrand/100));
					
					return($return);
				}
				
			} elseif ($type == 'Future') {
				$return['Addpop'] = $this->theCity->GenerateRacialDemographic($this->terrain, $this->climate, $count, $race, true, $military);
			} else {
				$return['Addpop'] = $this->theCity->GenerateRacialDemographic($this->terrain, $this->climate, $count, $race, false, $military);
			}
		//	echo 'Add Pop ' . $type . ', Count: ' . $count . ', Percent: ' . $data['Strength'] . ', GP: ' . $return['GP'] . '<br />';
			
			return $return;
			
		}
		private function RemovePop($type, $data){ //echo 'six'; 
		////////////////////////////////////////////////////////////////////////////////////////////
		// THIS NEEDS TO BE COMPLETED AND TESTED
		////////////////////////////////////////////////////////////////////////////////////////////
		
			if($data['Race'] == 1){
				// single race 
				$race = true;
			} else {
				// multi race
				$race = false;
			}
			
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			
			
			$count = ceil($this->theCity->GetPopulation() * ($random / 100) * (($this->dangerlevel['Number'] + 1) / 10));
			
		//	echo '|||||||||||||' . $this->dangerlevel['Number'] . ' ' . $count . '|||||||||||||||||<br />';
			
//			echo '<h3>' . $type . '</h3>';
			
//			echo 'Race: ' . $race . ' Random: ' . $random . ' Count: ' . $count . '<br />';
			
			if ($type != 'Historical') {
				
				$races = $this->theCity->GetRaces();
				
				$rcount = -1;
				$rpick = '';
				
				if($race){
				
//					echo '<h4>Single Remove</h4>';
					
//					var_dump($races);
					
					foreach($races as $k => $c){
						if($c >= $rcount && $c <= $count){
							$rpick = $k;
							$rcount = $c;
						}
					}
					
					if ($type != 'Future') {
						$this->theCity->RemoveRaces($rcount, array($rpick => $rcount), 'Current');
					}
					
					//var_dump(array($rpick => $rcount));
					//echo '<br />';
					$return['PopLoss'] = array($rpick => $rcount);
					
				//	var_dump($return);
					
				} else {
					$rcount = 0;
					
					$return = array();
					
					do {
						$crace = array_rand($races);
						
						$mpop = mt_rand(1, $races[$crace] - $return['PopLoss'][$crace]);
						
						if($popleft < $mpop){
							$mpop = $popleft;
						}
						
						if($crace != ''){
							$rcount += $mpop;
							$popleft = $count - $rcount;
							$return['PopLoss'][$crace] += $mpop;
						}
						
						if($races[$crace] == $return['PopLoss'][$crace]){
							
							unset($races[$crace]);
							
						}
						
						
					} while ($rcount < $count);
					
					
					if ($type != 'Future') {
						$this->theCity->RemoveRaces($rcount, $return['PopLoss'], 'Current');
					} 
					
					//$return['GP'] = floor($this->theCity->GetGPLimit() * ($hrand/100));
//					echo '<h4>Multi Remove</h4>';
//					var_dump($return);
//					echo '<br />';
					
				}
				
			} else {
				
			//	echo '<h4>Historical Remove</h4>';
				
				if($count > 0){
					$return['PopLoss'] = $this->theCity->GenerateRacialDemographic($this->terrain, $this->climate, $count, $race, true);
					$this->theCity->RemoveRaces($count, array(), 'Historical');
				} else {
					$return['PopLoss'] = array('' => 0);
				}
				// add to max
				
//				var_dump($return);
//				echo '<br />';
				
				
			} 
			
//			echo 'Remove Pop ' . $type . ', Count: ' . $count . ', Percent: ' . $data['Strength'] . ', GP: ' . $return['GP'] . '<br />';
			
			$return['losstype'] = $data['Type'];
			
			return $return;
			
			
		}	
		private function ReduceSupply($type, $data){ //echo 'seven'; 
			////////////////////////////////////////////////////////////////////////////////////////////
		//	echo 'Negative <br />';
		
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			$neg = $random * -1;
		
			$this->setSupplyLevel($neg);
		
			return array('Supply' => $neg);
		
		}
		private function AddSupply($type, $data){ //echo 'eight'; 
			////////////////////////////////////////////////////////////////////////////////////////////
		
			// 'Strength' => $this->EventSeverity,
			// 'Optional' => true
			/*
			Supply is an abstract concept, but this value should be somewhat controllable. 
			since supply is between -100 and 100, we need to find a way to add that value without going over 100. 
			Can we do a degrading system? 
		
			*/
			//echo 'Positive <br />';
			
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			$this->setSupplyLevel($random);
		
			return array('Supply' => $random);
		
		}
		private function ReduceWealth($type, $data){ //echo 'nine'; 
		////////////////////////////////////////////////////////////////////////////////////////////
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			$GP = $this->theCity->GetGPLimit() - floor($this->theCity->GetGPLimit() * ($random / 100));
			
			//echo 'Reduce Wealth: ' . $GP . '<br />';
			
			if($GP != 0){
				$this->theCity->SetGPLimit($GP);
			}
			
			return array('ReduceWealth' => $random);
		
		}
		private function AddWealth($type, $data){ //echo 'ten'; 
		////////////////////////////////////////////////////////////////////////////////////////////
			
			if($data['Optional']){
				$random = mt_rand($data['Strength'] * -1, $data['Strength']);
				if($random < 0){
					$random = 0;
				}
			} else { 
				$random = $data['Strength'];
			} 
			
			// for this code, we'll simply add by basic percentage - this should be sufficient.
			
			$GP = ceil($this->theCity->GetGPLimit() * ((100 + $random) / 100));
			
			//echo 'Add Wealth: ' . $GP . '<br />';
			
			if($GP != 0){
				$this->theCity->SetGPLimit($GP);
			}
			
			return array('AddWealth' => $random);
			
		}	
		private function DestroyBuildings($type, $data){ 
		////////////////////////////////////////////////////////////////////////////////////////////
		
			if($data['Optional']){
					$random = mt_rand($data['Strength'] * -1, $data['Strength']);
					if($random < 0){
						$random = 0;
					}
			} else { 
				$random = $data['Strength'];
			} 
		
			// historical add percentage to buildings to be generated.
			// recent add percentage to buildings to be included.
			// if population is added, remove the added buildings from the existing destroyed.
			// current destroy percentage of buildings in current town
			// future mark x buildings for destruction
			
			if($type == 'Historical' || $type == 'Recent'){
				
				$this->buildings['Ruins']['Old'] += $random;
			
			} elseif($type == 'Current'){
			
				$this->buildings['Ruins']['Current'] += round((100 - $this->buildings['Ruins']['Current']) * ( $random / 100 ));
			
			} else {
			
				$this->buildings['Ruins']['Future'] += round((100 - $this->buildings['Ruins']['Future']) * ( $random / 100 ));
			
			}
			
			//echo 'Destroy: ' . $random . '% of the buildings in ' . $type . '<br />';
			return array('Buildings' => $random);
		
		}
		private function AddPowerCenter($type, $data){ //echo 'twelve'; 
			
			// 'Strength' => $this->EventSeverity,
			// 'Type' => 'Good'
			
			$stats = $this->theCity->AddPowerCenter($type, $data['Type'], $data['Strength'], 'Event');
			
			////////////////////////////////////////////////////////////////////////////////////////////
			return $stats;
		
		}
		private function RemovePowerCenter($type, $data){ //echo 'thirteen'; 
		////////////////////////////////////////////////////////////////////////////////////////////
			//echo 'remove ' . $type . '<br />';
			
			
			if($type == 'Historical'){
			//	echo 'remove ' . $type . '<br />';
			} elseif ($type == 'Future'){
			//	echo 'remove ' . $type . '<br />';
			} else {
			//	echo 'remove ' . $type . '<br />';
				$stats = $this->theCity->RemovePowerCenter($data['Type'], $data['Strength'], 'Event');
			}
			return $stats;
		}
		
		private function GenerateClimate($climate = false){
			// this will generate the climate unless the climate is pregenerated. 
			if($climate == false){
				
				$a = mt_rand(1,20);
				
				if($a <= 1){
					$this->climate = 'Freezing';
				} elseif($a <= 6){
					$this->climate = 'Cold';
				} elseif($a <= 14){
					$this->climate = 'Temperate';
				} elseif($a <= 19){
					$this->climate = 'Warm';
				} else {
					$this->climate = 'Hot';
				}
				
			} else {
				$this->climate = $climate;
			}
		}
		
		private function GenerateTerrain($terrain = false){
			// this generates the terrain base unless pregenerated
			if($terrain == false){
				$options = array(
					'Aquatic',
					'Coastal',
					'Desert',
					'Plains',
					'Forest',
					'Hills',
					'Mountains',
					'Marsh',
					'Underground');
				$this->terrain = $options[mt_rand(0,7)];
			} else {
				$this->terrain = $terrain;
			}
		}
		
		public function GetClimate(){
			return $this->climate;
		}
		public function GetTerrain(){
			return $this->terrain;
		}
		public function GetDangerLevel(){
			return $this->dangerlevel['Name'];
		}
		public function GetTimeLineOverview(){
			
			$output = '<h2>Events</h2>';
			
			foreach($this->timeline as $key => $time){
				
				$output .= '<h4>' . $key . '</h4>';
				
				foreach($time as $k){
					
					$output .= $k->GetOverview() . '<br />';
				
				}
				
			}
			
			return $output;
			
		}
		public function GetThreatOverview(){
			$output = '<h4> Threats </h4>';
		
			foreach($this->threats as $key => $threat){
				$output .= $key . ': ' . $threat->GetThreatOverview();
			}
			
			return $output;
		}
		public function GetSupplyLevel() {
			return $this->supplylevel;
		}
		public function GetCurrentYear() {
			return $this->year;
		}
		public function GetTownStats(){
			/*** THIS IS A TEMP ***/
			
			$pop = $this->theCity->GetPopulation(true);
			
			$output = '<h3>Population Stats</h3>
			<strong>Maximum: ' . $pop['Max'] . '</strong><br />
		 <strong>Population: ' . $pop['Pop'] . '</strong><br />';
			
			
			
			
			$races = $this->theCity->GetTownRaces();
			
			foreach($races as $key => $var){
				$output .= $key . ': ' . $var . '<br />';
			}
			
			
			
			$output .= '<br />GP Limit: ' . $this->theCity->GetGPLimit() . '<br />';
			
			$output .= '<h3>Power Centers</h3>';
			
			$Powers = $this->theCity->GetPowerCenterTypes();
			
			foreach($Powers as $p){
				
				$output .= $p . '<br />';
				
			}
			
			
			$output .= '<h3>Classes</h3>';
			
			$auth = $this->theCity->GetTownSoldiers();
			
			$output .= 'Soldiers: ' . $auth['Soldiers'] . ', Conscripts: ' . $auth['Conscripts'] . '<br />';
			
			$classes = $this->theCity->GetClassBreakdowns();
			
			foreach($classes as $class => $val){
				$output .= $class . ': ';
				foreach($val as $index => $v){
					$output .= 'Level ' . $index . ': ' . $v . ' | ';
				}
				$output .= '<br />';
			}
			
			
			Return $output;
		}
		public function GetBuildings(){
			// we don't have this information yet, but we'll output the destroyed percentages.
			$output = '<h3> Buildings </h3>';
			
			$output .= 'Old Ruins ' . $this->buildings['Ruins']['Old'] . '%<br />';
			$output .= 'Current Ruins ' . $this->buildings['Ruins']['Current'] . '%<br />';
			$output .= 'To Be Destroyed ' . $this->buildings['Ruins']['Future'] . '%<br /><br />';
			
			return $output;
		}
		
		
	}

?>
<h1>City Generator 1.0</h1>
<p> Everything below here is generated content.</p>
<? 
//	$character = new CharacterGenerator(array(),array(),'L','G');

//	echo'<br />';
	
	$City = new CityGenerator();

	echo 'Climate/Terrain: ' . $City->GetClimate() . ', ' . $City->GetTerrain() . '</br >';
	echo 'Current Year: ' . $City->GetCurrentYear() . '<br />';
	echo 'Danger Level: ' . $City->GetDangerLevel() . '<br />';
	echo 'Supply Level: ' . $City->GetSupplyLevel() . '<br />';
	echo 'Town Seed Number: ' . $townseed . '<br />'; 
	echo $City->GetBuildings();
	echo $City->GetTownStats();
	echo $City->GetTimeLineOverview();
	echo $City->GetThreatOverview();
	
	
?>

