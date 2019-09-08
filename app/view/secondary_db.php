<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DB Sync </title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <link rel="stylesheet" media="screen" href="/app/view/css/styles.css" >
    <script type='text/javascript'>	

		function addDB(id){
            display = document.getElementById(id).style.display;
            if((display=='none')&&(document.getElementById('member').value!='')){
                document.getElementById(id).style.display='block';
            }
			
            var number = document.getElementById("member").value;
            var container = document.getElementById("container");
			var ln = container.getElementsByTagName('div').length;
			var count=ln;
            for (i=0;i<number;i++){	
                var div = document.createElement('div');
                div.setAttribute("class", "db_form your-form-selector");
                div.id="db"+i+count;

                var ul0 = document.createElement("ul");
                var li0 = document.createElement("li");
                var lb0 = document.createElement("label");
                lb0.appendChild(document.createTextNode("Connection name:"));
                lb0.setAttribute("for", "connection");
                var select0 = document.createElement("select");
                select0.name = i+"[name]";
				select0.id = "name"+i+count;
                var op01 = document.createElement("option");
                op01.value = "mysql";
                op01.appendChild(document.createTextNode("mysql"));
                var op02 = document.createElement("option");
                op02.value = "mssql";
                op02.appendChild(document.createTextNode("mssql"));
                var op03 = document.createElement("option");
                op03.value = "postgresql";
                op03.appendChild(document.createTextNode("postgresql"));
                select0.appendChild(op01);
                select0.appendChild(op02);
                select0.appendChild(op03);
                li0.appendChild(lb0);
                li0.appendChild(select0);
                ul0.appendChild(li0);
                div.appendChild(ul0);

                var ul1 = document.createElement("ul");
                var li1 = document.createElement("li");
                var lb1 = document.createElement("label");
                lb1.appendChild(document.createTextNode("DB name:"));
                lb1.setAttribute("for", "name");
                var db_name = document.createElement("input");
                db_name.required = "required";
                db_name.type = "text";
                db_name.name = i+"[params][dbname]";
				db_name.id="db_name"+i+count;
                db_name.placeholder = "db name";
                li1.appendChild(lb1);
                li1.appendChild(db_name);
                ul1.appendChild(li1);
                div.appendChild(ul1);

                var ul2 = document.createElement("ul");
                var li2 = document.createElement("li");
                var lb2 = document.createElement("label");
                lb2.appendChild(document.createTextNode("User name:"));
                lb2.setAttribute("for", "user");
                var user = document.createElement("input");
                user.required = "required";
                user.type = "text";
                user.name = i+"[params][user]";
				user.id = "user"+i+count;
                user.placeholder = "user name";
                li2.appendChild(lb2);
                li2.appendChild(user);
                ul2.appendChild(li2);
                div.appendChild(ul2);

                var ul3 = document.createElement("ul");
                var li3 = document.createElement("li");
                var lb3 = document.createElement("label");
                lb3.appendChild(document.createTextNode("User password:"));
                lb3.setAttribute("for", "password");
                var ps = document.createElement("input");
                ps.type = "password";
                ps.name = i+"[params][password]";
				ps.id = "password"+i+count;
                ps.placeholder = "password";
                li3.appendChild(lb3);
                li3.appendChild(ps);
                ul3.appendChild(li3);
                div.appendChild(ul3);


                var ul4 = document.createElement("ul");
                var li4 = document.createElement("li");
                var lb4 = document.createElement("label");
                lb4.appendChild(document.createTextNode("Host:"));
                lb4.setAttribute("for", "host");
                var host = document.createElement("input");
                host.required = "required";
                host.type = "text";
                host.name = i+"[params][host]";
				host.id = "host"+i+count;
                host.placeholder = "host";
                li4.appendChild(lb4);
                li4.appendChild(host);
                ul4.appendChild(li4);
                div.appendChild(ul4);
				
				var ul5 = document.createElement("ul");
                var li5 = document.createElement("li");
                var lb5 = document.createElement("label");
                lb5.appendChild(document.createTextNode("Port:"));
                lb5.setAttribute("for", "port");
                var port = document.createElement("input");
                port.required = "required";
                port.type = "text";
                port.name = i+"[params][port]";
				port.id = "port"+i+count;
                port.placeholder = "port";
                li5.appendChild(lb5);
                li5.appendChild(port);
                ul5.appendChild(li5);
                div.appendChild(ul5);

                var ul6 = document.createElement("ul");
                var li6 = document.createElement("li");
                var lb6 = document.createElement("label");
                lb6.appendChild(document.createTextNode("Driver:"));
                lb6.setAttribute("for", "driver");
                var select = document.createElement("select");
                select.name = i+"[params][driver]";
				select.id = "driver"+i+count;
                var op1 = document.createElement("option");
                op1.value = "pdo_mysql";
                op1.appendChild(document.createTextNode("pdo_mysql"));
                var op2 = document.createElement("option");
                op2.value = "pdo_sqlsrv";
                op2.appendChild(document.createTextNode("pdo_sqlsrv"));
                var op3 = document.createElement("option");
                op3.value = "pdo_pgsql";
                op3.appendChild(document.createTextNode("pdo_pgsql"));
                li6.appendChild(lb6);
                select.appendChild(op1);
                select.appendChild(op2);
                select.appendChild(op3);
                li6.appendChild(select);
                ul6.appendChild(li6);
                div.appendChild(ul6);
                container.appendChild(div);					
            }			
        }
        
		function removeDB() {
            var number = document.getElementById("nb").value;
            for (i=0;i<number;i++) {
                var element =  document.getElementById("container");
                element.removeChild(element.lastChild);				
            }			
        }



    </script>
