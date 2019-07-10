<?php

define('ORA_USER','monitoring');
define('ORA_PASS','ware22mon');
define('TNS_NAME','VM4SUP01_DWMON');


$connection = @ora_logon(ORA_USER.'@'.TNS_NAME, ORA_PASS) or die("Oracle Connect Error ". ora_error());    // создаем конект  
				
$open_connection = ora_open($connection);        // открываем соединение


?>