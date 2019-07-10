<?php

define('ORA_USER','monitoring');
define('ORA_PASS','ware22mon');
define('TNS_NAME','VM4SUP01_DWMON');
$connection = @ora_logon(ORA_USER.'@'.TNS_NAME, ORA_PASS) or die("Oracle Connect Error ". ora_error());    // создаем конект  
$open_connection = ora_open($connection);        // открываем соединение

//функция получения массива названий разделов
function GetOrder($rep_system){       
            global $open_connection;
            
            $query = "  
                        select GROUP_ORDER, GROUP_NAME
                        from MONITORING.DQ_LIST_CONTROL
                        where REP_SYSTEM = '$rep_system'
                        group by GROUP_ORDER, GROUP_NAME
                        order by GROUP_ORDER 
                     ";
                        
            ora_parse($open_connection, $query, 0);         
        	ora_exec($open_connection);
            
           while(ora_fetch_into($open_connection, $row))
            { 
                $data[] = $row;
            }  
            return $data;                 
} 

//функция получения массива номеров проверок данного раздела
function GetControlOrder($num, $rep_system){       
            global $open_connection;
            
            $query = "  
                        select LIST_ORDER, LIST_ID
                        from MONITORING.DQ_LIST_CONTROL
                        where REP_SYSTEM = '$rep_system'
                        and GROUP_ORDER = $num
                        order by LIST_ORDER
                     ";
                        
            ora_parse($open_connection, $query, 0);         
        	ora_exec($open_connection);
            
           while(ora_fetch_into($open_connection, $row))
            { 
                $data[] = $row;
            }  
            return $data;                 
} 


//функция получения заголовков проверки        
        function GetHeder($num){       
            global $open_connection;
            
            $query = "  select COLUMN_NAME 
                        from MONITORING.DQ_HEADINGS_CONTROL
                        where LIST_ID = $num 
                        order by SORT_NUM
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
                       || ' and REPORT_TIME in (SELECT max(REPORT_TIME) FROM MONITORING.DQ_DETAIL WHERE DQ_CONTROL_UK ='
                       || $num
                       || ')'
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
    <title>Актуальные результаты</title>
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link href="style_reestr.css" type="text/css" rel="stylesheet">    
</head>
    
    <body>

<?php

$rep_system = $_GET['rep_system'];

//получаем спиок разделов с номерами по порядку
$order_name = GetOrder($rep_system); // массив разделов

foreach($order_name as $order){ 
    
    echo "<h3 style='margin: 20px; padding: 5px; color: #FFF; background-color: #F08080;'>".$order[0].". ".$order[1]." </h3>";
        
    // получаем список проверок соответствующих данному разделу с номерами по порядку
    $controlOrder = GetControlOrder($order[0], $rep_system);  // массив проверок  
   
    //получаем заголовки первой проверки из списка, для <th> таблицы
    $headers = GetHeder($controlOrder[0][1]); // массив заголовков
    
    echo "<table style='width: 95%; border: 1px solid #EEB4B4; border-collapse: collapse; margin: 20px; font-size: 14px;'>";
        echo '<tr>';
        echo "<th style='border: 1px solid #EEB4B4; padding: 2px 7px 2px 7px; border-collapse: collapse; background-color: #F08080; color: #fff;  border-color: #fff; font-size: 14px;'>№</th>";
        foreach($headers as $head){
            echo "<th style='border: 1px solid #EEB4B4; padding: 2px 7px 2px 7px; border-collapse: collapse; background-color: #F08080; color: #fff;  border-color: #fff; font-size: 14px;'>".$head."</th>"; 
        }
        echo '</tr>';   
   
    foreach($controlOrder as $control){ // перебераем все проверки раздела
               
        //получаем последние результаты проверки по ее номеру
        $details = GetData($control[1]); // массив результатов проверки
        
        if($details){
            
           foreach($details as $row){  // перебераем строки массива результатов
            
             echo '<tr>';    
             echo "<td style='color: #228B22; border: 1px solid #EEB4B4; border-collapse: collapse; text-align: center; padding: 2px 10px 2px 10px;  margin: 20px 0 50px 0px; font-size: 13px;'>".$control[1].'</td>';    
            
                foreach($row as $data){ // перебераем столбцы строки результатов          
                    
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
                echo '</tr>';
            }  
        }
        else {  
             echo "<p style='color: #4169E1;'>№$control[1] Нет результатов проверки</p>";
             /*echo '№'.$control[1].' Нет результатов проверки';*/
        }
               
    }    
    echo "</table>";
}

echo "</body>";

?>

























