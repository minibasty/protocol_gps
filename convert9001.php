
<?php
$str="24240090142181051839ff99553032333731362e3030302c412c313833382e313133322c4e2c30393930322e363832392c452c302e30302c3135352c3038303331392c2c2a30307c302e387c3239367c343430307c303030302c303030302c303030332c303241427c30323038303030303037443330304346303144377c31327c30303030383045437c3042424f0d0a";
$str2="2424008f142181051839ff99553037343132392e3030302c412c313833382e313135362c4e2c30393930322e363832362c452c302e30302c34332c3035303331392c2c2a33457c302e377c3239307c303430307c303030302c303030302c303030332c303241317c30323038303030303037443330304346414545457c30427c30303030313934467c304482940d0a";
error_reporting(~E_NOTICE);
// GPS DATA NON RFID

function convLat($lat,$hemisphere)
{
	$latStr = $lat;
	$latArr = explode('.', $latStr);
	$latitude_degrees=substr($latArr[0],0, -2);
	$latitude_degrees=intVal($latitude_degrees);
	$latitude_minutes=substr($latArr[0], -2);
	$latitude_minutes=$latitude_minutes.".".$latArr[1];
	$latitude_hemisphere = $hemisphere;
	$latitude = $latitude_degrees + ($latitude_minutes / 60 );
	// $latitude = $latitude_degrees + ((($latitude_minutes*60)+($latitude_secount))/3600);
	if ($Latitude_hemisphere == 'S'){
	    $Latitude = -$Latitude;
	}
	return $latitude;
}

function convLong($long, $hemisphere)
{
	$longStr = $long;
	$longArr = explode('.', $longStr);
	$longitude_degrees=substr($longArr[0],0, -2);
	$longitude_degrees=intVal($longitude_degrees);
	$longitude_minutes=substr($longArr[0], -2);
	$longitude_minutes=$longitude_minutes.".".$longArr[1];
	$longitude_hemisphere = $hemisphere;
	$longitude = $longitude_degrees + ($longitude_minutes / 60 );
	// $longitude = $longitude_degrees + ((($longitude_minutes*60)+($longitude_secount))/3600);
	if ($longitude_hemisphere == 'W')
	{
	    $longitude = -$longitude;
	}
	return $longitude;
}

function convDateTime($time, $date)
{
	$gmt=round($time);
	$gmt=str_pad($gmt,6,"0",STR_PAD_LEFT);
	$gmt=str_split($gmt,2);
	$gmt_hh=$gmt[0];
	$gmt_mm=$gmt[1];
	$gmt_ss=$gmt[2];
	$gmtNew=$gmt_hh.":".$gmt_mm.":".$gmt_ss; //เวลา Device
	$dateArr=str_split($date, 2);
	$date=date("$dateArr[2]-$dateArr[1]-$dateArr[0]");
	$datetimeStr=$date." ".$gmtNew;
	$newDateTime = date("Y-m-d H:i:s", strtotime($datetimeStr. '+7 hour')); //date
	$dateTime=$newDateTime;
	return $dateTime;
}

