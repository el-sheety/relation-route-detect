<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Relations;
use App;
use Response;
use Session;
use Auth;
use DB;

use Exception;

use App\Http\Controllers\StatesController;


class RelationDetectionController extends Controller
{

    public function DetectionEngine(Request $request , $parent , $last , $current , $done_array = []) {
		$found = false;

        if(array_search($current, $done_array) === false) {

	    	$keeber_class = new $current();
	        $methods = get_class_methods($current);
	        $methods_key = array_search('__construct', $methods);
	        $relations = array_slice($methods,0, $methods_key);
	        
	        foreach($relations as $keeber_relation_value){
	        	//echo $current.' - '.$keeber_relation_value.'<br>';
	            if($keeber_relation_value == 'trashes') {
	                continue;
	            }
	            try {
		            if( !$keeber_class->$keeber_relation_value() || is_string($keeber_class->$keeber_relation_value()) || is_array($keeber_class->$keeber_relation_value() ) ) {
		                 continue;  
		            }
	            } catch(Exception $e) {
	            	continue;
	            }
	            $keeber_class_name = '';
	            try {
	            	$keeber_class_name =  get_class($keeber_class->$keeber_relation_value()->getRelated()); 
	            } catch(Exception $e) {
	            	continue;
	            }
		        if($keeber_class_name == $last){
		        	$found = true;
		        	return $done_array;
		        	//return true;
		        }
		        $done_array[] = $current;

	            $found = $this->DetectionEngine($request , $current , $last , $keeber_class_name , $done_array);
		        if($found != false){
		        	//print_r($found);
		        	return $found;
		        }
		        
	        }
	        if(!count($relations)) {
	        	return false;
	        }
	        
	    }
		        //echo "<br>-RetrN-<br>";

	    return $found;
    }


    public function startEngine(Request $request) {
   
        $first_point = $request->entity;
        $second_point = $request->colomn;
       $result = $this->DetectionEngine($request , $first_point , $second_point , $first_point);
      // print_r($result);
       //die();
       $relation_string = $first_point."::";
       $done_array = array();
       $count = 0;
       foreach ($result as $value) {
       	if($count != 0){
       		$value = str_replace("App", "", $value);
       	$value = preg_replace("/[^a-zA-Z]/", "", $value);
       	$value = strtolower($value);
       	}
       	
       	if(array_search($value , $done_array) === false){
       		
   			
   			if($count != 0){
   		$relation_string .= "whereHas('".$value ."' , function(\$q){ \$q->";
       		$done_array[] = $value;
   			}
       		$count++;
       	}
       	
       	

       	
       }
       $second_point = str_replace("App", "", $second_point);
       	$second_point = preg_replace("/[^a-zA-Z]/", "", $second_point);
       	$second_point = strtolower($second_point);
   		$relation_string .= "whereHas('".$second_point ."' );";
   		for($i = 0 ; $i < $count - 1 ; $i++){
   			$last = $count - 2;
   			if($i == $last){
   				$relation_string .= "})";
   			}else{
   			$relation_string .= "});";
   			}
   		}
   		$relation_string .= "->toSql();";
       //print_r($relation_string);
       //	die();
     //  $relation_string = $relation_string."get()";
       print_r($relation_string);

       ob_start();
        $relation1 = eval("echo $relation_string");
        $this_string = ob_get_contents();
        ob_end_flush();
        ob_clean();
       //echo "<br>";

$this_string = str_replace("and `trashes`.`trashable_type` = ?", "", $this_string);
        
  
      // $result = DB::select($this_string);
$result = json_encode($this_string); 

 
       return $result;
       
    }


}

