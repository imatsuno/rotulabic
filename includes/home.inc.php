<?php
include_once 'db_connect.php';
include_once 'includes/functions.php';
include_once 'lpbhn.php';	//Using functions 'setTransductiveData' and 'unsetTransductiveData'

sec_session_start();
unsetLabelingProcessData();

/**
* Connects to database and retrieves
* the status (concluded, waiting, in_progress) of a labeling process
* which the current user is tagging
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- Labeling Process ID	
* @return ($status) - Status of the given labeling process
*/
function getLPStatus($mysqli,$lpID){
	$status = "";
	$query = "	SELECT process_tagger_status 
						FROM tbl_labeling_process_tagger 
						WHERE process_tagger_process = ? AND process_tagger_tagger = ?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$lpID,$_SESSION['user_id']);
	if($stmt->execute()){
		$stmt->bind_result($status);
		$stmt->fetch();
	}else{
		setAlert("Erro ao recuperar o status do processo de rotulação");
	}
	$stmt->close();
	return $status;
}

/**
* Connects to the database and retrieves the number of done documents (skipped or labeled)
* by the tagger (current user) in a given labeling process
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- ID of a labeling process	
* @return ($answer) - Number of done documents
*/
function getNumberOfDoneDocuments($mysqli,$lpID) {
	$answer = 0;
    $query = "SELECT COUNT(*) 
				FROM tbl_document_labeling JOIN tbl_document
					ON (document_id=labeling_document AND document_process= ?)
				WHERE labeling_tagger = ?
				AND (labeling_status = 'labeled' OR labeling_status='skipped')" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$lpID,$_SESSION['user_id']);
	if($stmt->execute()){
		$stmt->bind_result($answer);
		$stmt->fetch();
	}
	$stmt->close();
	return $answer;
}


/**
* Connects to the database and retrieves the number of available documents (not finalized)
* for the tagger (current user) in a given labeling process
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- ID of a labeling process	
* @return ($answer) - Number of available documents
*/
function getTotalNumberOfDocuments($mysqli,$lpID) {
	$answer = 0;
    $query = "SELECT COUNT(*) 
				FROM tbl_document_labeling JOIN tbl_document
					ON (document_id=labeling_document AND document_process= ?)
				WHERE labeling_tagger = ?
				AND labeling_status != 'finalized'" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('ii',$lpID,$_SESSION['user_id']);
	if($stmt->execute()){
		$stmt->bind_result($answer);
		$stmt->fetch();
	}
	$stmt->close();
	return $answer;
}

/**
* Prints a row of the tagger's table, which contains a
* labeling process information
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpInfo) 	- Array with labeling process' name and id	
*/
function printTaggerTableRow ( $mysqli  , $lpInfo  ){
	$lpID = $lpInfo[0];
	$status = getLPStatus($mysqli,$lpID);
	echo '<tr>';
	echo '<td>'.$lpID.'</td>';
	
	$tot = getTotalNumberOfDocuments($mysqli,$lpID);
	
	//Progress means the percentage of labeled documents (using
	//the number of available documents [not finalized] as the total) 
	$progress = '';	
	
	if($status=='concluded'){
		echo '<td>'. $lpInfo[1].'</td>';
		$progress =  ' (' . $tot . '/' . $tot .')';
	}else{
		$done = getNumberOfDoneDocuments($mysqli,$lpID);
		$progress =  ' (' . $done . '/' . $tot .')';
		echo '<td><a href="guideline.php?lpID=' .$lpID. 
			'&status=' .$status. '">' .$lpInfo[1].'</a></td>';
	}
	echo '<td>'.getPortugueseStatus ($status).$progress.'</td>';
	echo '</tr>';	
}

/**
* Prints the information of the labeling processes that the 
* tagger (current user) was assigned to. If there is none, then
* a message is presented instead
*
* @param ($table) 	- matrix with labeling processes data
* @param ($mysqli) 	- mysqli object (MYSQL database connection)
*/
function printTaggerTable( $table , $mysqli ) {
	if ($table->num_rows > 0){
		echo '<thead><tr><th>ID</th><th>Nome</th><th>Status</th></tr></thead>';
		echo '<tbody>';
		while($row = mysqli_fetch_row($table)){
			printTaggerTableRow ($mysqli, $row );
		}
		echo '</tbody>';
	}else{
		echo '<td>Você ainda não está vinculado<br>a nenhum processo de rotulação</td>';
	}	
}

