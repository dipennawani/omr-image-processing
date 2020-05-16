<?php
class clsOmr
{
	
	public $grayValue;
	public $optionGrayValues = array();
	public $grayValueForCorners = 180;
	public $scanner;
	
	public $scannerWiseSettings = array(
									'HP'=>array('intAvgGrayValueForRollNoAndExamCode'=>210),
									'FJ'=>array('intAvgGrayValueForRollNoAndExamCode'=>150)
								  );
	
	public $totalQue;
	public $totalOpt;
	public $questionAnswerTemplate;
	public $rollNoTemplate;
	public $examCodeTemplate;
	public $dpi;
	public $initialScaleFactorX;
	public $initialScaleFactorY;
	public $width;
	public $height;
	public $scaleFactorY;
	public $scaleFactorX;
	
	public $im;
	public $answers;
	public $examCode;
	public $rollNo;
	
	public $topLeft;
	public $topRight;
	public $bottomRight;
	public $tiltAngle;
	const START_X_100DPI = 244.5;		
	
	const RADIUS_TO_CHECK_FOR_OPTIONS_100DPI = 4;
	const BLACK_DOTS_COUNTER_TO_CHECK_FOR_OPTIONS_100DPI = 40;
	
	const ROLLNO_START_X_100DPI = 32.5;
	const ROLLNO_START_Y_100DPI = 76.5;
	
	const OPT_DIFF_X_100DPI = 32.3;
	
	const TEMPLATE_HEIGHT_100DPI = 535;
	const TEMPLATE_WIDTH_100DPI = 773;
	
	const START_Y_100DPI = 177;
	const EXAMCODE_START_X_100DPI = 32.5;
	const EXAMCODE_START_Y_100DPI = 331.5; 
	
	const EXAMCODE_DIFF_X_100DPI = 19.5;
	const EXAMCODE_DIFF_Y_100DPI = 19.4;
	
	const ROLLNO_DIFF_X_100DPI = 19.5;
	const ROLLNO_DIFF_Y_100DPI = 19.7;
	
	const QUE_DIFF_Y_100DPI = 24.5;
	
	const SEC3_DIFF_X_100DPI = 183;
	const SEC5_DIFF_X_100DPI = 365.3;
	
	const VERTICAL_LINE_HEIGHT_100DPI = 6;
	const HORIZONTAL_LINE_WIDTH_100DPI = 6;
	const TOTAL_PIXELS_INLINE_100DPI = 4;
	
	
	function clsOmr(){		
		//Set scannerwise settings by detecting the scanner...		
		$this->scanner = 'HP';		
	}
	
	function createImage($path){
		$this->im = imagecreatefromjpeg($path);
	}
	
	function getDPI(){
		
		$this->width = imagesx($this->im);
		$this->height = imagesy($this->im);
		
		
		if($this->width > 2400){
			$this->dpi = 300;
		}
		elseif($this->width > 1600){
			$this->dpi = 200;
		}
		elseif($this->width > 1200){
			$this->dpi = 150;
		}
		else{
			$this->dpi = 100;
		}		
		
		$this->initialScaleFactorX = ($this->width/810);
		$this->initialScaleFactorY = ($this->height/583);
	}
		
