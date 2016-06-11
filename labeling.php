<?php include_once 'includes/labeling.inc.php'; ?>

<!DOCTYPE html>
<html lang="pt">
    <head> 
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="author" content="Rafael Paravia">
		<title>Rotular</title>
		
         <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<!-- Bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		
		<link rel="stylesheet" href="styles/main.css" />
		<link rel="stylesheet" href="styles/sticky-footer-navbar.css" />
		
		<script type="text/Javascript">
			$(document).ready(function(){
				$("#btnAddLabel").click(function(){
					var append = true;
					$('input[name^="lpSugLabels"]').each(function() {	
						//Checking if the value was already input
						if( $(this).val() === $("#txtLabel").val() ){
							append = false;
							return false;
						}
					});
					if(append)appendLabel();
					else alert("Rótulo duplicado !");
				});
			});
			function appendLabel(){
				//Append a new row to the table of labels
				$("#tblLabelList").append(
					"<tr>" + 
						"<td><input type='text' name='lpSugLabels[]' readonly value='" + $("#txtLabel").val() + "'></td>" +
						"<td><img src='images/ic_delete.png' onClick='removeRow($(this))'></td>" + 
					"</tr>"
				);
				$("#txtLabel").val("");
			};
			function removeRow(delete_icon){
				delete_icon.closest('tr').remove(); //Removing the row
			};
			function validateForm(btnSubmit){
				var submit = true;
				if(btnSubmit==='jump'){
					if(!confirm("Esse documento ficará sem rótulo!")){
						submit = false;
					}
				}else if(btnSubmit==='next'){
					if(	($('input[name^="lpLabels"]:checked').size() == 0) && 
						($('input[name^="lpSugLabels"]').size() == 0) ){
							alert("Pelo menos um rótulo deve ser escolhido!");
							submit = false;
					}
				}
				if(submit){
					var input = $("<input>")
					.attr("type", "hidden")
					.attr("name", "btnSubmit").val(btnSubmit);
					$('#labelingForm').append($(input)).submit();
				}
			};				
		
		</script>
		
    </head>
    <body style='background-color:white;'>   
		<?php showAlert(); ?>
		<?php if (login_check($mysqli) == true ) : ?>
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
			
			<h2 align="center">
				<?php echo "Processo de rotulação : ". $_SESSION['cur_lpName']?>
			</h2>
			<?php showProgressBar()?>
			<h3 align="center">
				<?php echo "Documento : " . stripslashes($docName)?>
			</h3>
			<div align="center">
				<textarea rows="10" cols="80"  readonly ><?php echo stripslashes($docText)?>
				</textarea>
			</div>
				
			
			<form action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>" 
			method ="post" id="labelingForm" name="labelingForm" >
				<table class = 'table table-bordered' align="center" style="width:550px">
					<?php showLabels($mysqli); ?>
					<?php showInputOfSuggestions(); ?>
				</table>
				<div align="center">
					<input type="button" class='btn btn-default' onclick="validateForm('back')" value="Documento Anterior">
					<input type="button" class='btn btn-default' onclick="validateForm('jump')" value="Pular Documento">
					<?php showButtonNext() ?>					
				</div>
				<div align="center" style="padding-top:10px" >
					<input type="button" class='btn btn-default btn-sm' 
					<?php echo "onclick=\"location.href='guideline.php?lpID=" . (string)$_SESSION['cur_lpID'] . "';\"" ?>				 
					value="Voltar à página de instruções" />
				</div>
			</form>
		<?php else : ?>
            <p>
                <span class="error">Você não está autorizado a visualizar esta página.</span> 
				Primeiro você deve realizar o <a href="index.php">login</a>.
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
