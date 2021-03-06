<?php
include_once 'db_connect.php';
include_once 'functions.php';
include_once 'lpbhn.php'; 

sec_session_start();

/**
* If there is an error during the rotulation process,
* then the system rollbacks the database, takes user to index page and
* presents an error message
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function dbError($mysqli) {
	$mysqli->rollback();
	$mysqli->autocommit(TRUE);
	echo mysqli_error($mysqli);
	setAlert("Fatal error when trying to retrieve information");
	header('Location: ./index.php');
	exit();
}


/**
* Outputs a progress bar that indicates how much of the labeling
* process the tagger has completed
*/
function showProgressBar(){
	if((isset($_SESSION['curDocID'])) 
	&& (isset($_SESSION['minDocID'])) 
	&& (isset($_SESSION['maxDocID']))){
		
	$totalDocs 	= $_SESSION['maxDocID'] - $_SESSION['minDocID']+1;
	$completed 	= $_SESSION['curDocID'] - $_SESSION['minDocID'];
	$progress	= (int) ($completed * 100 / $totalDocs) ;
	
	echo 	"<div class='container' style='padding:0'>
				<div class = 'row'>
					<div class='progress col-xs-6 col-sm-4 col-centered' style='padding:0'>".
						"<div class='progress-bar' role='progressbar'".
							" aria-valuenow='" . $_SESSION['curDocID'] . "' " . 
							" aria-valuemin='" . $_SESSION['minDocID'] . "' " . 
							" aria-valuemax='" . $_SESSION['maxDocID'] . "' " . 
							" style='width:" .$progress. "%'>".
								$progress. "% complete". 
						'</div>'.
					'</div>'.
				'</div>'.
			'</div>';
	}	
}



/**
* Outputting to user (tagger) the label suggested by the system 
*
* @param  ($sugLabel) - label to be presented 
*/
function showSuggestedLabel($sugLabel){
	if(!empty($sugLabel)){
		
		$inputType = "radio";					//Radio Button for single label
		if($_SESSION['cur_lpMultilabel']) $inputType = "checkbox";		//Checkboxes for multilabel
		
		echo 
		'<tr>
			<td><strong>System\'s suggestion :</strong></td>
			<td><input type='. $inputType .' checked name="lpLabels[]" value="' . $sugLabel . '">&ensp;' . $sugLabel . '</td><br>
		</tr>';
		
	}	
}

/**
* After showing the suggested label, then the system 
* outputs to user (tagger) the remaining options (labels)
* Obs.: In test mode, it does not have a suggestion
*
* @param  ($labels) - array of labels to be presented 
*/
function showOtherLabels($labels){
	if(!empty($labels)){
		
		if($_SESSION['cur_lpAlgorithm'] == 'testMode') echo '<tr><td>Opções de Rótulo :</td><td>';
		else echo '<tr><td><strong>Other label options :</strong></td><td>';
		
		$inputType = "radio";					//Radio Button for single label
		if($_SESSION['cur_lpMultilabel']) $inputType = "checkbox";		//Checkboxes for multilabel
		
		foreach ($labels as $lbl ){
			echo '<input type='. $inputType .' name="lpLabels[]" value="' .$lbl . '">&ensp;' .$lbl . '<br>';		
		}
		echo '</td></tr>';
	}
}

function getAspects($mysqli){
	$prev_aspects = array();

    $query = "	SELECT aspect_tagger, aspect_doc, aspect_lp, aspect_type, aspect_aspect, aspect_polarity, aspect_start, aspect_end, aspect_number 
					FROM tbl_aspect 
					WHERE aspect_doc = ? AND aspect_tagger = ?"; 
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if($stmt->execute()){
		$result = $stmt->get_result();
		while($data = mysqli_fetch_row($result)){
			array_push($prev_aspects, $data);
		}
	}else{
		setAlert("Error retrieving information");
	}
	$stmt->close();
	
	return $prev_aspects;
}

/**
* Gets the items in the given position for each aspect in the document and print them as a list
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
* @param  ($index) - the position of the item according to the table's structure
*/
function getAspectItems($mysqli, $index){
	$str = "[";

	$prev_aspects = getAspects($mysqli);
	$count = count($prev_aspects);
	for($i = 0; $i < $count; $i++){
		$item = $prev_aspects[$i][$index];//get only the needed part
		
		if($index == 3 || $index == 4 || $index == 5)//these are strings
			$item = "\"".$item."\"";
			
		$str = $str.$item;
		if($i < $count-1)
			$str = $str.", ";
	}
	
	echo $str."]";
}

function getSIs($mysqli){
	$prev_SIs = array();
	$prev_aspects = getAspects($mysqli);
	
    $query = "	SELECT si_term, si_real_text, si_start, si_end, si_aspect_number 
					FROM tbl_sentiment_indication
					WHERE si_aspect_number = ?"; 
	$stmt = $mysqli->prepare($query);
	
	$count = count($prev_aspects);
	for($i = 0; $i < $count; $i++){
		$stmt->bind_param('i',$prev_aspects[$i][8]);
		if($stmt->execute()){
			$result = $stmt->get_result();
			while($data = mysqli_fetch_row($result)){
				array_push($prev_SIs, $data);
			}
		}else{
			setAlert("Error retrieving information");
		}
	
	
	}
	$stmt->close();
	return $prev_SIs;
}

