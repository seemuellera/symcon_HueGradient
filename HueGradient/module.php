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
		$this->RegisterVariableString("Gradient","Gradient");
		$this->RegisterVariableString("GradientHtml","GradientHtml","~HTMLBox");
		$this->RegisterVariableinteger("Step","Step");

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
		$form['actions'][] = Array("type" => "Button", "label" => "Start", "onClick" => 'HUEGRADIENT_Start($id,"0000ff",10);');
		$form['actions'][] = Array("type" => "Button", "label" => "Stop", "onClick" => 'HUEGRADIENT_Stop($id);');

		// Return the completed form
		return json_encode($form);

	}

	public function RefreshInformation() {

		if (GetValue($this->GetIDForIdent("Status") ) ) {
		
		
			$this->NextStep();
		}

	}

	public function Start(string $newColor, int $steps) {
	
		SetValue($this->GetIDForIdent("Status"), true );	

		$oldColor = $this->GetColor();
		$gradient = $this->GetGradient($oldColor, $newColor, $steps);

		$gradient_json = json_encode($gradient);
		SetValue($this->GetIDForIdent("Gradient"), $gradient_json);

		$gradient_html = "<table>";
		foreach ($gradient as $currentGradient) {
		
			$gradient_html = $gradient_html . '<tr><td bgcolor="#' . $currentGradient . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>'; 
		}
		$gradient_html = $gradient_html . "</table>";
		SetValue($this->GetIDForIdent("GradientHtml"), $gradient_html);

		SetValue($this->GetIDForIdent("Step"), 0);
	}

	public function NextStep() {
	
		$gradient_json = GetValue($this->GetIDForIdent("Gradient"));
		$step = GetValue($this->GetIDForIdent("Step"));

		$gradient = json_decode($gradient_json);

		$gradient_count = count($gradient);

		$stepNew = $step + 1;

		if ($stepNew >= $gradient_count) {
		
			$this->Stop();
		}
		else {
	
			$color = $gradient[$stepNew];
			HUE_SetColor($this->ReadPropertyInteger("TargetId"), hexdec($color) );
			SetValue($this->GetIDForIdent("Step"), $stepNew);
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

		$varIdColor = IPS_GetObjectIDByName("Color",$targetId);

		if (! $varIdColor) {
		
			$varIdColor = IPS_GetObjectIDByName("Farbe",$targetId);
		}

		if (! $varIdColor) {
		
			IPS_LogMessage($_IPS['SELF'],"HUEGRADIENT - Reading color not possible for device $targetId - could not find variable");

			return 2;
		}

		$color = GetValue($varIdColor);

		$color_hex = sprintf('%06s',dechex($color) );

		IPS_LogMessage($_IPS['SELF'], "HUEGRADIENT - Reading color for device $targetId: $color / $color_hex");

		return ($color_hex);
	}

	protected function GetGradient(string $colorOld, string $colorNew, int $steps) {

		IPS_LogMessage($_IPS['SELF'], "HUEGRADIENT - Starting transition from $colorOld to $colorNew in $steps steps");
	
		$r1=hexdec(substr($colorOld,0,2)); 
		$g1=hexdec(substr($colorOld,2,2)); 
		$b1=hexdec(substr($colorOld,4,2)); 

		$r2=hexdec(substr($colorNew,0,2)); 
		$g2=hexdec(substr($colorNew,2,2)); 
		$b2=hexdec(substr($colorNew,4,2)); 

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
			array_push($colors, $color); 
	    	} 

		array_push($colors, $colorNew); 
		  	
  		return $colors;

	}

	public function Stop() {
	
		SetValue($this->GetIDForIdent("Status"), false);
	}

    }
?>
