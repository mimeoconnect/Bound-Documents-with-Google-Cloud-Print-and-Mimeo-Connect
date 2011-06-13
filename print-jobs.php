<?php 
//Actually Register the Printer
$client = new Zend_Gdata_HttpClient();  
$client = $client->setClientLoginToken($_SESSION['Client_Login_Token']);

$client->setHeaders('Authorization','GoogleLogin auth='.$_SESSION['Client_Login_Token']); 
$client->setHeaders('X-CloudPrint-Proxy','Mimeo'); 		

//GCP Services - Register
$client->setUri('http://www.google.com/cloudprint/interface/fetch');

$PrinterQuery = "SELECT User_Printer_ID FROM user_printer WHERE Printer_ID = '" . $Printer_ID . "' and G_Email = '" . $G_Email . "'";
//echo $PrinterQuery . "<br />";
$PrinterResult = mysql_query($PrinterQuery) or die('Query failed: ' . mysql_error());

if($PrinterResult && mysql_num_rows($PrinterResult))
{						
$Printer = mysql_fetch_assoc($PrinterResult);	
$User_Printer_ID = $Printer['User_Printer_ID'];
}		

//echo "Fetching: " . $User_Printer_ID . "<br />";
$client->setParameterPost('printerid', $User_Printer_ID);

$response = $client->request(Zend_Http_Client::POST);

$JobResponse = json_decode($response->getBody());

//var_dump($JobResponse);

//$Success = $JobResponse->success;
//echo "Success: " . $Success . "<br />";

if(isset($JobResponse->jobs))
{
// Printer Information
$Jobs = $JobResponse->jobs;	

foreach($Jobs as $Job) 
	{
	
	$Job_ID = $Job->id;
	$Job_Title = $Job->title;
	$Job_Content_Type = $Job->contentType;
	$File_URL = $Job->fileUrl;
	$Job_NumberPages = $Job->numberOfPages;
	$Job_Status = $Job->status;
	
	$client->setUri($File_URL);
	$FileResponse = $client->request(Zend_Http_Client::POST);
	$FileContent = $FileResponse->getBody();											
	
	$Save_Filename = 'files/' . $Job_ID . ".pdf";
	
	//echo "Saving " . $Save_Filename . "<br />";
	
    $fh = fopen($Save_Filename, "w");
    if($fh==false)
        die("unable to create file");
    fputs($fh,$FileContent,strlen($FileContent));
    fclose ($fh);	

    $Print_Job_URL = "http://googlecloudprinters.laneworks.net/" . $Save_Filename;
												
	//echo "Title: " .  $Job_Title . "<br />";
	//echo "Content Tyype: " .  $Job_Content_Type . "<br />";
	//echo "Status: " . $Job_Status . "<br />";
	//echo "# of Pages: " . $Job_NumberPages . "<br />";
	
	//echo "File URL: " . $File_URL . "<br />";
	//echo "Print Job URL: " . $Print_Job_URL . "<br />";
	
	$PostCheckQuery = "SELECT ID FROM user_printer_job WHERE Printer_ID = '" . $Printer_ID . "' AND G_Email = '" . $G_Email . "' AND Job_ID = '" . $Job_ID . "'";
	//echo $PostCheckQuery . "<br />";
	$CheckResult = mysql_query($PostCheckQuery) or die('Query failed: ' . mysql_error());
	
	if($CheckResult && mysql_num_rows($CheckResult))
		{						
		$CheckResult = mysql_fetch_assoc($CheckResult);	
		$Message = $CheckResult;
		}
	else
		{
		$AddedTimeStamp = date('Y-m-d H:i:s');
		
		//Insert this post
		$query = "INSERT INTO user_printer_job(
			Title,
			Content_Type,
			Status,
			NumberofPages,
			File_URL,
			Job_URL,
			Printer_ID,
			G_Email,
			AddedTimeStamp,
			Job_ID
			) VALUES(
			'" . $Job_Title . "',
			'" . $Job_Content_Type . "',
			'" . $Job_Status . "',
			'" . $Job_NumberPages . "',
			'" . $File_URL . "',
			'" . $Print_Job_URL . "',
			'" . $Printer_ID . "',
			'" . $G_Email . "',
			'" . $AddedTimeStamp . "',
			'" . $Job_ID . "'
			)";
		//echo $query;
		mysql_query($query) or die('Query failed: ' . mysql_error());	
		}				
	
	}
}								
?>