/**
* Gets the number of sentiment indications of each aspect and print them as a list
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function getNumberOfSIs($mysqli){
	$str = "[";
	
	$prev_SIs = getSIs($mysqli);
	$prev_aspects = getAspects($mysqli);
	
	$count = count($prev_aspects);
	$siCount = count($prev_SIs);
	$offset = 0;
	
	for($i = 0; $i < $count; $i++){
		$j = 0;
		
		while($j+$offset < $siCount && $prev_SIs[$j+$offset][4] == $prev_aspects[$i][8]){
			$j++;
		}
		$offset = $j+$offset;
		$str = $str.$j;
		
		if($i < $count-1)
			$str = $str.", ";
	}
	
	echo $str."]";
}

/**
* Gets the items in the given position for each sentiment indication in the document and print them as a list
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
* @param  ($index) - the position of the item according to the table's structure
*/
function getSIItems($mysqli, $index){
	$str = "[";

	$prev_SIs = getSIs($mysqli);
	$count = count($prev_SIs);
	for($i = 0; $i < $count; $i++){
		$item = $prev_SIs[$i][$index];//get only the needed part
		if($index == 0 || $index == 1)//these are strings
			$item = "\"".$item."\"";
		
		$str = $str.$item;
		if($i < $count-1)
			$str = $str.", ";
	}
	
	echo $str."]";
}

/**
* This function gets all labels and print them as a list
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function getLabels($mysqli){
	$str = "[";
	
	$labels = getLabelOptions($mysqli);
	$i = 0;
	$count = count($labels)-1;
	foreach($labels as $label){
		$label = strtoupper($label);
		if($i != $count)
			$str = $str."'".$label."'".",";
		else
			$str = $str."'".$label."']";
		$i++;
	}
	
	echo $str;
}

/**
* This function gets the colors of the label options
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function getLabelColors($mysqli){
	$colors = array(); //each label's color
    $query = "	SELECT lpLabelOpt_color 
					FROM tbl_labeling_process_label_option 
					WHERE lpLabelOpt_lp = ?"; 
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['cur_lpID'] );
	if($stmt->execute()){
		$result = $stmt->get_result();
		while($data = mysqli_fetch_row($result)){
			array_push($colors, $data[0]);
		}
	}else{
		setAlert("Error retrieving information");
	}
	$stmt->close();
	return $colors;
}

/**
* This function gets the color of each label and print them all as a list
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function getColors($mysqli){
	$str = "[";
	
	$colors = getLabelColors($mysqli);
	
	$i = 0;
	$count = count($colors)-1;
	foreach($colors as $color){
		if($i != $count)
			$str = $str."'".$color."'".",";
		else
			$str = $str."'".$color."']";
		$i++;
	}
	
	echo $str;
}

/**
* This function finds out the labels options of this process,
* shuffles it, and chooses a random suggestion
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function showRandomSuggestion($mysqli){
	$labels = getLabelOptions($mysqli);
	if(!empty($labels)){
		//Selecting a random label from array($labels)
		//To be the suggested label
		srand((float)microtime()*1000000);
		shuffle($labels);
		$_SESSION['lblOptionRank'] = $labels;
		$sugLabel = array_shift($labels);	//Removes the first label from array
		showSuggestedLabel($sugLabel);
		showOtherLabels($labels);
	}
}

/**
* This function finds out labels options of this process,
* order it by rank (votes), and outputs the most voted label as
* the suggested label (and the remaining ones after that)
*
* @param  ($mysqli) - mysqli object (MYSQL database connection) 
*/
function showMostVotedSuggestion($mysqli){
	$labels = getLabelOptions($mysqli);		  //Labels of this labeling process
	$rankedLabels = getRankedLabels($mysqli); //Labels of this labeling process that have a rank
	$rankedLabels = array_merge($rankedLabels,array_diff($labels,$rankedLabels));	
	
	//Will be presented only the labels that are on $labels
	//And it will be ordered by the rank(votes)
	$labelOptions = array_intersect($rankedLabels,$labels);
	
	if(!empty($labelOptions )){
		$_SESSION['lblOptionRank'] = $labelOptions; //Stores to figure out later
		$sugLabel = array_shift($labelOptions); //Removes the first label from array (most voted)
		showSuggestedLabel($sugLabel);
		showOtherLabels($labelOptions);
	}
}

