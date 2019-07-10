<?php require 'connection.php'; ?>
<?php session_start(); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
    <title>Реестр проверок</title>
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <link href="style_reestr.css" type="text/css" rel="stylesheet">    
</head>
    
    <body>
    
        <div class="wrapper">    
<?php 

function GetInfo($rep_system){   //функция получения информации по всем проверкам указанной системы   
    global $open_connection;
    
    $query = " 
        WITH t1
             AS (SELECT LIST_ID, REV_NAME, REP_SYSTEM
                   FROM MONITORING.DQ_LIST_CONTROL
                  WHERE REP_SYSTEM IN ($rep_system))
          SELECT DISTINCT dq_control_uk,
                          NVL (result_char5, 'null'),
                          NVL (result_char6, 'null') SYSTEM,
                          t.REV_NAME,
                          TO_CHAR (report_time, 'DD.MM.YY  HH24:MI'),
                          error_flag,
                          rule_description
            FROM    MONITORING.DQ_DETAIL
                 JOIN
                    (SELECT t1.LIST_ID, t1.REV_NAME, t1.REP_SYSTEM FROM t1) t
                 ON dq_control_uk = LIST_ID
           WHERE xk IN (  SELECT MAX (xk)
                            FROM MONITORING.DQ_DETAIL
                        GROUP BY dq_control_uk)
        ORDER BY SYSTEM DESC ";
                
    ora_parse($open_connection, $query, 0);         
	ora_exec($open_connection);
    
   while(ora_fetch_into($open_connection, $row))
    { 
        $data[] = $row;
    }  
    return $data;                 
}  

function GetErrorNum(){  //функция получения номеров проверок с ошибками    
    global $open_connection;
    
    $query = "  SELECT DISTINCT(m.dq_control_uk)
                 FROM MONITORING.DQ_DETAIL m
                 JOIN
                 (
                     SELECT DQ_CONTROL_UK, MAX(report_time) as report_time
                     FROM MONITORING.DQ_DETAIL
                     GROUP BY DQ_CONTROL_UK 
                 ) n
                 ON m.DQ_CONTROL_UK = n.DQ_CONTROL_UK
                 AND m.report_time =  n.report_time 
                 WHERE error_flag = 'Y'
                ";
                
    ora_parse($open_connection, $query, 0);         
	ora_exec($open_connection);       
   
   while(ora_fetch_into($open_connection, $row))
    { 
        $data[] = $row[0];
    }  
    return $data;                 
}  

/*
echo '<pre>';
print_r($error);
echo '</pre>';*/
         
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  
        foreach($_GET as $system){
            $rep_system[] = "'".$system."'";
            }
                        
            if($_GET){
            $_SESSION['rep_system'] = implode(',', $rep_system);
            }     

        echo '<h1>Реестр проверок '.$_SESSION['rep_system'].'</h1>';
        echo $_SESSION['res']; 
?>        
        <form action="show_detail.php" method="POST" name="forma_det"> 
        <input class="button" type="submit" name="submit_result" value="Показать результаты"/> 
        <input class="button" type="submit" name="submit_sql" value="Показать SQL-запрос"/> 
        <table>
            <tr class="first_row">
                <th></th>
                <th>№</th>
                <th>Таблица</th>
                <th>Система</th>
                <th>Описание</th>
                <th>Время работы</th>
                <th style="font-size: 13px;">Ошибка</th>
                <th>Детали</br><span>(за 14 дней)</span></th>
            </tr>  
            <?php
            //$rep_system = $_GET['rep_system'];
            
            $result = GetInfo($_SESSION['rep_system']); 
            $error = GetErrorNum();    
            foreach($result as $row){ ?>             
            <tr>                
                <td><input type="checkbox" value="<?php echo $row[0];?>" name="check_num[]"/></td>                
                <td class="width_1"><?php echo $row[0];?></td>
                <td class="width_2"><?php echo $row[1];?></td>
                <td class="width_3"><?php echo $row[2];?></td>
                <td class="width_4"><?php echo $row[3];?></td>
                <td class="width_5"><?php echo $row[4];?></td>
                <?php 
                    $flag = 0;
                    if($error){
                        foreach($error as $err){
                        if($row[0] == $err){
                            $flag = 1;
                            }
                        } 
                    }
                    else{
                        $flag = 0;
                    }
                       
                        if($flag == 1){
                            echo '<td class="width_8">';
                            echo 'да';
                            echo '</td>'; 
                            }
                        else{ 
                            echo '<td class="width_9">';
                            echo 'нет'; 
                            echo '</td>';
                            } 
                     ?>                                  
                 <td class="width_6"><?php echo $row[6];?></td>
            </tr> 
            <?php } ?>
        </table>        
        </form> 
        </div>
            
    </body>
    
<?php 
    ora_logoff($connection); 
    unset($_SESSION['res'], $_SESSION['check_num_det'], $_SESSION['check_num_sql']);
    //session_destroy();
?>   
   
</html>