</head>
    <body class="center">
        <div class="db">
            <p align="center">Synchronize DB</p>
            <img src="images/db.png" class="image_db">
            </br>
            <div class="header">
                Secondary DB
            </div>
            <hr class="line"/>
            <form method="post" action="/app/config/connect.php" name="secondary_db">
                <div id="form" class="form" style="display: none;">
                    <span class="required_notification">* Denotes Required Field</span>
                    </br>   
					<div id="container" class="prokrutka"></div>						
                </div>
                <div class="bt">
					<button id="save">Save Params</button>
					<button id="clear">Clear All</button></br>
                    <label for="connections">Number:</label>
                    <input type="text" id="member" />
                    <a href="#" id="addDB" onclick="addDB('form'); document.getElementById('member').value = ''">Fill connection</a>					
                    <input type="text" id="nb" />
                    <a href="#" id="removeDB" onclick="removeDB(); document.getElementById('nb').value = ''">Remove connection</a>					
                </div>							
                <hr class="line"/>
                <div align="right">
					<a href="http://massdb/" id="back">Back home</a>
                    <button type="submit">synchronize</button>
                </div>				
            </form>					
        </div>
    </body>
	<script>
		var container = document.getElementById("container");
		var ln = container.getElementsByTagName('div').length;
		display = document.getElementById('form').style.display;
		if(display=='block'){
			document.getElementById('form').style.display='none';
		} else {
			document.getElementById('form').style.display='block';
		}
		
		$('#save').click( function() {			
			var container = $('#container').html();
			localStorage.setItem('container', container);			
			var $data = {};
			$('#container').find ('input, select').each(function() {  
				$data[this.id] = $(this).val();
				localStorage.setItem(this.id, $data[this.id]);
			});
			return false;
		});

		if(localStorage.getItem('container')) {
		$('#container').html(localStorage.getItem('container'));
		}	
	
		$('#container').find ('input, select').each(function() {  
			$(this).val(localStorage.getItem(this.id));	
		});
		
		
		$('#clear').click( function() {
		localStorage.removeItem('container');
		$('#container').find ('input, select').each(function() {  
			localStorage.removeItem(this.id);	
		});		
		location.reload();
		return false;
		});		
			
	</script>
</html>