/**
* Shows labels (classes) for the current labelling process, which uses
* the transductive algorithm for classification of label suggestion.
* Current document ($_SESSION['curDocID']) must be set before calling this function	
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function showTransductiveSuggestion($mysqli){
	$labels = $_SESSION['fDoc'][$_SESSION['curDocID']];
	arsort($labels);
	$rankedLabels = array_keys ($labels);
	$_SESSION['lblOptionRank'] = $rankedLabels;
	showSuggestedLabel(array_shift($rankedLabels)); //Removes first option(best rank) from array and show it
	showOtherLabels($rankedLabels); 
}


function insertSuggestion($mysqli,$algorithm,$suggestion){
	$query = "REPLACE INTO tbl_suggestion VALUES (?,?,?,?)";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('iiss',$_SESSION['curDocID'],$_SESSION['user_id'],$algorithm,$suggestion);
	
	if(!$stmt->execute()){
		$stmt->close();
		
		dbError($mysqli);
	}						
	$stmt->close();
}

function testModeSuggestion($mysqli){
	$labels = getLabelOptions($mysqli);
	if(!empty($labels)){
		$_SESSION['lblOptionRank'] = $labels;
		showOtherLabels($labels); //No suggestion this time
		
		//Calculates Random suggestion
		srand((float)microtime()*1000000);
		shuffle($labels);
		$suggestedLabel = array_shift($labels);
		insertSuggestion ($mysqli, 'random', $suggestedLabel);		
		
		//Calculates MostVoted suggestion
		$rankedLabels = getRankedLabels($mysqli); 
		$suggestedLabel = array_shift($rankedLabels);
		insertSuggestion ($mysqli, 'mostVoted', $suggestedLabel);
		
		//Calculates Transductive suggestion
		$tLabels = $_SESSION['fDoc'][$_SESSION['curDocID']];
		arsort($tLabels);
		$rankedLabels = array_keys ($tLabels);
		$suggestedLabel = array_shift($rankedLabels);
		insertSuggestion ($mysqli, 'transductive', $suggestedLabel);
	}
}

/**
* This function outputs the labels options to user accordingly
* to the current algorithm (for label suggestion) of this labeling proccess 
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function showLabels($mysqli) {
	if($_SESSION['cur_lpAlgorithm'] == 'random')			showRandomSuggestion($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'mostVoted')	showMostVotedSuggestion($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'transductive')	showTransductiveSuggestion($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'testMode')		testModeSuggestion($mysqli);
}

function showColoredLabels($mysqli){
	$labels = getLabelOptions($mysqli);
	$colors = getLabelColors($mysqli);

	for($i = 0; $i < count($labels); $i++){
		echo "<option style='background-color:".$colors[$i]."' value=\"".$colors[$i]."\">".$labels[$i]."</option>\n";
	}

	#<option style='background-color:green' value="green"><a href="#">Positivo</a></option>
	#<option style='background-color:red' value="red"><a href="#">Negativo</a></option>
	#<option style='background-color:yellow' value="yellow"><a href="#">Neutro</a></option>
}

/**
* If the process allows the user to suggest a label, then this input
* is inserted in the form	
*/
function showInputOfSuggestions() {
	if( (isset( $_SESSION['cur_lpType'] )) && ($_SESSION['cur_lpType'] == "postSet")) {
		//If it is allowed to make label suggestion, then we add this input to the form
		echo
		'<tr>
			<td>Suggest a new label :</td>
			<td>
				<table id="tblLabelList">
					<tr>
						<td><input  id="txtLabel"    type="text" name="txtLabel" ></td>
						<td><button id="btnAddLabel" type="button" >Add</button></td>
					</tr>
				</table>
			</td>
		</tr>';
		
	}	
}

/**
* Outputs the input 'next button' accordingly to the current document, 
* i.e., if it is the last document, then the button will have a 'finalize'
* indication instead of 'next'
*/
function showButtonNext () {
	if(	!empty($_SESSION['curDocID']) 
		&& !empty($_SESSION['maxDocID']) 
		&& $_SESSION['curDocID'] == $_SESSION['maxDocID'] ) {
			//Last document
			echo "<input type='button' class='btn btn-default' onclick=\"validateForm('next')\" value='Finish'>";
			phpAlert("Last document to be labeled!");
			return true;
		}
	echo "<input type='button' class='btn btn-default' onclick=\"validateForm('next')\" value='Next Document'>";
	return false;
}

/**
* Unset the data (of this labeling process) stored in session 
*/
function unsetData(){
	if( !empty($_SESSION['cur_lpAlgorithm']) && 
		($_SESSION['cur_lpAlgorithm'] == 'transductive' || $_SESSION['cur_lpAlgorithm'] == 'testMode'))
			unsetTransductiveData();
	
	unset($_SESSION['cur_lpID']);
	unset($_SESSION['cur_lpName']);
	unset($_SESSION['cur_lpMinAccRate']);
	unset($_SESSION['cur_lpMinFinalAccRate']);
	unset($_SESSION['cur_lpMultilabel']);
	unset($_SESSION['cur_lpType']);
	unset($_SESSION['cur_lpAlgorithm']);
	unset($_SESSION['curDocID']);
	unset($_SESSION['minDocID']);
	unset($_SESSION['maxDocID']);
	unset($_SESSION['lblOptionRank']);
	unset($_SESSION['cur_lpLabelingType']);
}

/**
* This function connects to database and retrives
* the labels options of the current labeling process
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
* @return ($labels) - array of labels (options)
*/
function getLabelOptions($mysqli){
	$labels = array(); //Options of labels for this Labeling Process
    $query = "	SELECT lpLabelOpt_label 
					FROM tbl_labeling_process_label_option 
					WHERE lpLabelOpt_lp = ?"; 
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['cur_lpID'] );
	if($stmt->execute()){
		$result = $stmt->get_result();
		while($data = mysqli_fetch_row($result)){
			$data[0] =($data[0]);
			array_push($labels, $data[0]);
		}
	}else{
		setAlert("Error retrieving information");
	}
	$stmt->close();
	return $labels;
}

/**
* This function connects to database and retrives
* the ranked labels of the current labeling process
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
* @return ($rankedLabels) - array of labels (ordered by rank)
*/
function getRankedLabels($mysqli){
	$rankedLabels = array();
	$query = "	SELECT rLabel_label 
						FROM tbl_ranked_label 
						WHERE rLabel_document = ?
						ORDER BY rLabel_accuracy DESC"; //Labels ordered by rank
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['curDocID'] );
	if($stmt->execute()){
		$result = $stmt->get_result();
		while($data = mysqli_fetch_row($result)){
			$data[0] =($data[0]);
			array_push($rankedLabels, $data[0]);
		}
	}else{
		setAlert("Error retrieving information");
	}
	$stmt->close();
	return $rankedLabels;
}

