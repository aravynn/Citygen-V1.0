<?php

/** 
 *
 * Power & Threat Generator.
 *
 *
 */
 
class PowerGenerator {
	
	private $PowerType;
	private $PowerName;
	private $GEAlignment;
	private $LCAlignment;
	private $PowerLevel;
	
	private $status;
	private $origin;
	
	function __construct($CityModifier, $PowerType=false, $PowerLevelSet=false, $status='Active', $alignment=false, $origin = 'Random', $population, $classes) {
		$this->SetPowerType($CityModifier, $PowerType, $population, $classes);
		
		if(!$alignment){
			$this->SetGEAlignment();
			$this->SetLCAlignment();
		} else {
			$this->GEAlignment = $alignment['GE'];
			$this->LCAlignment = $alignment['LC'];
		}
		
		//$this->SetPowerName();
		$this->SetPowerLevel($PowerLevelSet);

		$this->status = $status;
		$this->origin = $origin;
		//echo 'Origin: ' . $this->PowerOrigin();
	}
	
	public function SetStatus($status){
		// statuses - Active, Forming, Destroyed
		$this->status = $status;
	}
	
	public function PowerStatus(){
		return $this->status;
	}
	
	public function PowerOrigin(){
		return $this->origin;
	}
	
	private function SetPowerType($mod, $pow=false, $population, $classes){
		if($pow == false){
			$powercenter = mt_rand(1,20) + $mod;
		
			if($powercenter < 14){
				$this->PowerType = 'Conventional';
			} elseif($powercenter < 19){
				$this->PowerType = 'Nonstandard';
			} else {
				$this->PowerType = 'Magical';
			}
		} else {
			$this->PowerType = $pow;
		}
		
		$countarray = array(
			array('min' => 1, 'max' => 3),
			array('min' => 2, 'max' => 3),
			array('min' => 3, 'max' => 3),
			array('min' => 1, 'max' => 7),
			array('min' => 3, 'max' => 12),
			array('min' => 6, 'max' => 40),
			array('min' => 20, 'max' => 100),
			array('min' => 50, 'max' => 300)
			);
		
		$fn = 'Generate' . $this->PowerType;
		
		echo 'pop: ' . $population;
		
		for($i = 0; $i < 8; $i++){
			if($countarray[$i]['min'] > ($population / 10)){
				unset($countarray[$i]);
			}
		}
		
		$countset = array_rand($countarray);
		
		$powercount = mt_rand($countarray[$countset]['min'], $countarray[$countset]['max']);
		echo 'power count: ' . $powercount;
		//echo $fn;
		
		$this->$fn();
	}
	
	private function GenerateConventional(){

	}
	
	private function GenerateNonstandard(){
	//	echo 'Nonstandard';
	}
	
	private function GenerateMagical(){
	//	echo 'Magical';
	}
	
	private function GenerateMonstrous(){
	//	echo 'Monstrous';
		// get a monster/monsters. 
	}
	
	private function GenerateAuthority(){
	//	echo 'Authority';
		$HighAuthority = mt_rand(1,100);
		
		if($HighAuthority < 61){
			$this->PowerName = 'Highest Level Warrior';
		} elseif($HighAuthority < 81){
			$this->PowerName = '2nd Highest Level Fighter';
		} else {
			$this->PowerName = 'Highest Level Fighter';
		}
	}
	
	
	
	
	private function SetGEAlignment(){
		
		$a = mt_rand(1,100);
		
		if($a < 42){
			$this->GEAlignment = 'G';
		} elseif($a < 65){ 
			$this->GEAlignment = 'N';
		} else {
			$this->GEAlignment = 'E';
		}
	}
	
	private function SetPowerName(){
		if($this->PowerType == 'Authority'){
			$this->GenerateAuthority();
		}
	}
	
	private function SetPowerLevel($PowerLevel){
		
		
		//echo 'Set Power Level';
		
		if(!$PowerLevel){
			// set a random power level. 
			
			$pows = floor(100 * pow((mt_rand(1,1000000)/1000000), 2)) + 1;
		//	$pows = array('Support', 'Support', 'Support', 'Minor', 'Minor', 'Major');
		//	$this->PowerLevel = $pows[array_rand($pows)];
			$this->PowerLevel = $pows;
		
			
		//	echo "Power" . $pows;
		} elseif($PowerLevel == 'Authority'){
		
			$pows = mt_rand(1,70) + 30;
			$this->PowerLevel = $pows;
			
		} else {
		
		//echo $PowerLevel;
			
			$this->PowerLevel = $PowerLevel;
		}
	}
	
	private function SetLCAlignment(){
	
		$a = mt_rand(1,100);
		
		if($a < 81){
			$this->LCAlignment = 'L';
		} elseif($a < 95){ 
			$this->LCAlignment = 'N';
		} else {
			$this->LCAlignment = 'C';
		}
	}
	
	public function GetPowerType(){
		return $this->PowerType;
	}
	
	public function GetAlignment(){
		return $this->LCAlignment . $this->GEAlignment;
	}
	
	public function GetGEAlignment(){
		return $this->GEAlignment;
	}
	
	public function GetLCAlignment(){
		return $this->LCAlignment;
	}
	
	public function GetPowerName(){
		return $this->PowerName;
	}
	
	public function GetPowerLevel(){
		return $this->PowerLevel;
	}
}

?>


	