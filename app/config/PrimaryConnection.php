<?php
	
		$logDir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'logger'.DIRECTORY_SEPARATOR;
		if (file_exists($logDir)) {
			foreach (glob($logDir.'/*') as $file) {
				unlink($file);
			}
		}	

    session_start();

    if (!empty($_REQUEST['primary'])){
        $primary=$_REQUEST['primary'];
        $_SESSION['primary'] = $primary;		
        header("Location: /app/view/secondary_db.php");
    }

?>