/**
* This function connects to database and sets an document as
* 'skipped' by the tagger (in case the button 'jump' is clicked)
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function jumpDocument($mysqli){
	//Deleting old entries
	if($_SESSION['cur_lpAlgorithm'] == 'mostVoted') removeVotes($mysqli);
	deleteChosenLabels($mysqli);  
	
	//Updating document labeling status --> skipped
	$query = "UPDATE tbl_document_labeling 
				SET labeling_status = 'skipped', labeling_date = CURRENT_TIME() 
				WHERE labeling_document = ? 
					AND labeling_tagger = ?;";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if(!$stmt->execute()){
		$stmt->close();
		
		dbError($mysqli);
		return;
	}
	$stmt->close();
	
}

/**
* This function connects to database and discounts the votes (chosen labels)
* that the tagger did previously for the current document
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function removeVotes($mysqli){
	//Maybe someone has labelled a document, 
	//then came back later and changed(jumped or gave another label)
	//So, we discount this previous vote on the table of rank
	$query = "	UPDATE tbl_ranked_label 
					JOIN tbl_chosen_label 
					ON (label_document = rLabel_document 
						AND label_label = rLabel_label
						AND label_document = ?
						AND label_tagger = ?)
					SET tbl_ranked_label.rLabel_accuracy = tbl_ranked_label.rLabel_accuracy - 1";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if(!$stmt->execute()){
		$stmt->close();
		
		dbError($mysqli);
		return;
	}
	$stmt->close();
}

/**
* This function connects to database and 
* adds one vote for each chosen label
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function addVotes($mysqli){
	$chosenLabels = array(); //The merge of the labels that were chosen(checkboxes) and suggested
	if(isset($_SESSION['lblOptionRank'] )) {
		//Adding the labels marked on checkboxes to the created array
		foreach ($_SESSION['lblOptionRank'] as $lblOption){
			$lblOption =($lblOption);
			if(isChecked('lpLabels',$lblOption)){
				array_push($chosenLabels,$lblOption);
			}
		}
	}
	
	if(isset($_POST['lpSugLabels'] )) { 
		$chosenLabels = array_merge($chosenLabels,$_POST['lpSugLabels']);
	}

	$query = "	INSERT INTO tbl_ranked_label
					VALUES (?,?,1)
					ON DUPLICATE KEY UPDATE 
						rLabel_accuracy = rLabel_accuracy + 1"; //Adding one
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('is',$_SESSION['curDocID'],$lblOption);
	
	foreach ($chosenLabels as $lblOption){
		$lblOption =($lblOption);
		if(!$stmt->execute()){
			$stmt->close();
			
			dbError($mysqli);
			return;					
		}		
	}
	$stmt->close();
}

/**
* This function connects to database and 
* adds one vote for each chosen label
* In this case, it does not check suggestions (labels)
*
* @param ($mysqli)			- mysqli object (MYSQL database connection) 	
* @param ($chosenLabels)	- array with the chosen labels
*/
function addTransductiveVotes($mysqli,$chosenLabels){
	$query = "	UPDATE tbl_ranked_label
					SET rLabel_accuracy = rLabel_accuracy + 1
					WHERE rLabel_document = ?
						AND rLabel_label = ?;"; 
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('is',$_SESSION['curDocID'],$chosenLabel);
	
	foreach ($chosenLabels as $chosenLabel){
		$chosenLabel =($chosenLabel);
		if(!$stmt->execute()){
			$stmt->close();
			
			dbError($mysqli);
			return;					
		}		
	}
	$stmt->close();
}

/**
* Maybe someone has labeled a document and 
* then came back later and changed it (jumped or gave another label)
* In this case, this function is used to remove the labels that
* were chosen previously
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function deleteChosenLabels($mysqli){
	$query = "	DELETE FROM tbl_chosen_label 
					WHERE label_document = ? 
						AND label_tagger = ?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if(!$stmt->execute()){
		$stmt->close();
		
		dbError($mysqli);
		return;
	}
	$stmt->close();
}

/**
* Updates the table of labels, adding the labels that were suggested 
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertLabelSuggestion($mysqli){
	//Inserting each new label on the table of labels
	if(isset($_POST['lpSugLabels'] )){
		$query = "REPLACE INTO tbl_label VALUES(?)";
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('s', $lbl);
		foreach ($_POST['lpSugLabels'] as $lbl){
			$lbl =($lbl);
			if(!$stmt->execute()){
				$stmt->close();
				
				dbError($mysqli);
				return;								
			}		
		}
		$stmt->close();
	}
}

/**
* If the number of users agreeing with some label is bigger than min_acceptance_rate,
* then we insert this label on the options for the current labeling process
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function acceptSuggestions($mysqli){
	$query = "	SELECT label_label FROM ( 
								SELECT label_label, label_tagger FROM tbl_chosen_label 
								WHERE label_rank=-1 GROUP BY label_label , label_tagger 
								)AS subQuery 
					WHERE label_label NOT IN (
							SELECT lpLabelOpt_label FROM tbl_labeling_process_label_option 
							WHERE lpLabelOpt_lp = ?)
					GROUP BY label_label 
					HAVING COUNT(*) >= ? ";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['cur_lpID'],$_SESSION['cur_lpMinAccRate']);
	
	$secondQuery = "INSERT INTO tbl_labeling_process_label_option(lpLabelOpt_lp, lpLabelOpt_label) VALUES (?, ?);";
	$secondStmt  = $mysqli->prepare($secondQuery);
	
	//phpAlert("lpId: ".$_SESSION['cur_lpID']);
	$secondStmt->bind_param('is',$_SESSION['cur_lpID'], $labeldata);
	
	if($stmt->execute()){
		$result = $stmt->get_result();
		if ($result->num_rows > 0){	
			while($data = mysqli_fetch_row($result)){
				$labeldata =($data[0]);
				phpAlert($labeldata);
				//Inserting the labels that were accepted into database
				if(!$secondStmt->execute()){
					$stmt->close();
					$secondStmt->close();
					//phpAlert("Here: ".$mysqli->error);					
					dbError($mysqli);
					return;					
				}
			}
		}
	}else{
		$stmt->close();
		$secondStmt->close();	
		
		dbError($mysqli);
		return;
	}
	$stmt->close();
	$secondStmt->close();	
}

/**
* Verifies if a label is checked 
*
* @param  ($inputName)	- name of input (checkboxes) on form 
* @param  ($value) 	  	- value(label) to verify if it is checked 
* @return 				- returns true if the label is checked and false otherwise	
*/
function isChecked($inputName,$value){
	if(!empty($_POST[$inputName])){
        foreach($_POST[$inputName] as $chkval){
            if($chkval == $value){
                return true;
            }
        }
    }
    return false;
}

