﻿<?php include_once 'includes/taggers.inc.php'; ?>

<!DOCTYPE html>
<html lang="pt">
    <head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Adicionar Rotuladores</title>
		
		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		
		<!-- Bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		
		<link rel="stylesheet" href="styles/main.css" />
		<link rel="stylesheet" href="styles/sticky-footer-navbar.css" />	
		
		<script type="text/javascript">
			$(document).ready(function(){
				//Button 'add' is clicked
				$("#addTagger").click(function(){
					if(isNameCorrect() && !isTaggerRepeated()) appendTagger();
				});
	
			});
			
			//Checks if the selected tagger was already added to table
			function isTaggerRepeated(){
				var repeated = false;
				var val = $('#tagger').val();
				$('input[name^="lpTaggers"]').each(function() {	
					//Checking if the value was already input
					if( $(this).val() === val ){
						repeated = true;
						alert("Rotulador duplicado !");
						return false;
					}
				});
				return repeated;
			};	
			
			//Checks if the user name was typed correctly
			function isNameCorrect(){	
				
				var ok = $('#taggers option').filter(function() {
					return this.value == $('#tagger').val();
				}).data('tg');
				if(!ok) alert("Nome de usuário incorreto !");
				return ok;
				
			};			

			//Add the selected tagger to the table of taggers
			function appendTagger(){
				//Append a new row to the table of taggers
				$("#tblTaggers tbody").append(
					"<tr class='active'>" +  
						"<td>" + $('#tagger').val() + "</td>" +
						"<td class='sr-only'><input type='text' name='lpTaggers[]' readonly value='" + $('#tagger').val() + "'></td>" +
						"<td onClick='removeRow($(this))'><img src='images/ic_delete.png' ></td>" + 
					"</tr>"
				);
				$("#tagger").val("");
			};

			//Remove a tagger if the 'delete' image is clicked
			function removeRow(delete_icon){
				delete_icon.closest('tr').remove(); //Removing the row
			};
			
			//Submit form (taggers)
			function mySubmit(){
				if($('input[name^="lpTaggers"]').size() == 0){
					if(confirm("Tem certeza de que não quer adicionar rotuladores a este processo de rotulação ?")){
						$(location).attr('href','index.php');
					}
				}else{
					$("#taggersForm").submit(); 
				}
			};
		</script>
    </head>
	
    <body>
		<?php	showAlert(); ?>
		<?php if ((login_check($mysqli) == true) && ($_SESSION['user_role'] == 'processAdmin')  ) : ?>
			<header>
				<nav class="navbar navbar-default">
					<div class="container">
						<div class="navbar-header navbar-left">
							<a class="navbar-brand" href="index.php">Sistema de Apoio à Rotulação Manual de Textos</a>
						</div>
						<p class="navbar-text">
							--  Olá, <?php echo htmlentities($_SESSION['username']); ?>!
						</p>
						<div id="navbar" class="collapse navbar-collapse navbar-right">
							<ul class="nav navbar-nav">
								<li><a href="profile.php">Perfil</a></li>
								<?php if (($_SESSION['user_role'] == 'processAdmin')  ){
										echo 	'<li><a href="helpAdmin.php">Manual do administrador</a></li>
												<li><a href="help.php">Manual do usuário</a></li>';
									}else{
										echo '<li><a href="help.php">Manual</a></li>';
									}
								?>
								<li><a href="#about">Sobre</a></li>
								<li><a href="includes/logout.php">Sair</a></li>
							</ul>
						</div><!--/.nav-collapse -->
					</div>
				</nav>
			</header>
			<div align="center" >
				<h4 align="center">Selecione os rotuladores do processo de rotulação </h4>
				<div class="form-group">

					<input list="taggers" name="taggers_list" id="tagger" placeholder=" Escolha um rotulador">
						<datalist id="taggers">
							<?php printUsers($mysqli,$lpID); ?>
						</datalist>
				</div>
				<button type="button" class='btn btn-default' id="addTagger" >Adicionar</button>
				<button type="button" class='btn btn-default' id="submitButton" onClick="mySubmit()" >Concluir</button>
			</div>
			
			<div  class="container ">
				<div class='row'>
					<div class='col-md-2 col-centered text-center'>
						<form action = "<?php echo esc_url($_SERVER['PHP_SELF']) . $lpIDstr ; ?>" 
						id="taggersForm"  method="post">
							<table id="tblTaggers" class="table table-hover table-bordered ">
								<thead><tr class="active "><th class="text-center" colspan="3">Rotuladores</th></tr></thead>
								<tbody><?php printCurrentTaggers($mysqli,$lpID); ?></tbody>
							</table>
						</form>
					</div>
				</div>
			</div>
		<?php else : ?>
            <p>
                <span class="error">Você não está autorizado a visualizar esta página.</span> 
				<a href="index.php">Voltar</a>
            </p>
        <?php endif; ?>
		<footer class="footer">
			<div class="container">
				<p class="text-muted">
					Esta obra de <a xmlns:cc="http://creativecommons.org/ns#" href="http://labic.icmc.usp.br/" property="cc:attributionName" rel="cc:attributionURL">LABIC | ICMC-USP</a> 
					está licenciado com uma Licença <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Atribuição 4.0 Internacional</a>.				
					<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Licença Creative Commons" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/80x15.png" /></a>
				</p>
			</div>
		</footer>
    </body>
</html>