function convertData($dataStr)
{
	$str=$dataStr;
	$count=strlen($dataStr);
	$RFID="";
	if ($count>500)
	{
		$posrn=strpos($str, '7c25');
		// Login 2424
		$login=substr($str,0,4);
		// dataLangth 008f
		$langth=substr($str,4,4);
		// id 	142181051825
		$id=substr($str,8,12);
		// position ff
		$posff=strpos($str, 'ff')+2;
		// command
		$command=substr($str, $posff,4);	//9955
		// poscommand
		$poscommand=strpos($str, $command)+4;
		$checkstart=substr($str, $poscommand, 2);
		if ($checkstart=="75") {
			$pos75=strpos($str, $checkstart)+2;
			$startData=$pos75;
		}else {
			$startData=$poscommand;
		}

		$endData=substr($str, ($posrn));
		$countEndData=strlen($endData); //จำนวนข้อมูลส่วนท้าย
		$datahex=substr($str,$startData,(-$countEndData));
		$data=hex2bin($datahex);
		$dataArr=explode('|',$data);

		$gprmc=$dataArr[0];
		$gprmcArr=explode(',',$gprmc);

		// $gmtNew=convGmt($gprmcArr[0]); //time dev
		$status=$gprmcArr[1];	//status
		$latData=convLat($gprmcArr[2], $gprmcArr[3]);  //Latitude
		$longData=convLong($gprmcArr[4], $gprmcArr[5]);  //Longtitude
		$speed=($gprmcArr[6]*1.85);	//SPEED
		$heading=$gprmcArr[7];

		 //ตรวจสอบเวลา ว่ามีอักขระอื่นบนอยู่หรือไม่
		$timeArr=explode("P", $gprmcArr[0]);
		$countTime=(count($timeArr)-1);
		// $timeArr[$countTime]; ตัวแปรแสดงเวลาที่ไม่มีอักขระ

		$dateTime=convDateTime($timeArr[$countTime], $gprmcArr[8]);// datetime
		$countArr=count($gprmcArr);
		$data1="";
		$data2="";
		if ($countArr==10)
		{
			$checksum=$gprmcArr[9];	   //checksum
		}
		elseif($countArr==11)
		{
			//ถ้ามีข้เอมูล 11
			$data1=$gprmcArr[9];
			$checksum=$gprmcArr[10];	   //checksum
		}else{
			//ถ้ามีข้เอมูล 12
			$data1=$gprmcArr[9];
			$data2=$gprmcArr[10];
			$checksum=$gprmcArr[11];	   //checksum
		}

		$hdop=$dataArr[1];		//hdop
		$altitude=$dataArr[2];	//altitude
		$state=$dataArr[3];		//state

		$adArr=explode(",", $dataArr[4]);
		$ad1=$adArr[0];
		$ad2=$adArr[1];
		$ext_Ad=$adArr[2];
		$bat_Ad=$adArr[3];

		$baseId=$dataArr[5];
		$csq=$dataArr[6];
		$mileage=$dataArr[7];
		$satellite=$dataArr[8];
		$chk_rn=substr($datahex,-8);
		$chk=substr($chk_rn, 0 ,4);
		$rn=substr($chk_rn, 4 ,4);

		// /r/n
		$checksum=substr($str, $posrn, 4);
		$RFIDhex=substr($str, ($posrn+4));
		$RFIDstr=hex2bin($RFIDhex);
		$rfidSub=explode(" ",$RFIDstr);
		$rfidArr =array_filter($rfidSub);
		$nameSub = reset($rfidArr);
		$nameSub = explode("^",$nameSub);
		$nameSub = array_filter($nameSub);
		$nameSub = reset($nameSub);
		$nameSub_arry=explode("$", $nameSub);
		$rf_lname=$nameSub_arry[0];
		$rf_fname=$nameSub_arry[1];
		$rf_prefix=$nameSub_arry[2];
		$rf_fullname=$rf_prefix.$rf_fname." ".$rf_lname;
		$rfidSub1 = next($rfidArr);
		$rfidSub2 = next($rfidArr);
		$rfidSub3 = next($rfidArr);
		$rfidSub4 = next($rfidArr);
		$rfid_num=$rfidSub1." ".$rfidSub2." ".$rfidSub3." ".$rfidSub4;
	}
	elseif ($count > 200 AND $count < 500 ) {
		// Login 2424
		$login=substr($str,0,4);
		// dataLangth 008f
		$langth=substr($str,4,4);
		// id 	142181051825
		$id=substr($str,8,12);
		// position ff
		$posff=strpos($str, 'ff')+2;
			// command
		$command=substr($str, $posff,4);	//9955
		// poscommand
		$poscommand=strpos($str, $command)+4;
		$checkstart=substr($str, $poscommand, 2);

		if ($checkstart=="75") {
			$pos75=strpos($str, $checkstart)+2;
			$startData=$pos75;
		}else {
			$startData=$poscommand;
		}
		// data ==============================
		$datahex=substr($str,$startData, -8);
		$data=hex2bin($datahex);
		$dataArr=explode("|",$data);

		$gprmc=$dataArr[0];
		$gprmcArr=explode(",",$gprmc);

		// $gmtNew=convGmt($gprmcArr[0]); //time dev
		$status=$gprmcArr[1];	//status
		$latData=convLat($gprmcArr[2], $gprmcArr[3]);  //Latitude
		$longData=convLong($gprmcArr[4], $gprmcArr[5]);  //Longtitude
		$speed=($gprmcArr[6]*1.85);	//SPEED
		$heading=$gprmcArr[7];

		 //ตรวจสอบเวลา ว่ามีอักขระอื่นบนอยู่หรือไม่
		$timeData=substr($gprmcArr[0], -10);
		// $timeArr=explode("P", $gprmcArr[0]);
		// $countTime=(count($timeArr)-1);
		// $timeArr[$countTime]; ตัวแปรแสดงเวลาที่ไม่มีอักขระ

		$dateTime=convDateTime($timeData, $gprmcArr[8]);// datetime
		$countArr=count($gprmcArr);
		$data1="";
		$data2="";
		if ($countArr==10)
		{
			$checksum=$gprmcArr[9];	   //checksum
		}
		elseif($countArr==11)
		{
			//ถ้ามีข้เอมูล 11
			$data1=$gprmcArr[9];
			$checksum=$gprmcArr[10];	   //checksum
		}else{
			//ถ้ามีข้เอมูล 12
			$data1=$gprmcArr[9];
			$data2=$gprmcArr[10];
			$checksum=$gprmcArr[11];	   //checksum
		}

		$chk_rn=substr($datahex,-8);
		$chk=substr($chk_rn, 0 ,4);
		$rn=substr($chk_rn, 4 ,4);

		// END data ==============================

		$hdop=$dataArr[1];		//hdop
		$altitude=$dataArr[2];	//altitude
		$state=$dataArr[3];		//state

		$adArr=explode(",", $dataArr[4]);
		$ad1=$adArr[0];
		$ad2=$adArr[1];
		$ext_Ad=$adArr[2];
		$bat_Ad=$adArr[3];

		$baseId=$dataArr[5];
		$csq=$dataArr[6];
		$mileage=$dataArr[7];
		$satellite=$dataArr[8];
	}else{
		return false;
	}

	$dataArray = array(
		'login' => $login,
		'id' => $id,
		'status' => $status,
		'datetime' => $dateTime,
		'lat'=> $latData,
		'long' => $longData,
		'speed' => $speed,
		'heading' => $heading,
		'data1' => $data1,
		'data2' => $data2,
		'checksum' => $checksum,
		'hdop' => $hdop,
		'altitude' => $altitude,
		'state' => $state,
		'ad1' => $ad1,
		'ad2' => $ad2,
		'ext_Ad' => $ext_Ad,
		'bat_Ad' => $bat_Ad,
		'baseId' => $baseId,
		'csq' => $csq,
		'mileage' => $mileage,
		'satellite' => $satellite,
		'rfid_name'	=> $rf_fullname,
		'rfid_number' => $rfid_num
		);
	return $dataArray;
}

	print_r(convertData($str));
?>