/**
* Updating database: adding to table of chosen label the labels that
* were suggested by the current tagger
* Note: suggestions are marked with rank -1
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertLabelsSuggestedByTagger($mysqli){
	if (isset($_POST['lpSugLabels'])){
		$query = "REPLACE INTO tbl_chosen_label 
					VALUES(?,?,?,-1)";		//Rank -1 for suggested labels
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('iis',$_SESSION['curDocID'],$_SESSION['user_id'],$lbl);
		foreach ($_POST['lpSugLabels'] as $lbl){
			$lbl =($lbl);
			if(!$stmt->execute()){
				$stmt->close();
				dbError($mysqli);
				return;
			}	
		}
		$stmt->close();
	}	
}

/**
* Updating database: adding to table of chosen label the labels that
* were actually chosen (marked on checkboxes)
* Note: Its rank is the position on the checkbox (and then we if the user
* accepted the system's suggestions, as it is marked with rank one)
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertChosenLabels($mysqli){
	if(isset($_SESSION['lblOptionRank'] )) {
		$count = 1 ;
		$chosenLabels = array();
		$query = "REPLACE INTO tbl_chosen_label VALUES(?,?,?,?)"; 
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('iisi',$_SESSION['curDocID'],$_SESSION['user_id'],$lblOption,$count);
		foreach ($_SESSION['lblOptionRank'] as $lblOption){
			$lblOption =($lblOption);
			if(isChecked('lpLabels',$lblOption)){
				$chosenLabels[] = $lblOption;
				if(!$stmt->execute()){
					$stmt->close();
					dbError($mysqli);
					return;					
				}
			}
			$count = $count + 1 ;
		}
		$stmt->close();
	}
	return $chosenLabels;
}

/**
* Updating database after labels were chosen
* and when the suggestion algorithm is random
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertRandom($mysqli){
	//Deleting old entries 
	deleteChosenLabels($mysqli); 
	
	//Then, take care of the labels suggested by the tagger
	insertLabelsSuggestedByTagger($mysqli);
	
	//Next, inserting the labels that are marked on the checkboxes
	//Obs.: Its rank was saved in $_SESSION['lblOptionRank']
	insertChosenLabels($mysqli);
}

/**
* Updating database after labels were chosen
* and when the suggestion algorithm is 'most voted'
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertMostVoted($mysqli){
	//Deleting old entries 
	removeVotes($mysqli);
	deleteChosenLabels($mysqli); 	
	
	//Then, take care of the labels suggested by the tagger
	insertLabelsSuggestedByTagger($mysqli);
	
	//Next, inserting the labels that are marked on the checkboxes
	//Obs.: Its rank was saved in $_SESSION['lblOptionRank']
	insertChosenLabels($mysqli);
	addVotes($mysqli);
}

/**
* Updating database after labels were chosen
* and when the suggestion algorithm is 'transductive classification'
* Also, updates the algorithm's matrices 
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertTransductive($mysqli){
	//Deleting old entries 
	removeVotes($mysqli);
	deleteChosenLabels($mysqli); 
	
	$chosenLabels = insertChosenLabels($mysqli);
	addTransductiveVotes($mysqli,$chosenLabels);
	transductiveClassification ($mysqli);	//Updates matrices
}


/**
* Updating database after a document is labelled
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function insertLabels($mysqli){
	insertLabelSuggestion($mysqli); //New labels to the database
	
	if($_SESSION['cur_lpAlgorithm'] == 'random') insertRandom($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'mostVoted') insertMostVoted($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'transductive') insertTransductive($mysqli);
	else if($_SESSION['cur_lpAlgorithm'] == 'testMode') insertTransductive($mysqli); //Works in the same way
	
	//Updating document labeling status --> labeled
	$query = "	UPDATE tbl_document_labeling 
					SET labeling_status = 'labeled', labeling_date = CURRENT_TIME() 
					WHERE labeling_document = ? 
						AND labeling_tagger = ?;";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if(!$stmt->execute()){
		$stmt->close();
		dbError($mysqli);
		return;	
	}
	$stmt->close();
	
}

/**
* Updates tagger's labeling process status to "concluded"
* after that all the documents were marked (labelled or jumped)
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function concludeTaggerLP($mysqli){
	$query = "	UPDATE tbl_labeling_process_tagger 
						SET process_tagger_status = 'concluded' 
						WHERE process_tagger_process = ? 
							AND process_tagger_tagger = ?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['cur_lpID'],$_SESSION['user_id']);
	if(!$stmt->execute()){
		$stmt->close();
		dbError($mysqli);
		return;	
	}
	$stmt->close();
}

/**
* Updates labeling process status to "concluded"
* after all the belonging taggers concludes the labeling process
*
* @param ($mysqli) - mysqli object (MYSQL database connection) 	
*/
function concludeLP($mysqli){
	//If all taggers have finished, then change the LP status to concluded
	$query = "SELECT COUNT(*) 
				FROM tbl_labeling_process_tagger
				WHERE process_tagger_process = ? 
					AND process_tagger_status != 'concluded'";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['cur_lpID']);
	
	$secondQuery = "UPDATE tbl_labeling_process 
						SET process_status = 'concluded' 
						WHERE process_id = ?";
	$secondStmt =	$mysqli->prepare($secondQuery);	
	$secondStmt->bind_param('i',$_SESSION['cur_lpID'] );
	
	if($stmt->execute()){
		$stmt->store_result();
		$stmt->bind_result($missingTaggers);
		$stmt->fetch();	
		if($missingTaggers == 0){
			//Updating labeling progress status --> concluded
			if(!$secondStmt->execute()){
				$stmt->close();
				$secondStmt->close();
				dbError($mysqli);
				return;					
			}
		}
	}else{
		$stmt->close();
		$secondStmt->close();
		dbError($mysqli);
		return;
	}
	$stmt->close();
	$secondStmt->close();
}

