<?php
ini_set('max_execution_time', 2000);
$time_start = microtime(true);
system('DATE=`date +"%Y-%m-%d"`;FILE="/generate/Agent_Performance_$DATE.csv";
    wget http://192.168.0.10/vicidial/AST_agent_performance_detail.php --post-data "query_date=$DATE&end_date=$DATE&group[]=Bima&user_group[]=Bima&shift=ALL&file_download=1" --http-user=[user] --http-passwd=[password] -O $FILE');
$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes otherwise seconds
$execution_time = ($time_end - $time_start)/60;
if($execution_time > 0)
{
	//////////////USER GROUP//////////////
	$userGroup = 'bima';
	//////////////USER GROUP//////////////
	$datenow   = date("Y-m-d");
	//////////////DATABASE CONNECTION//////////////
	$host = ''; //HOST NAME.
    $db_name = ''; //Database Name
    $db_username = ''; //Database Username
    $db_password = ''; //Database Password
    try
    {
        $conn = new PDO('mysql:host='. $host .';dbname='.$db_name, $db_username, $db_password);
    }
    catch (PDOException $e)
    {
        exit('Error Connecting To DataBase');
    }
    //////////////DATABASE CONNECTION//////////////
    $DATE = date('Y-m-d');	
    $FILE="/generate/Agent_Performance_$DATE.csv";
    $stmt = $conn->query("SELECT DISTINCT `user` FROM `vicidial_agent_log` WHERE `user_group`='$userGroup' AND LEFT(event_time,10) = '$datenow'");
		$agent_count = $stmt->rowCount();
		$stmt->execute();
		//$url = '/root/sample_mail/test.txt';
		require_once 'phpmailer/PHPMailerAutoload.php';
		$sender = 'test@testdomain.com';
		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 587;
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = 'tls';
		$mail->Username = $sender; //email sender
		$mail->Password = 'emailpass'; //email password 
		$mail->setFrom($sender, 'IT Report'); //email sender with name caption

		/*Agent Performance report will address to these emails*/
		$mail->addAddress('');
		/*Agent Performance report will address to these emails*/


		$mail->addStringAttachment(file_get_contents($FILE), $FILE);

		/*Agent Performance report will Copy these emails*/
		$mail->AddCC('');
		/*Agent Performance report will Copy these emails*/
		

		$mail->addReplyTo($sender); //email sender

		$mail->isHTML(true);
		$mail->Subject = 'Daily Calls Report ['.strtoupper($userGroup).'] - '.$datenow;

		$body = "<table>
		
				<tr style='background:black;color:white;font-family:sans-serif;'>
					<th style='padding:2px;'>Extension</th>
					<th style='padding:2px;'>less than 1 min</th>
					<th style='padding:2px;'>1 to 5 min</th>
					<th style='padding:2px;'>5 to 30 min</th>  
					<th style='padding:2px;'>30 min and above</th>
					<th style='padding:2px;'>Live Calls</th>
					
					<th style='padding:2px;'>Unestablish Calls</th>
					
					<th style='padding:2px;'>Total Calls</th>
					<!--
					<th style='padding:2px;'>Total Pause Time</th>
					<th style='padding:2px;'>Total Talk Time</th>
					<th style='padding:2px;'>DNC</th>
					<th style='padding:2px;'>Call Back</th>
					<th style='padding:2px;'>Not Interested</th>
					
					<th style='padding:2px;'>Sale</th>
					-->
			</tr>";


		while($user = $stmt->fetch(PDO::FETCH_ASSOC))
			{
				$user_alias = $user['user'];
				$select_user = $conn->prepare("SELECT `full_name` FROM `vicidial_users` WHERE `user`='$user_alias'");
				$select_user->execute();
				$user = $select_user->fetch(PDO::FETCH_ASSOC);
				
				//select report
				$select_report = $conn->query("SELECT 
				SUM(talk_sec BETWEEN 1 AND 59) AS 'T0',
				SUM(talk_sec BETWEEN 60 AND 300) AS 'T1',
				SUM(talk_sec BETWEEN 301 AND 1800) AS 'T2',
				SUM(talk_sec > 1800) AS 'T3',
				SUM(pause_sec) AS 'PAUSE',
				SUM(talk_sec) AS 'TALK',
				SUM(talk_sec > 0) AS 'T4',
				(COUNT(talk_sec) - SUM(talk_sec > 0)) AS 'UC',
				COUNT(talk_sec) AS 'T5',
				SUM(status = 'DNC') AS 'DNC',
				SUM(status = 'CALLBK') AS 'CALLBK',
				SUM(status = 'NI') AS 'NI',
				SUM(status = 'SALE') AS 'BOOKED'
				FROM `vicidial_agent_log` WHERE LEFT(event_time,10) = '$datenow' AND `user` = '$user_alias'");
				$select_report->execute();
				$report = $select_report->fetch(PDO::FETCH_ASSOC);
				
				$body .= "<tr style='font-family:verdana;font-size:11px;'>
							<td style='background:#C6DEFF;color:black;'>".$user['full_name']."</td>           
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T0']."</td>        
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T1']."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T2']."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T3']."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T4']."</td>
							
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['UC']."</td>
							
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['T5']."</td>
							<!--
							<td style='background:#C6DEFF;color:black;text-align:center;'>".gmdate("H:i:s", $report['PAUSE'])."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".gmdate("H:i:s", $report['TALK'])."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['DNC']."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['CALLBK']."</td>
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['NI']."</td>
							
							<td style='background:#C6DEFF;color:black;text-align:center;'>".$report['BOOKED']."</td>
							-->
						</tr>";
					

			}
				$body .= '<tr><th colspan="14" style="background: black;color:rgb(241,160,17);font-family: sans-serif;text-align: right;padding:2px;">Iconcept Contact Solutions &copy; '.date("Y").' Vicidial Call Report</th></tr>';
				$body .= "</table>";
				$body .= "<br />";

				$vici_report = $conn->query("SELECT 
				SUM(talk_sec BETWEEN 1 AND 59) AS 'TOT0',
				SUM(talk_sec BETWEEN 60 AND 300) AS 'TOT1',
				SUM(talk_sec BETWEEN 301 AND 1800) AS 'TOT2',
				SUM(talk_sec > 1800) AS 'TOT3',
				SUM(talk_sec > 0) AS 'TOT4',
				COUNT(talk_sec) AS 'TOT5'
				FROM `vicidial_agent_log` WHERE LEFT(event_time,10) = '$datenow' AND `user_group`='$userGroup'");
				$vici_report->execute();
				$report_total = $vici_report->fetch(PDO::FETCH_ASSOC);

				$body .= '<table>
							<tr>
								<td>Total calls less than 1 minute</td>
								<td></td>
								<td>'.$report_total['TOT0'].'</td>
							</tr>
							<tr>
								<td>Total calls between 1 and 5 minutes</td>
								<td></td>
								<td>'.$report_total['TOT1'].'</td>
							</tr>
							<tr>
								<td>Total calls between 5 and 30 minutes</td>
								<td></td>
								<td>'.$report_total['TOT2'].'</td>
							</tr>
							<tr>
								<td>Total calls 30 minutes and above</td>
								<td></td>
								<td>'.$report_total['TOT3'].'</td>
							</tr>
							<tr>
								<td>Total live for the day</td>
								<td></td>
								<td>'.$report_total['TOT4'].'</td>
							</tr>
							<tr>
								<td>Total calls for the day</td>
								<td></td>	
								<td>'.$report_total['TOT5'].'</td>
							</tr>
							<tr>
								<td>Total Agents for the day</td>
								<td></td>	
								<td>'.$agent_count.'</td>
							</tr>
						  </table>
						  <p>
						 ';
					$body .= '<b>Note:</b> Agent Performance Report for today whos present on work. Should you have any question, please leave a message on us.';
					$body .= '<br>';
					$body .= '<pre><b>CALLS</b> = Total number of calls sent to the user.
					<b>TIME</b> = Total time of these (PAUSE + WAIT + TALK + DISPO).
					<b>PAUSE</b> = Amount of time being paused in related to call handling.
					AVG means Average so everything -AVG is for example amount of PAUSE-time divided by total number of calls: (PAUSE / CALLS = PAUSAVG)
					<b>WAIT</b> = Time the agent waits for a call.
					<b>TALK</b> = Time the agent talks to a customer or is in dead state (DEAD + CUSTOMER).
					<b>DISPO</b> = Time the agent uses at the disposition screen (where the agent picks NI, SALE etc).
					<b>DEAD</b> = Time the agent is in a call after the customer has hung up.
					<b>CUSTOMER</b> = Time the agent is in a live call with a customer.
					And the rest is just System Statuses that the agent picked and how many, to find out what they means then head over to Admin -> System Statuses.
					- Next table is Pause Codes.
					<b>TOTAL</b> = Total time on the system (WAIT + TALK + DISPO + PAUSE).
					<b>NONPAUSE</b> = Everything except pause (WAIT + TALK + DISPO).
					<b>PAUSE</b> = Only Pause.
					- The last table is pause codes and their time (like Agent Time Detail).
					<b>LOGIN</b> = The pause code when going from login directly to pause.
					<b>LAGGED</b> = The time the agent had some network problem or similar.
					<b>ANDIAL</b> = This pause code triggers if the agent been on dispo screen for longer than 1000 seconds.
					and empty is undefined pause code.</pre>';
					$body .= '<br />';
		$mail->Body = $body;
		if($report_total['TOT5'] > 0)
		{
			if(!$mail->send())
			{
				
				$result = 'Something went wrong!';

			}
			else
			{
				$result = 'Report has been sent!';
			}
		}
		else
		{
			$result = 'No Dial has been made today!';
		}
}
?>