	function getTiltAngle(){
		
		$this->topLeft['x'] = null;
		$this->topLeft['y'] = null;
		$isTopLeft = false;
		
		//To get the top left corner.....
		for($y = 5;$y<$this->height-5;$y++){
			$isHorizontalLinePresent = false;
			$isVerticalLinePresent = false;
			
			for($x = 10;$x<($this->width/4);$x++){
				
				$isBlack = $this->isBlack($x, $y);

				if($isBlack){
					
					$isTopLeft = $this->checkForTopLeft($x,$y);
		
					if($isTopLeft){
		
						$this->topLeft['x'] = $x;
						$this->topLeft['y'] = $y;
						break;
					}
				}
			}
			
			if($isTopLeft){
				break;
			}
		}		
		
		$this->topRight['x'] = Null;
		$this->topRight['y'] = Null;
		$isTopRight = false;
		
		//To get the top right corner.....
		for($y = 5;$y<($this->height-5); $y++){
	
			for($x = $this->width-10;$x>(3*($this->width)/4); $x--){
				
				$isBlack = $this->isBlack($x, $y);

				if($isBlack){
					
					$isTopRight = $this->checkForTopLeft($x,$y);
					if($isTopRight){
						$this->topRight['x'] = $x;
						$this->topRight['y'] = $y;
						break;
					}
				}
			}
			
			if($isTopRight){
				break;
			}
		}

		$this->bottomRight['x'] = Null;
		$this->bottomRight['y'] = Null;
		$isBottomRight = false;
		for($y = $this->height-10;$y>10;$y--){
			for($x = $this->width-10;$x>(3*($this->width)/4);$x--){
				$isBlack = $this->isBlack($x, $y);
				
				if($isBlack){
					$isBottomRight = $this->checkForBottomRight($x,$y);
					
					if($isBottomRight){
						$this->bottomRight['x'] = $x;
						$this->bottomRight['y'] = $y;
						break;
					}
				}
			}
			
			if($isBottomRight){
				break;
			}
		}
		
		//To find the final tilt angle....
		$opp = $this->topRight['y'] - $this->topLeft['y'];
		$adj = $this->topRight['x'] - $this->topLeft['x'];
		
		$currentAngle = rad2deg(atan(($opp/$adj)));
		
		$this->tiltAngle = $currentAngle;
		$this->rotateImage($this->tiltAngle);
		$this->resetToOriginalPoints();
		$this->cropImage();
		
		$this->getScaleFactor();
		
	}	
	
	function getScaleFactor(){
	
		$this->scaleFactorY = (($this->height*100)/(self::TEMPLATE_HEIGHT_100DPI*$this->dpi));
		$this->scaleFactorX = (($this->width*100)/(self::TEMPLATE_WIDTH_100DPI*$this->dpi));
	
	}
	
	function resetToOriginalPoints(){		
		
		$this->topLeft['x'] = null;
		$this->topLeft['y'] = null;
		$isTopLeft = false;

		//To get the top left corner.....
		for($y = 5;$y<$this->height-5;$y++){
			$isHorizontalLinePresent = false;
			$isVerticalLinePresent = false;
			
			for($x = 10;$x<($this->width/4);$x++){
				
				$isBlack = $this->isBlack($x, $y);

				if($isBlack){
					
					$isTopLeft = $this->checkForTopLeft($x,$y);
					if($isTopLeft){
						$this->topLeft['x'] = $x;
						$this->topLeft['y'] = $y;
						break;
					}
				}
			}
			
			if($isTopLeft){
				break;
			}
		}
		
		$this->bottomRight['x'] = Null;
		$this->bottomRight['y'] = Null;
		$isBottomRight = false;
		for($y = $this->height-10;$y>10;$y--){
			for($x = $this->width-10;$x>(3*($this->width)/4);$x--){
				$isBlack = $this->isBlack($x, $y);
				
				if($isBlack){
					$isBottomRight = $this->checkForBottomRight($x,$y);
					
					if($isBottomRight){
						$this->bottomRight['x'] = $x;
						$this->bottomRight['y'] = $y;
						break;
					}
				}
			}
			
			if($isBottomRight){
				break;
			}
		}		
	}
	
	function rotateImage($angle){
		
		$transColor = imagecolorallocate($this->im, 255, 255, 255);
		$this->im = imagerotate($this->im, $angle, $transColor);
		
		$this->width = imagesx($this->im);
		$this->height = imagesy($this->im);
	}
		
