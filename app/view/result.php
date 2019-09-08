<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Result Sync</title>
        <link rel="stylesheet" media="screen" href="/app/view/css/styles.css" >
        		<script type="text/javascript">
        			function openbox(id){
        				display = document.getElementById(id).style.display;
        				if(display=='none'){
        					document.getElementById(id).style.display='block';
        				}else{
        					document.getElementById(id).style.display='none';
        					}
        			}
        		</script>
    </head>
    <body class="center">
        <div class="db_result">
            <p align="center">Synchronize DB</p>
            <img src="images/db.png" class="image_db">
            </br></br>			
			<div class="header">Database synchronization result:</div>	
			</br>
			<div class="result">
				<?php
					$logDir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'logger'.DIRECTORY_SEPARATOR;
					define('FILENAME', $logDir.'error.log');
					if (file_exists(FILENAME)&& file_get_contents(FILENAME)):
				?>
					<div class="header" style="color: red;">
						<a onclick="openbox('error'); return false" class="a2">
							details
						</a>
						<img src="images/invalid.png">
						ERROR
					</div>
					<form id="error" class="prokrutka2" style="display: none;">
						<pre><?php include($logDir.'error.log'); ?></pre>
					</form>
				<?php endif; ?>

				<?php foreach (glob($logDir.'/*') as $file): ?>
					<?php
						$fileName = basename($file, ".log");
						if (file_exists($file) && file_get_contents($file) && ($fileName!='error')):
					?>
							<div class="header" style="color: #5cd053; ">
								<a onclick="openbox('<?php echo $fileName ?>'); return false" class="a2">
									details
								</a>
								<img src="images/valid.png">
								<?php echo $fileName ?> SUCCESS
							</div>
							<form id="<?php echo $fileName ?>" class="prokrutka2" style="display: none;">
								<pre><?php include($file); ?></pre>
							</form>
					<?php endif; ?>
				<?php endforeach; ?>


				<?php if(!glob($logDir.'/*')): ?>
                    <div class="header" style="color:  #5cd053; margin-top: 20px;" align="center">
                    	<img src="images/valid.png">
                    	    Databases were synchronized initially
                    </div>
                    </br>
                <?php endif; ?>				
			</div>
			<hr class="line"/>
			<div align="right">
					<a href="http://massdb/" id="back">Back home</a>                    
            </div>
        </div>
    </body>
</html>