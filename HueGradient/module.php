<?php

    // Klassendefinition
    class HueGradient extends IPSModule {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            
		// Diese Zeile nicht löschen.
            	parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","HueGradient");
		$this->RegisterPropertyInteger("TargetId",1);
		$this->RegisterPropertyInteger("RefreshInterval",0);

		// Variables
		$this->RegisterVariableBoolean("Status","Status","~Switch");
		$this->RegisterVariableString("Gradient","");
		$this->RegisterVariableinteger("Step",0);

		// Default Actions
		$this->EnableAction("Status");

		// Timer
		$this->RegisterTimer("RefreshInformation", 0 , 'HUEGRADIENT_RefreshInformation($_IPS[\'TARGET\']);');

        }

	public function Destroy() {

		// Never delete this line
		parent::Destroy();
	}
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {

		
		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);
		

            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
        }


	public function GetConfigurationForm() {

        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
			"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "SelectObject", "name" => "TargetId", "caption" => "Target Object");
		

		// Add the buttons for the test center
		$form['actions'][] = Array("type" => "Button", "label" => "Run next cycle", "onClick" => 'HUEGRADIENT_RefreshInformation($id);');
		$form['actions'][] = Array("type" => "Button", "label" => "Start", "onClick" => 'HUEGRADIENT_Start($id,255,10);');
		$form['actions'][] = Array("type" => "Button", "label" => "Stop", "onClick" => 'HUEGRADIENT_Stop($id);');

		// Return the completed form
		return json_encode($form);

	}

	public function RefreshInformation() {

		if (GetValue($this->GetIDForIdent("Status") ) ) {
		
		
			$this->NextStep();
		}

	}

	public function Start($newColor, $steps) {
	
		SetValue($this->GetIDForIdent("Status"), true );	

		$oldColor = $this->getColor();
		$gradient = $this->GetGradient($oldColor, $newColor, $steps);

		$gradient_json = json_encode($gradient);
		SetValue($this->GetIDForIdent("Gradient"), $gradient_json);
	}

	public function NextStep() {
	
		$newDimValue = GetValue($this->GetIDForIdent("Intensity" ) ) - $this->ReadPropertyInteger("DimStep");		

		if ($newDimValue <= 0) {
		
			$this->SwitchOff();
		}
		else {
		
			$this->SetDim($newDimValue);
		}
	}

	protected function GetColor() {

		$targetId = $this->ReadPropertyInteger("TargetId");
		$targetDetails = IPS_GetInstance($targetId );
		$targetModuleName = $targetDetails['ModuleInfo']['ModuleName'];

		if (! $targetModuleName) {

			IPS_LogMessage($_IPS['SELF'],"HUEGRADIENT - Reading color not possible for device $targetId - module type could not be identified");
			return 2;
		}

		$varIdColor = IPS_GetObjectIDByName("Farbe",$targetId);

		if (! $varIdColor) {
		
			IPS_LogMessage($_IPS['SELF'],"HUEGRADIENT - Reading color not possible for device $targetId - could not find variable");

			return 2;
		}

		$color = GetValue($varIdColor);

		IPS_LogMessage($_IPS['SELF'], "HUEGRADIENT - Reading color for device $targetId: $color");

		return $color;
	}

	protected function GetGradient(int $colorOld, int $colorNew, int $steps) {
	
		$r1=hexdec(substr($colorOld,1,2)); 
		$g1=hexdec(substr($colorOld,3,2)); 
		$b1=hexdec(substr($colorOld,5,2)); 

		$r2=hexdec(substr($colorNew,1,2)); 
		$g2=hexdec(substr($colorNew,3,2)); 
		$b2=hexdec(substr($colorNew,5,2)); 

		$diff_r=$r2-$r1; 
		$diff_g=$g2-$g1; 
		$diff_b=$b2-$b1;
			  
		$colors = Array();  
		for ($i=0; $i<$steps; $i++) { 
			
			$factor=$i / $steps; 

		        $r=round($r1 + $diff_r * $factor); 
		        $g=round($g1 + $diff_g * $factor); 
		       	$b=round($b1 + $diff_b * $factor); 

		       	$color=sprintf("%02X",$r) . sprintf("%02X",$g) . sprintf("%02X",$b);
			array_push($colors, hexdec($color)); 
	    	} 

		array_push($colors, hexdec($colorNew)); 
		  	
  		return $colors;

	}

	public function Stop() {
	
		SetValue($this->GetIDForIdent("Status"), false);
	}

    }
?>