	function cropImage(){
				
		$this->height = $this->bottomRight['y'] - $this->topLeft['y'];
		$this->width = $this->bottomRight['x'] - $this->topLeft['x'];
		
		$oldWidth = imagesx($this->im);
		$oldHeight = imagesy($this->im);

		$newimage  = imagecreatetruecolor( $this->width, $this->height );
				
		imagecopyresampled($newimage, $this->im, 0, 0, $this->topLeft['x'], $this->topLeft['y'],$this->width, $this->height,$this->width, $this->height);
		$this->im = $newimage;
		
		unset($newimage);
	}
	
	function checkForTopLeft($x,$y){
		
		$pointsToCheck = array();
		
		$radiusToCheckX = (int)(($this->initialScaleFactorX)*(4));//7;
		$radiusToCheckY = (int)(($this->initialScaleFactorY)*(4));//7;
		
		for($i=-$radiusToCheckX;$i<=$radiusToCheckX;$i++){

			for($j=-$radiusToCheckY;$j<=$radiusToCheckY;$j++){

				if(!$this->isBlack($x+$i,$y+$j,$this->grayValueForCorners)){
					return false;
				}				
			}
		}
		
		$diameterToCheckX = ceil(($this->initialScaleFactorX)*(7));
		$diameterToCheckY = ceil(($this->initialScaleFactorY)*(7));
				
		if($this->isBlack($x+$diameterToCheckX+1,$y,$this->grayValueForCorners)){
			return false;
		}
		
		if($this->isBlack($x-$diameterToCheckX-1,$y,$this->grayValueForCorners)){		
			return false;
		}
	
		if($this->isBlack($x,$y+$diameterToCheckY+1,$this->grayValueForCorners)){
			return false;
		}
		
		if($this->isBlack($x,$y-$diameterToCheckY-1,$this->grayValueForCorners)){
			return false;
		}
		
		if($x+$diameterToCheckX<0 || $y+$diameterToCheckY<0 || $x-$diameterToCheckX> $this->width || $y-$diameterToCheckY > $this->height ||
			$x-$diameterToCheckX<0 || $y-$diameterToCheckY<0 || $x+$diameterToCheckX> $this->width || $y+$diameterToCheckY > $this->height
			){
			return false;
		}		
		return true;
		
	}
	
	function checkForBottomRight($x,$y,$debug=0){
		
		$pointsToCheck = array();
				
		$radiusToCheckX = (int)(($this->initialScaleFactorX)*(4));//7;
		$radiusToCheckY = (int)(($this->initialScaleFactorY)*(4));//7;
		
		for($i=-$radiusToCheckX;$i<=$radiusToCheckX;$i++){

			for($j=-$radiusToCheckY;$j<=$radiusToCheckY;$j++){

				if(!$this->isBlack($x+$i,$y+$j,$this->grayValueForCorners)){
					return false;
				}	
			}
		}
		
		$diameterToCheckX = ceil(($this->initialScaleFactorX)*(7));
		$diameterToCheckY = ceil(($this->initialScaleFactorY)*(7));
		
		if($this->isBlack($x+$diameterToCheckX+1,$y,$this->grayValueForCorners)){
			return false;
		}
	
		if($this->isBlack($x-$diameterToCheckX-1,$y,$this->grayValueForCorners)){			
			return false;
		}
	
		if($this->isBlack($x,$y+$diameterToCheckY+1,$this->grayValueForCorners)){			
			return false;
		}
		
		if($this->isBlack($x,$y-$diameterToCheckY-1,$this->grayValueForCorners)){			
			return false;
		}
		
		if($x+$diameterToCheckX<0 || $y+$diameterToCheckY<0 || $x-$diameterToCheckX> $this->width || $y-$diameterToCheckY > $this->height ||
			$x-$diameterToCheckX<0 || $y-$diameterToCheckY<0 || $x+$diameterToCheckX> $this->width || $y+$diameterToCheckY > $this->height
			){
			return false;
		}		
		return true;		
	}	
	
	function createTemplate(){
		
		$this->createExamCodeTemplate();
		$this->createRollNoTemplate();
		$this->createQuestionTemplate();
	}
	
