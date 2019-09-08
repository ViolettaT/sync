<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>DB Sync </title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <link rel="stylesheet" media="screen" href="/app/view/css/styles.css" />				
    </head>
    <body class="center">
        <div class="db">
            <p align="center">Synchronize DB</p>
            <img src="app/view/images/db.png" class="image_db">
            </br>
            <div class="header">
                Primary DB
            </div>
            <hr class="line"/>
            <div>
                <span class="required_notification">* Denotes Required Field</span>
                </br>
                <form class="db_form" action="/app/config/PrimaryConnection.php" method="post" name="primary_db">
                    <div class="main_form" id="primary_db">
                        <ul>
                            <li>
                                <label for="db_name">DB name:</label>
                                <input type="text" name="primary[dbname]" id="dbname" placeholder="db name" required />
                            </li>
                            <li>
                                <label for="user">User name:</label>
                                <input type="text" name="primary[user]" id="user" placeholder="user name" required />
                            </li>
                            <li>
                                <label for="password">User password:</label>
                                <input type="password" name="primary[password]" id="password" placeholder="password" />
                            </li>
                            <li>
                                <label for="host">Host:</label>
                                <input type="text" name="primary[host]" id="host" placeholder="host" required />

                            </li>
							<li>
                                <label for="port">Port:</label>
                                <input type="text" name="primary[port]" id="port" placeholder="port" required />

                            </li>
                            <li>
                                <label for="driver">Driver:</label>
                                <select name="primary[driver]" id="driver">
                                    <option value="pdo_mysql">pdo_mysql</option>
                                    <option value="pdo_sqlsrv">pdo_sqlsrv</option>
                                    <option value="pdo_pgsql">pdo_pgsql</option>
                                </select>
                            </li>
                        </ul>
                    </div>	
					<div class="bt" align="right">
					<a id="savepr">Save Params</a>
					</div>
                    <hr class="line"/>
                    <div align="right">						
                        <button type="submit" class="button"> Next </button>
                    </div>
                </form>
            </div>
        </div>
    </body>
	
	<script>			
		$('#savepr').click( function() {						
		var $data = {};
		$('#primary_db').find ('input, select').each(function() {  
			$data[this.id] = $(this).val();
			localStorage.setItem(this.id, $data[this.id]);
		});		
		});	
	
		$('#primary_db').find ('input, select').each(function() {  
			$(this).val(localStorage.getItem(this.id));	
		});	
	</script>
</html>