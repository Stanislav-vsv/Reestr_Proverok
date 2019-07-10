<?php 
require 'connection.php'; 
session_start();

/*echo '<pre>';
print_r($_POST);
echo '</pre>';*/

// обработка события нажатия кнопки просмотра результатов
if(isset($_POST['submit_result'])){
    if(isset($_POST['check_num'])){ //если есть массив с номерами выбранных проверок
        $_SESSION['check_num_det'] = $_POST['check_num'];
        header("Location: show_detail.php");
        exit;
    }
    else{
       $_SESSION['res'] = "<p style = 'color: red'> Надо выбрать проверку или проверки </p>";        
       header("Location: reestr-proverok.php");
       exit(); 
    }
}
// обработка события нажатия кнопки просмотра sql-запроса    
if(isset($_POST['submit_sql'])){
    if(isset($_POST['check_num'])){ //если есть массив с номерами выбранных проверок
        $_SESSION['check_num_sql'] = $_POST['check_num']; 
        header("Location: show_detail.php");
        exit; 
    }
    else{
       $_SESSION['res'] = "<p style = 'color: red'> Надо выбрать проверку </p>";        
       header("Location: reestr-proverok.php");
       exit(); 
    }   
}

 //функция получения названия проверки
    function GetName($num){
        global $open_connection;
        
        //получаем текст запроса
        $query = "  SELECT REV_NAME
                    FROM MONITORING.DQ_LIST_CONTROL
                    WHERE LIST_ID = $num 
                 ";
                 
        ora_parse($open_connection, $query, 0);         
    	ora_exec($open_connection);
        
        while(ora_fetch_into($open_connection, $row))
        { 
            $data = $row[0];
        }  
        return $data;   
    }
    
//выводим результаты проверки если есть сессионная переменная $_SESSION['check_num_det']
//которая создается при условии наличия массива с номерами проверок
if(isset($_SESSION['check_num_det'])){  ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
            <title>Просмотр</title>
            <link href="style_reestr.css" type="text/css" rel="stylesheet">
        </head>            
        <body>            
          <div class="wrapper">
          <a class="back" href="reestr-proverok.php">&lt;&lt;&nbsp;&nbsp;Назад</a>
       <?php 
        //функция получения заголовков проверки        
        function GetHeder($num){       
            global $open_connection;
            
            $query = "  SELECT column_name 
                        FROM  MONITORING.DQ_HEADINGS_CONTROL
                        WHERE list_id = $num 
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
       || ') '
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
              
        // получаем массив с номерами отмеченных проверок
        $check_num = $_SESSION['check_num_det'];
        
        //получаем для каждой проверки заголовок и данные
        foreach($check_num as $num){
            $headers = GetHeder($num); 
            $details = GetData($num); 
            $name = GetName($num);            
            
               //выводим на экран табличку с результатами проверки
                echo '<h3>Проверка №'.$num.'&nbsp;&nbsp;&nbsp;'.$name.'</h3>';
                if(isset($details)){
                    echo '<table class="detail">';
                    echo '<tr>';
                    array_shift($headers);           
                    foreach($headers as $head){
                        echo '<th>'.$head.'</th>';
                    }
                    echo '</tr>';  
                     
                    foreach($details as $datas){
                       array_shift($datas); 
                                 
                     echo '<tr>';    
                        foreach($datas as $data){                            
                            if($data == 'N'){
                                echo '<td class="width_9">';
                                echo 'НЕТ';
                            }
                            elseif($data == 'Y'){
                                echo '<td class="width_8">';
                                echo 'ДА';
                            }
                            else{
                                echo '<td>';
                                echo $data;
                            }                     
                            echo '</td>';
                        }
                     echo '</tr>';
                     }            
                     echo '</table>'; 
                }
                else{
                    echo "<p class='mess'> Резултаты этой проверки приходят в виде рассылки или она НЕ работала больше двух недель</p>";
                } 
          }
}

//выводим sql-запрос если есть сессионная переменная $_SESSION['check_num_sql']
//которая создается при условии наличия массива с номером проверки
if($_SESSION['check_num_sql']){ ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
            <title>Просмотр</title>
            <link href="style_reestr.css" type="text/css" rel="stylesheet">
        </head>            
        <body>            
          <div class="wrapper">
          <a class="back" href="reestr-proverok.php">&lt;&lt;&nbsp;&nbsp;Назад</a>
    <?php 
    
        // получаем массив с номерами отмеченных проверок
        $check_num = $_SESSION['check_num_sql'];
        $name = GetName($check_num[0]);
                
        echo "<h2>Запрос проверки № $check_num[0] &nbsp; &nbsp; $name </h2>";
			
				
		$zapros = " SELECT SQL_QUERY
                    FROM MONITORING.DQ_LIST_CONTROL
                    WHERE LIST_ID = $check_num[0] " ;      // сам запрос, присваивается переменной $zapros 
		
		ora_parse($open_connection, $zapros, 0);         // парсится запрос, функция возвращает true/false
		
		ora_exec($open_connection);                      // выполняем запрос в базе oracle 
		
		ora_fetch_into($open_connection, $row);    
		                                           
		foreach ($row as $current)
		echo nl2br($current); 
}
       
?> 

</div>        
</body>
</html>
    
<?php 
    ora_logoff($connection); 
?>    
   