	function setNoOfQuestions($noOfQuestions){
		$this->totalQue = $noOfQuestions;
	}
	
	function createQuestionTemplate(){
		
		$this->totalOpt = 4;
		$this->questionAnswerTemplate = array();
		
		$startX = (($this->scaleFactorX)*(self::START_X_100DPI)*($this->dpi)/100);
		$startY = (($this->scaleFactorY)*(self::START_Y_100DPI)*($this->dpi)/100);
		
		$xVal = null;
		$yVal = null;
		
		$optDiffX = (($this->scaleFactorX)*(self::OPT_DIFF_X_100DPI)*($this->dpi)/100);
		$queDiffY = (($this->scaleFactorY)*(self::QUE_DIFF_Y_100DPI)*($this->dpi)/100);
		
		$sec3DiffX = (($this->scaleFactorX)*(self::SEC3_DIFF_X_100DPI)*($this->dpi)/100);
		$sec5DiffX = (($this->scaleFactorX)*(self::SEC5_DIFF_X_100DPI)*($this->dpi)/100);
		
		for ($i = 1; $i < $this->totalQue+1; $i++) {
			
			if($i<15){
				$xVal = $startX;
			}
			elseif($i<29){
				$xVal = $startX + $sec3DiffX;
			}
			else{
				$xVal = $startX + $sec5DiffX;
			}
			
			if($i==1 || $i==15 || $i==29){
				$yVal = $startY;
			}
			
			for ($j = 1; $j < $this->totalOpt+1; $j++) {
				$this->questionAnswerTemplate[$i][$j]['x'] = $xVal;
				$this->questionAnswerTemplate[$i][$j]['y'] = $yVal;
				$xVal = $xVal + $optDiffX;
			}
			
			$yVal = $yVal + $queDiffY;
		}
	}	
	
	function createExamCodeTemplate(){

		$examCodeStartX = (($this->scaleFactorX)*(self::EXAMCODE_START_X_100DPI)*($this->dpi)/100);
		$examCodeStartY = (($this->scaleFactorY)*(self::EXAMCODE_START_Y_100DPI)*($this->dpi)/100);
		
		$examCodeDiffX = (($this->scaleFactorX)*(self::EXAMCODE_DIFF_X_100DPI)*($this->dpi)/100);
		$examCodeDiffY = (($this->scaleFactorY)*(self::EXAMCODE_DIFF_Y_100DPI)*($this->dpi)/100);
		
		$xVal = $examCodeStartX;
		for($i=0;$i<7;$i++){
			$yVal = $examCodeStartY;	
			for($j=0;$j<=9;$j++){
				
				$this->examCodeTemplate[$i][$j]['x'] = $xVal;
				$this->examCodeTemplate[$i][$j]['y'] = $yVal;
				$yVal = $yVal + $examCodeDiffY;
			}

			$xVal = $xVal + $examCodeDiffX;
		}
	}
	
	function createRollNoTemplate(){

		$rollNoStartX = (($this->scaleFactorX)*(self::ROLLNO_START_X_100DPI)*($this->dpi)/100);
		$rollNoStartY = (($this->scaleFactorY)*(self::ROLLNO_START_Y_100DPI)*($this->dpi)/100);
		
		$rollNoDiffX = (($this->scaleFactorX)*(self::ROLLNO_DIFF_X_100DPI)*($this->dpi)/100);
		$rollNoDiffY = (($this->scaleFactorY)*(self::ROLLNO_DIFF_Y_100DPI)*($this->dpi)/100);
		
		$xVal = $rollNoStartX;
		for($i=0;$i<7;$i++){
			$yVal = $rollNoStartY;	
			for($j=0;$j<=9;$j++){
				
				$this->rollNoTemplate[$i][$j]['x'] = $xVal;
				$this->rollNoTemplate[$i][$j]['y'] = $yVal;
				$yVal = $yVal + $rollNoDiffY;
			}

			$xVal = $xVal + $rollNoDiffX;
		}
	}
	
