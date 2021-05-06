<?php

/** 
 *
 * Dice Roll Generator.
 *
 * floor($max * pow((mt_rand(1,1000000)/1000000), $power) + $min);
 */
 
function RollDice($die, $count = 1){
	$total = 0;
	for($i=0;$i<$count;$i++){
		$total += mt_rand(1,$die);
	}
	//if($count > 1){
	//	echo $count . '<br /><br />';
	//}
	
	return $total;
}

function RollStat($str=3){
	// roll generic stats. 
	$array = array();
	
	for($i=0;$i<$str;$i++){
		$array[$i] = RollDice(6,1);
	}
	
	rsort($array);
	$top3 = array_reverse(array_slice($array, 0, 3));
	
	return array_sum($top3);
}

?>