function isHiddenAspectEnabled($mysqli){
	$ret = 0;
	$query = "SELECT process_hidden_aspect  
					FROM tbl_labeling_process
					WHERE process_id = ?" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['cur_lpID']);
	if($stmt->execute()){
		$stmt->bind_result($ret);
		$stmt->fetch();
		$stmt->close();
	}else{
		$stmt->close();
	}
	return $ret;
}

function isGeneralAspectEnabled($mysqli){
	$ret = 0;
	$query = "SELECT process_generic_aspect  
					FROM tbl_labeling_process
					WHERE process_id = ?" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['cur_lpID']);
	if($stmt->execute()){
		$stmt->bind_result($ret);
		$stmt->fetch();
		$stmt->close();
	}else{
		$stmt->close();
	}
	return $ret;
}

/**
* Each document has an ID, which is created in a sequential manner (3,4,5...)
* Then, each labeling process has n documents and each one of this
* Has an ID inside a range, which is discovered
* and saved as minDocID and maxDocID (in session)
* Obs.: maxDocID  - minDocID = n
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- current labeling process id
*/
function getLPDocRange ($mysqli, $lpID) {
	$query = "	SELECT MIN(document_id), MAX(document_id) 
					FROM tbl_document 
					WHERE document_process = ?" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$lpID);
	if($stmt->execute()){
		$stmt->bind_result($_SESSION['minDocID'],$_SESSION['maxDocID']);
		$stmt->fetch();
		$stmt->close();
	}else{
		$stmt->close();
		dbError($mysqli);
	}
}

/**
* Discovers which document should be the first one
* to be presented (that is, the first with status 'waiting')
* and stores it in session as curDocID. 
* If there is no document with this status, then the curDocID
* is set to the first document of this labeling process
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
*/
function getFirstDocument ($mysqli) {
	$query = "	SELECT MIN(labeling_document) 
					FROM tbl_document_labeling 
					WHERE (	labeling_tagger = ? 
						AND labeling_document >= ? 
						AND labeling_document <= ? 
						AND labeling_status='waiting')" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('iii',$_SESSION['user_id'],$_SESSION['minDocID'],$_SESSION['maxDocID']);
	if($stmt->execute()){
		$stmt->bind_result($_SESSION['curDocID']);
		$stmt->fetch();
		$stmt->close();
	}else{
		$stmt->close();
		dbError($mysqli);
	}

	if( empty ( $_SESSION['curDocID'] ) ) $_SESSION['curDocID'] = $_SESSION['minDocID'];
}

/**
* Gets current document name and text
*
* @param  ($mysqli) 	- mysqli object (MYSQL database connection)
* @return - array with the desired data 	
*/
function getDocumentInfo ($mysqli) {
	$docName = $docText = "";
	$query = "	SELECT document_name, document_text 
					FROM tbl_document 
					WHERE document_id = ? LIMIT 1" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['curDocID']);
	if($stmt->execute()){
		$stmt->bind_result($docName,$docText);
		$stmt->fetch();
		$stmt->close();
		$docName = utf8_decode($docName);
		$docText = utf8_decode($docText);
	}else{
		$stmt->close();
		dbError($mysqli);
		return;
	}
	$docName = substr ($docName,0,-4); //Removing ".txt"

	return array ($docName, $docText);
	
}