	function extractExamCode(){
		
		for ($i = 0; $i < 7 ; $i++) {
			
			$intBestGrayScaleSelected=255;
			$intSelectedOption = 0;
			
			for ($j = 0; $j <=9; $j++) {
				$this->optionGrayValues = array();
				$isBlack = $this->isOptionBlack($this->examCodeTemplate[$i][$j]['x'], $this->examCodeTemplate[$i][$j]['y']);
				
				if($isBlack){
					$grayAvg = array_sum($this->optionGrayValues)/count($this->optionGrayValues);
					if($grayAvg>$this->scannerWiseSettings[$this->scanner]['intAvgGrayValueForRollNoAndExamCode'])continue; // max grayscale avg should be 150...for option to get selected
					
					if($grayAvg>=$intBestGrayScaleSelected)continue; // current avg should be less than selected gray scale for all the options....
					else{
						//difference between two options should be atleast 25 to select...So making this examcode invalid to give the error...
						if($intBestGrayScaleSelected - $grayAvg < 25){
							$this->examCode = 0;
							return;
						}
						
						$intSelectedOption = $j;
						$intBestGrayScaleSelected = $grayAvg;
					}
				}
			}			
			$this->examCode[$i] = $intSelectedOption;
		}
		if(!empty($this->examCode)){
			$this->examCode = (int)implode('',$this->examCode);
		}
		else{
			$this->examCode = 0;
		}
	}
	
	function extractRollNo(){
		
		for ($i = 0; $i < 7 ; $i++) {
			
			$intBestGrayScaleSelected=255;
			$intSelectedOption = -1;
			for ($j = 0; $j <=9; $j++) {
				$this->optionGrayValues = array();
				$isBlack = $this->isOptionBlack($this->rollNoTemplate[$i][$j]['x'], $this->rollNoTemplate[$i][$j]['y']);
				
				if($isBlack){
					$grayAvg = array_sum($this->optionGrayValues)/count($this->optionGrayValues);
					if($grayAvg>$this->scannerWiseSettings[$this->scanner]['intAvgGrayValueForRollNoAndExamCode'])continue; // max grayscale avg should be 150...for option to get selected
					
					if($grayAvg>=$intBestGrayScaleSelected)continue; // current avg should be less than selected gray scale for all the options....
					else{
						if($intBestGrayScaleSelected - $grayAvg < 25){//difference between two options should be atleast 25 to select...So making this as invalid roll no...						
							$this->rollNo = 0;
							return;						
						}
						
						$intSelectedOption = $j;
						$intBestGrayScaleSelected = $grayAvg;
					}
				}
			}
			if($intSelectedOption>=0){
				$this->rollNo[] = $intSelectedOption;
				
			}
		}
		
		if(!empty($this->rollNo)){
			$this->rollNo = (int)implode('',$this->rollNo);
		}
		else{
			$this->rollNo = 0;
		}
		
	}
	
	function extractData(){
		
		$this->extractRollNo();
		$this->extractExamCode();
		$this->extractAnswers();
		
		return array(
		
		'exam_code'=>$this->examCode,
		'roll_no'=>$this->rollNo,
		'answers'=>$this->answers
		
		);	
	}
	
