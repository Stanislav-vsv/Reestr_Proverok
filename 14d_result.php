<?php

define('ORA_USER','monitoring');
define('ORA_PASS','ware22mon');
define('TNS_NAME','VM4SUP01_DWMON');
$connection = @ora_logon(ORA_USER.'@'.TNS_NAME, ORA_PASS) or die("Oracle Connect Error ". ora_error());    // создаем конект  
$open_connection = ora_open($connection);        // открываем соединение

//функция получения заголовков проверки        
        function GetHeder($num){       
            global $open_connection;
            
            $query = "  select COLUMN_NAME 
                        from MONITORING.DQ_HEADINGS_CONTROL
                        where LIST_ID = $num 
                     ";
                        
            ora_parse($open_connection, $query, 0);         
        	ora_exec($open_connection);
            
           while(ora_fetch_into($open_connection, $row))
            { 
                $data[] = $row[0];
            }  
            return $data;                 
        }   
   

//функция получения результатов проверки        
        function GetData($num){       
            global $open_connection;
            
            //получаем текст запроса
            $query = "  
                 SELECT 'select ' || REPLACE (sql_text, '#')
                       || ' from MONITORING.DQ_LIST_CONTROL, MONITORING.DQ_DETAIL where DQ_DETAIL.DQ_CONTROL_UK= DQ_LIST_CONTROL.LIST_ID and DQ_LIST_CONTROL.LIST_ID = '
                       || $num
                       || ' and REPORT_TIME > SYSDATE-14 ORDER BY REPORT_TIME DESC'
                          AS sql_text
                  FROM (    SELECT rn,
                                   cnt,
                                   list_id,
                                   SYS_CONNECT_BY_PATH (sql_text, '#') sql_text
                              FROM (SELECT rn,
                                           cnt,
                                           list_id,
                                           sql_text,
                                           LAG (rn, 1, 0) OVER (PARTITION BY list_id ORDER BY rn)
                                              ld_rn
                                      FROM (SELECT TO_NUMBER (
                                                      list_id
                                                      || ROW_NUMBER ()
                                                         OVER (PARTITION BY list_id
                                                               ORDER BY sort_num)) rn,
                                                   list_id,
                                                   TO_NUMBER (
                                                      list_id
                                                      || COUNT (1) OVER (PARTITION BY list_id))
                                                      cnt,
                                                   CASE
                                                      WHEN sort_num =
                                                              MIN (sort_num)
                                                                 OVER (PARTITION BY list_id)
                                                      THEN
                                                            table_name
                                                         || '.'
                                                         || field_name
                                                      ELSE
                                                            ','
                                                         || table_name
                                                         || '.'
                                                         || field_name
                                                   END
                                                      sql_text
                                              FROM DQ_HEADINGS_CONTROL
                                             WHERE rep_flag = 1
                                             and LIST_ID = $num ))
                        START WITH rn = TO_NUMBER (list_id || 1)
                        CONNECT BY PRIOR rn = ld_rn)
                 WHERE rn = cnt
                     ";
                        
            ora_parse($open_connection, $query, 0);         
        	ora_exec($open_connection);
            
           while(ora_fetch_into($open_connection, $row))
            { 
                $query = $row[0];
            }            
            
            // выполняем запрос
            $exec_query = "$query";            
            ora_parse($open_connection, $exec_query, 0);         
        	ora_exec($open_connection); 
            
            while(ora_fetch_into($open_connection, $row))
            { 
                $data[] = $row;
            } 
            
            return $data;               
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
    <title>Реестр проверок</title>
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link href="style_reestr.css" type="text/css" rel="stylesheet">    
</head>
    
    <body>

<?php

$list_id = $_GET['list_id'];

//получаем заголовки проверки из списка, для <th> таблицы
$headers = GetHeder($list_id); // массив заголовков

//получаем последние результаты проверки по ее номеру
$details = GetData($list_id); // массив результатов проверки

echo "<h3 style='margin-top: 30px; text-align: center; padding: 5px; color: #FFF; background-color: #F08080;'>№".$list_id." ".$details[0][0].". </h3>";  

echo "<h4 style='padding: 5px; text-align: center; color: #FFF; background-color: #F08080;'>Информация за последние 14 дней:</h4>";  
    
echo "<table style='width: 80%; border: 1px solid #EEB4B4; border-collapse: collapse; margin: 20px auto 30px auto; font-size: 14px;'>";

    array_shift($headers);
    array_pop($headers); 
    
    echo '<tr>'; // открываем строку с заголовками th
    foreach($headers as $head){
        echo "<th style='border: 1px solid #EEB4B4; padding: 2px 7px 2px 7px; border-collapse: collapse; background-color: #F08080; color: #fff;  border-color: #fff; font-size: 14px;'>".$head."</th>"; 
    }
    echo '</tr>'; // конец строки th
        
    foreach($details as $line){ //перебираем все строки результатов
        echo '<tr>'; // открываем строку с данными td
        array_shift($line);
        array_pop($line); 
        foreach($line as $data){ //перебираем все столбцы строки 
                    
           if($data == 'N'){
            echo "<td style='color: #228B22; border: 1px solid #EEB4B4; border-collapse: collapse; text-align: center; padding: 2px 10px 2px 10px;  margin: 20px 0 50px 0px; font-size: 13px;'>";
            echo 'НЕТ';
            }
            elseif($data == 'Y'){
                echo "<td style='color: #DC143C; border: 1px solid #EEB4B4; border-collapse: collapse; text-align: center; padding: 2px 10px 2px 10px;  margin: 20px 0 50px 0px; font-size: 13px;'>";
                echo 'ДА';
            }
            else{
                echo "<td style='border: 1px solid #EEB4B4; border-collapse: collapse; text-align: center; padding: 2px 10px 2px 10px;  margin: 20px 0 50px 0px; font-size: 13px;'>";
                echo $data; 
            
            }
            echo '</td>';
        }  
        echo '</tr>';  // конец строки td
    } 
echo "</table>";

echo "</body>";

?>