function finalizeDocument ($mysqli) {
	//Checking if a document should get status finalized -- First Query
	$maxVotes = 0;
	$query = "	SELECT MAX(cnt) 
					FROM (SELECT COUNT(*) AS cnt 
							FROM tbl_chosen_label JOIN tbl_document 
								ON (document_id=label_document AND document_process=? AND document_id=?) 
							GROUP BY label_label) 
					AS maxVotes";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['cur_lpID'],$_SESSION['curDocID']);				
	if($stmt->execute()){
		$stmt->bind_result($maxVotes);
		$stmt->fetch();
		$stmt->close();
	}else{
		$stmt->close();
		dbError($mysqli);
		return;
	}				
	
	if( $maxVotes >= $_SESSION['cur_lpMinFinalAccRate'] ){
		//Finalizes the document -- Second query
		$secQuery = "	UPDATE tbl_document_labeling 
						SET labeling_status = 'finalized', labeling_date = CURRENT_TIME() 
						WHERE labeling_document = ? 
							AND labeling_status != 'labeled';";
		$secStmt = $mysqli->prepare($secQuery);
		$secStmt->bind_param('i',$_SESSION['curDocID']);
		if(!$secStmt->execute()){
			$secStmt->close();
			dbError($mysqli);
			return;	
		}
		$secStmt->close();
	}
}

function isCurrentDocumentFinalized ($mysqli) {
	$status = "";
	$query = "SELECT labeling_status 
				FROM tbl_document_labeling 
				WHERE labeling_document = ? 
				AND labeling_tagger = ?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
	if($stmt->execute()){
		$stmt->bind_result($status);
		$stmt->fetch();
		$stmt->close();
		return $status == 'finalized' ;
	}else{
		$stmt->close();
		dbError($mysqli);
		return;
	}
}

function saveAnnotations($mysqli){
	if(!empty($_POST["numberOfRows"]) && $_POST["numberOfRows"] > 0){
		$numRows = $_POST["numberOfRows"];
		$numOfSIs = $_POST["numberOfSIs"];
		$aspects = $_POST['aspect_aspect'];
		$types = $_POST['aspect_type'];
		$polarities = $_POST['aspect_polarity'];
		$starts = $_POST['aspect_start'];
		$ends = $_POST['aspect_end'];
		$SIs; $SI_starts; $SI_ends; $SI_real_texts;
		if(!empty($_POST['SIs'])){
			$SIs = $_POST['SIs'];
			$SI_starts = $_POST['SI_starts'];
			$SI_ends = $_POST['SI_ends'];
			$SI_real_texts = $_POST['SI_real_texts'];
		}
		
		$curSI = 0;
		$aspect_type;
		
		$ids = [];
		
		//Get all aspect IDs associated to the current document, tagger and proccess
		$select_query1 = "SELECT aspect_number 
								FROM tbl_aspect 
								WHERE aspect_lp = ?
								AND aspect_tagger = ?
								AND aspect_doc = ?"; 
								
		$select_stmt1 = $mysqli->prepare($select_query1);
		$select_stmt1->bind_param('iii',$_SESSION['cur_lpID'], $_SESSION['user_id'], $_SESSION['curDocID']);
		if($select_stmt1->execute()){
			$result = $select_stmt1->get_result();
			while($data = mysqli_fetch_row($result)){
				array_push($ids, $data[0]);
			}
		}else{
			setAlert("Error retrieving information");
		}
		$select_stmt1->close();
		
		foreach($ids as $id){
			//Deleting aspect entry, for the case when the user returned to a annotated document
			$delete_query = "DELETE FROM tbl_aspect
								WHERE aspect_number = ?"; 
								
			$delete_stmt = $mysqli->prepare($delete_query);
			$delete_stmt->bind_param('i',$id);
				
			if(!$delete_stmt->execute()){
				phpAlert("".$mysqli->error);
				$delete_stmt->close();
				dbError($mysqli);
				return;	
			}
			$delete_stmt->close();
			
			//Deleting sentiment indications for the document
			$delete_query = "DELETE FROM tbl_sentiment_indication
								WHERE si_aspect_number = ?"; 
								
			$delete_stmt = $mysqli->prepare($delete_query);
			$delete_stmt->bind_param('i', $id);
				
			if(!$delete_stmt->execute()){
				phpAlert("".$mysqli->error);
				$delete_stmt->close();
				dbError($mysqli);
				return;	
			}
			$delete_stmt->close();
		}
		
		for($i = 0; $i < $numRows; $i++){
			
			$aspect = $aspects[$i];
			if($types[$i] == 1){
				$aspect = "HIDDEN";
				$aspect_type = "hidden";
			}else if($types[$i] == 2){
				$aspect = "GENERIC";
				$aspect_type = "generic";
			}else{
				$aspect_type = "normal";
			}
			
			//Saving primary aspect data
			$insert_query1 = "INSERT INTO tbl_aspect (aspect_lp, aspect_type, aspect_aspect, aspect_polarity, 
												aspect_start, aspect_end, aspect_tagger, aspect_doc) 
											VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
											
			$insert_stmt1 = $mysqli->prepare($insert_query1);
			
			$aspect =($aspect); $polarities[$i] =($polarities[$i]);
			$insert_stmt1->bind_param('isssiiii', $_SESSION['cur_lpID'], $aspect_type, $aspect, 
								$polarities[$i], $starts[$i], $ends[$i], $_SESSION['user_id'], $_SESSION['curDocID']);
				
			if(!$insert_stmt1->execute()){
				phpAlert("".$mysqli->error);
				$insert_stmt1->close();
				dbError($mysqli);
				return;	
			}
			$insert_stmt1->close();
			
			$aspect_number;
			
			//Retrieving the number assigned to the aspect
			$select_query = "SELECT aspect_number 
								FROM tbl_aspect 
								WHERE aspect_lp = ?
								AND aspect_tagger = ?
								AND aspect_aspect = ?
								AND aspect_start = ?
								AND aspect_doc = ?"; 
								
			$select_stmt = $mysqli->prepare($select_query);
			$select_stmt->bind_param('iisii',$_SESSION['cur_lpID'], $_SESSION['user_id'], $aspect, $starts[$i], $_SESSION['curDocID']);
			if($select_stmt->execute()){
				$result = $select_stmt->get_result();
				$data = mysqli_fetch_row($result);
				$aspect_number = $data[0];
			}else{
				setAlert("Error retrieving information");
			}
			$select_stmt->close();
			
			for($j = 0; $j < $numOfSIs[$i]; $j++){
				//Saving sentiment indication data
				$insert_query2 = "INSERT INTO tbl_sentiment_indication (si_term, si_real_text, si_start, si_end, si_aspect_number) 
												VALUES (?, ?, ?, ?, ?);";
				$insert_stmt2 = $mysqli->prepare($insert_query2);
				$SIs[$curSI] =($SIs[$curSI]);
				
				$insert_stmt2->bind_param('ssiii', $SIs[$curSI], $SI_real_texts[$curSI], $SI_starts[$curSI], $SI_ends[$curSI], $aspect_number);
									
				if(!$insert_stmt2->execute()){
					phpAlert("".$mysqli->error);
					$insert_stmt2->close();
					dbError($mysqli);
					return;	
				}
				$insert_stmt2->close();
				
				$curSI++;
			}
		}
	
		
		//Updating document labeling status --> labeled
		$query = "	UPDATE tbl_document_labeling 
						SET labeling_status = 'labeled', labeling_date = CURRENT_TIME() 
						WHERE labeling_document = ? 
							AND labeling_tagger = ?;";
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('ii',$_SESSION['curDocID'],$_SESSION['user_id']);
		if(!$stmt->execute()){
			$stmt->close();
			dbError($mysqli);
			return;	
		}
		$stmt->close();
	}
}