	function extractAnswers(){
		
		for ($i = 1; $i < $this->totalQue+1; $i++) {
			$this->answers[$i] = '*';//* - no answer, # - more than 1 answer...
			$selectedOptionData['selected'] = '*';
			$selectedOptionData['mean'] = 0;
			
			for ($j = 1; $j < $this->totalOpt+1; $j++) {
				$isBlack = $this->isOptionBlack($this->questionAnswerTemplate[$i][$j]['x'], $this->questionAnswerTemplate[$i][$j]['y']);
				
				if($isBlack){

					if($selectedOptionData['selected'] == '*'){
						$selectedOptionData['mean'] = array_sum($this->optionGrayValues)/count($this->optionGrayValues);
						$selectedOptionData['selected'] = $j;
						$this->answers[$i] = $j;
						
					}
					else{
						
						$currentOptionMean = array_sum($this->optionGrayValues)/count($this->optionGrayValues);
						
						$diffInMean = $currentOptionMean - $selectedOptionData['mean'];
						
						//keep the previous option selected if difference in mean is more than 25....
						if($diffInMean > 25){
							continue;
						
						}
						//select current option if difference in mean is less than -25..
						elseif($diffInMean < -25 ){
							
							$selectedOptionData['mean'] = array_sum($this->optionGrayValues)/count($this->optionGrayValues);
							$selectedOptionData['selected'] = $j;
							$this->answers[$i] = $j;
							
						}
						//there is no significance difference so marking the answer as invalid (#)
						else{
							
							$selectedOptionData['selected'] = '#';
							$selectedOptionData['mean'] = 0;
							$this->answers[$i] = '#';
							break;
							
						}
						
						
						
						
					}
				}
			}
		}
	}
	
	function showImage(){
				
		// start buffering
		ob_start();
		imagejpeg($this->im);
		$contents =  ob_get_contents();
		ob_end_clean();
		
		echo '<img height="250" width="250" src = "data:image/jpeg;base64,'.base64_encode($contents).'"/>';
		imagedestroy($this->im);
	}
	
	function showImageContent(){
		
		header("Content-Type: image/jpeg");
		header('Cache-Control: max-age=86400, public');
		header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60*24))); // 24 hour
		imagejpeg($this->im);
		imagedestroy($this->im);
		
	}
	
	function isOptionBlack($x,$y){
		
		$pointsToCheck = array();
		
		$radiusToCheckX = (int)(($this->scaleFactorX)*(self::RADIUS_TO_CHECK_FOR_OPTIONS_100DPI)*(($this->dpi)/100));
		$radiusToCheckY = (int)(($this->scaleFactorY)*(self::RADIUS_TO_CHECK_FOR_OPTIONS_100DPI)*(($this->dpi)/100));
		
		
		for($i=-$radiusToCheckX;$i<=$radiusToCheckX;$i++){

			for($j=-$radiusToCheckY;$j<=$radiusToCheckY;$j++){
				
				$temp['x'] = $x+$i;
				$temp['y'] = $y+$j;
				
				$pointsToCheck[] = $temp;				
			}
		}		
		
		$countOfBlack = 0;
		$this->optionGrayValues = array();

		foreach($pointsToCheck as $point){
			
			if($this->isBlack($point['x'],$point['y'])){
				$countOfBlack++;
			}
			$this->optionGrayValues[] = $this->grayValue;
		}
		
		$blackDotsToCheck = ((2*$radiusToCheckX+1)*(2*$radiusToCheckY+1)*(0.60));
		
		
		if($countOfBlack>$blackDotsToCheck){
			return true;
		}
		else{
			return false;
		}
	}
	
	function getGrayOfPixel($x,$y){
		
		if($x<0 || $y<0 || $x >= $this->width || $y >= $this->height){
			return 0;
		}
		
		$rgb = imagecolorat($this->im, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		
		$gray = ($r + $g + $b) / 3;
		
		return $gray;		
	}
	function isBlack($x,$y,$defaultGray = 0){
		
		if($x<0 || $y<0 || $x >= $this->width || $y >= $this->height){
			return false;
		}
		
		$rgb = imagecolorat($this->im, (int)$x, (int)$y);
		
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		
		$gray = ($r + $g + $b) / 3;
		
		$this->grayValue = $gray;
		
		if($defaultGray){
		
			if($gray <= $defaultGray){
				return true;
			}
			else{	
				return false;
			}		
		}
		
		if($gray > 225){
			return false;
		}

		return true;
	}
}
?>