/**
* Connects to database and retrieves the data of the labeling processes  
* that the tagger (current user) was assigned to
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection)
*/
function getTaggerLPs($mysqli) {
	$query = "	SELECT process_id, process_name 
					FROM tbl_labeling_process JOIN tbl_labeling_process_tagger
					ON (process_id =  process_tagger_process AND process_tagger_tagger = ?)
					ORDER BY process_id";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['user_id']);
	if($stmt->execute()){
		$result = $stmt->get_result();
		printTaggerTable( $result, $mysqli );
	}else{
		setAlert("Erro ao recuperar os processos de rotulação do banco de dados!");
	}
	$stmt->close();
}

/*
* Connects to database and retrieves the number of taggers that were 
* assigned to a certain labeling process and completed it (status = concluded)
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- ID of a labeling process	
* @return ($answer) - Number of done taggers
*/
function getNumberOfDoneTaggers($mysqli,$lpID) {
	$answer = 0;
    $query = "SELECT COUNT(*) 
				FROM tbl_labeling_process_tagger
				WHERE process_tagger_process = ?
					AND process_tagger_status='concluded'" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$lpID);
	if($stmt->execute()){
		$stmt->bind_result($answer);
		$stmt->fetch();
	}
	$stmt->close();
	return $answer;
}

/*
* Connects to database and retrieves the number of taggers that were 
* assigned to a certain labeling process
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpID) 	- ID of a labeling process	
* @return ($answer) - Number of taggers
*/
function getTotalNumberOfTaggers($mysqli,$lpID) {
	$answer = 0;
    $query = "SELECT COUNT(*) 
				FROM tbl_labeling_process_tagger
				WHERE process_tagger_process = ?" ;
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$lpID);
	if($stmt->execute()){
		$stmt->bind_result($answer);
		$stmt->fetch();
	}
	$stmt->close();
	return $answer;
}

/**
* Prints a row of the admin's table, which contains 
* information of a labeling process that is owned by the current user (admin)
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection) 	
* @param ($lpInfo) 	- Array with labeling process' name and id	
*/
function printAdminTableRow ( $lpInfo,$mysqli ){
	
	$lpID = $lpInfo[0];
	$tot = getTotalNumberOfTaggers($mysqli,$lpID);
	$done = getNumberOfDoneTaggers($mysqli,$lpID);
	$progress =  ' (' . $done . '/' . $tot .')';
	
	echo '<tr>';
	echo '<td>'.$lpID.'</td>';
	echo '<td><a href="labelingProcessInfo.php?lpID=' .$lpID. '">' .$lpInfo[1]. '</a></td>';
	echo '<td>'.getPortugueseStatus ($lpInfo[2]).$progress.'</td>';
	echo '</tr>';	
}

/**
* Prints the information of the labeling processes that the 
* current user (admin) owns. If there is none, then
* a message is presented instead
*
* @param ($table) 	- matrix with labeling processes data
* @param ($mysqli) 	- mysqli object (MYSQL database connection)
*/
function printAdminTable( $table,$mysqli ) {
	if ($table->num_rows > 0){
		echo '<thead><tr><th>ID</th><th>Nome</th><th>Status</th></tr></thead>';
		echo '<tbody>';
		while($row = mysqli_fetch_row($table)){
			printAdminTableRow ( $row,$mysqli );
		}
		echo '</tbody>';
	}else{
		echo '<td>Você ainda não criou<br>nenhum processo de rotulação</td>';
	}	
}

/**
* Connects to database and retrieves the data of the labeling processes  
* which the current user (admin) owns
*
* @param ($mysqli) 	- mysqli object (MYSQL database connection)
*/
function getAdminLPs($mysqli) {
	$LPs = array();
	$query = "SELECT process_id, process_name, process_status
				  FROM tbl_labeling_process 
				  WHERE process_admin = ? 
				  ORDER BY process_id";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param('i',$_SESSION['user_id']);
	if($stmt->execute()){
		$result = $stmt->get_result();
		printAdminTable ($result,$mysqli);	
	}else{
		setAlert("Erro ao recuperar os processos de rotulação do banco de dados!");
	}
	$stmt->close();
}