//Rollback point
$mysqli->autocommit(FALSE);$mysqli->commit();

//First, checking form submission
if(	(!empty($_POST["btnSubmit"])) 
	&& (isset($_SESSION['curDocID'])) 
	&& (isset($_SESSION['minDocID'])) 
	&& (isset($_SESSION['maxDocID']))) {
	
	$btnSubmit = $_POST["btnSubmit"];
	if($btnSubmit === 'back'){			//'Back' button was clicked
		if( $_SESSION['curDocID'] <= $_SESSION['minDocID']){
			//If it is the first document, then take user back to 'guideline.php'
			header('Location: ./guideline.php?lpID=' . (string)$_SESSION['cur_lpID']);
			unsetData();
			exit();
		}else{
			do{
				$_SESSION['curDocID'] = $_SESSION['curDocID'] - 1; //Previous Document
			}while ($_SESSION['curDocID'] >= $_SESSION['minDocID'] && isCurrentDocumentFinalized($mysqli));
			
			if( $_SESSION['curDocID'] < $_SESSION['minDocID']){
				//If it is the first document, then take user back to 'guideline.php'
				header('Location: ./guideline.php?lpID=' . (string)$_SESSION['cur_lpID']);
				unsetData();
				exit();
			}
		}
	}else if($btnSubmit === 'stop'){	//'Stop' button was clicked
		unsetData();
	}
	else{
		if($btnSubmit === 'jump'){		//'Jumping' button was clicked
			jumpDocument($mysqli);
		}else{							//'Next' button was clicked
			if(!isset($_SESSION['cur_lpLabelingType']) || $_SESSION['cur_lpLabelingType'] == 'normal'){
				insertLabels($mysqli);
				finalizeDocument($mysqli);
			}else{
				saveAnnotations($mysqli);
				//finalizeDocument
			}
		}
		
		if( $_SESSION['curDocID'] >= $_SESSION['maxDocID']){
			//If it is the last document, then take user back to 'index.php'
			concludeTaggerLP($mysqli);
			concludeLP($mysqli);
			acceptSuggestions($mysqli);
			unsetData();
		}else{
			do {
				$_SESSION['curDocID'] = $_SESSION['curDocID'] + 1; //Next Document
			} while ($_SESSION['curDocID'] <= $_SESSION['maxDocID'] && isCurrentDocumentFinalized($mysqli));
			
			if( $_SESSION['curDocID'] > $_SESSION['maxDocID']) {
				//If it is the last document, then take user back to 'index.php'
				concludeTaggerLP($mysqli);
				concludeLP($mysqli);
				acceptSuggestions($mysqli);
				unsetData();				
			}
		}
	}
}

if (isset($_SESSION['cur_lpID'])){
	$lpID = $_SESSION['cur_lpID'];
	if(!(isset($_SESSION['minDocID']) && isset($_SESSION['maxDocID']))){
		getLPDocRange($mysqli, $lpID);	//Setting minDocId and maxDocId
	}
	if(!isset($_SESSION['curDocID'])){
		getFirstDocument ($mysqli);		//Setting current document ID
	}	
	
	//Finally, Getting current document name and text
	$docName = $docText = "";
	list ($docName, $docText) = getDocumentInfo ($mysqli);
	
	$mysqli->commit();$mysqli->autocommit(TRUE);
}else {
	$mysqli->commit();$mysqli->autocommit(TRUE);
	header('Location: ./index.php');
	exit();
}