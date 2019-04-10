<?php


error_reporting(E_ALL);

/* Include function convert data */
require('convert9001.php');
// Include function insert to database
require('insert.php');
// Include connect Database
require('config_protocol.php');

/* Set timezone  */
date_default_timezone_set("Asia/Bangkok");
/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting as it comes in. */
ob_implicit_flush();

$address = '217.175.242.25';

$port = 9001;

// create a streaming socket, of type TCP/IP
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

socket_bind($sock, $address, $port);

socket_listen($sock);

// create a list of all the clients that will be connected to us..
// add the listening socket to this list
$clients = array($sock);

while (true)
{
    // create a copy, so $clients doesn't get modified by socket_select()
    $read = $clients;
    $write = null;
    $except = null;

    // get a list of all the clients that have data to be read from
    // if there are no clients with data, go to next iteration
    if (socket_select($read, $write, $except, 0) < 1)
        continue;

    // check if there is a client trying to connect
    if (in_array($sock, $read))
    {
        $clients[] = $newsock = socket_accept($sock);

        socket_write($newsock, "There are ".(count($clients) - 1)." client(s) connected to the server\n");

        socket_getpeername($newsock, $ip, $port);
        echo "New client connected: {$ip} : {$port}\n";

        $key = array_search($sock, $read);
        unset($read[$key]);
    }

    // loop through all the clients that have data to read from
    foreach ($read as $read_sock)
    {
        // read until newline or 1024 bytes
        // socket_read while show errors when the client is disconnected, so silence the error messages
        $data = @socket_read($read_sock, 4096, PHP_BINARY_READ);

        // check if the client is disconnected
        if ($data === false)
        {
            // remove client for $clients array
            $key = array_search($read_sock, $clients);
            unset($clients[$key]);
            echo "client disconnected.\n";
            continue;
        }


        $dataMaster=$data;
        $data = trim($data);
        $dataHex= bin2hex($dataMaster);
        if (!empty($data))
        {

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
        //
        // echo "เข้ามาแล้ว";
        // เช็คจำนวนชุดข้อมูลที่ส่งมาในรอบนั้นๆ หากมีมากกว่า 1 ชุดให้แบ่งและเพิ่มข้อมูลให้ครบตามใจนวน
        // $count=strlen($dataHex);
        $dataSplit=explode("0d0a",$dataHex);
        $countStr=(count($dataSplit)-1);
        $iStr=0;
        // $checklogin="";
        // $resultArr=array();
        // print_r($dataSplit);
        while ($iStr < $countStr) {
            $hex=$dataSplit[$iStr].'0d0a';
            $convData= convertData($hex);
            $iStr++;

            if ($convData) {
                $datelog=date('Y-m-d');
                $servertime=date('Y-m-d H:i:s');
                $devicetime=$convData['datetime'];
                if ($convData['status']=='A') {
                    $status=1;
                }else {
                    $status=0;
                }
                $latitude=$convData['lat'];
                $longitude=$convData['long'];
                $speed=$convData['speed'];
                $altitude=$convData['altitude'];
                $course=$convData['heading'];
                $state=$convData['state'];
		$hdop =$convData['hdop'];
                $arrayOut = array(
                    
                    'ad1' => $convData['ad1'],
                    'ad2' => $convData['ad2'],
                    'ext_Ad' => $convData['ext_Ad'],
                    'bat_Ad' => $convData['bat_Ad'],
                    'baseId' => $convData['baseId'],
                    'csq' => $convData['csq'],
                    'mileage' => $convData['mileage'],
                    'satellite' => $convData['satellite'],
                );
                $attributes= json_encode($arrayOut);
                $rfid_name=$convData['rfid_name'];
                $rfid_number=$convData['rfid_number'];

                $select="SELECT `devi_id` , `devi_imei` FROM devices WHERE devi_imei='$convData[id]'";
                $rs_select=$conn->query($select);
                $r_select=$rs_select->fetch_assoc();
                if ($r_select) {
                    // ถ้าเจอ imei ที่ protocol ส่งมา
                    $insert="INSERT INTO positions VALUES ('','$r_select[devi_id]','$devicetime','$servertime','', '$altitude' ,'$latitude', '$longitude', '$speed','$course','$hdop','$attributes','$status','$state')";
                    $rs_insert=$conn->query($insert);
                    $last_id=$conn->insert_id;

                    if ($rs_insert) {
                        // ถ้าเพิ่มข้อมูลตำแหน่งสำเร็จ
                        // echo "Insert Sccess \n";
                        $update="UPDATE devices SET id_position='$last_id', last_update='$devicetime'";

                        $checkrfid=substr($state, 0,1);
                        $checkkey=substr($state, 1,1);
                        // หากมีข้อมูลบัตรส่งมา (มีการรูดบัตร)
                        if ($rfid_name != '' AND $rfid_number != '') {
                            if ($checkrfid == '6')
                            {
                                $update .= ", rfid_name='$rfid_name', rfid_number='$rfid_number'";

                                //INSERT login driver Value 1
                                $insert_login="INSERT INTO login_drivers VALUES ('','$devicetime', '$rfid_name', '$rfid_number', '$r_select[devi_id]', '$latitude', '$longitude', '$speed', '1')";
                                $login_show="Loin Success";
                                $checklogin='4';
                                $checkName=$rfid_name;
                                $checkNumber=$rfid_number;


                            }elseif($checkrfid == '2')
                            {
                                $update .= ", rfid_name='', rfid_number=''";
                                $insert_login="INSERT INTO login_drivers VALUES ('','$devicetime', '$rfid_name', '$rfid_number', '$r_select[devi_id]', '$latitude', '$longitude', '$speed', '0')";

                            }
                            $rs_insert_login=$conn->query($insert_login);
                            //INSERT logout driver Value 0
                            // $insert_login="INSERT INTO login_drivers VALUES ('','$devicetime', '$rfid_name', '$rfid_number', '$r_select[devi_id]', '$latitude', '$longitude', '$speed', '0')";
                            // $login_show="Logout Success";
                        }else{
                            if ($checkrfid == '2')
                            {
                                $update .= ", rfid_name='', rfid_number=''";
                                if ($checkkey=="0" AND $checklogin=="4") {
                                    $insert_login="INSERT INTO login_drivers VALUES ('','$devicetime', '$checkName', '$checkNumber', '$r_select[devi_id]', '$latitude', '$longitude', '$speed', '0')";
                                    $rs_insert_login=$conn->query($insert_login);
                                    if ($rs_insert_login) {
                                        $checklogin='0';
                                    }
                                }
                            }
                        }
                        echo "----".$checklogin."---\n";
                        $update.=" WHERE devi_id='$r_select[devi_id]'";

                        $rs_update=$conn->query($update);


                        // if ($rs_insert_login) {
                        //     // Check Insert
                        //     echo $login_show."\n";
                        // }else{
                        //     echo $insert_login;
                        // }

                        // if ($rs_update) {
                        //     // echo "Update Sccess \n";
                        // }else{
                        //     echo "Update Fail \n";
                        // }

                        $devicetimeArr = explode(" ", $devicetime);
                        $dateMark = $devicetimeArr[0];
                        $timeMark = $devicetimeArr[1];
                    if ($timeMark <= '00:10:00') {
                        // echo "////////////";
                        $checkMark="SELECT posit_mark_id, posit_mark_date, device_code FROM positions_mark WHERE posit_mark_date = '$dateMark' AND device_code = '$r_select[devi_id]'";
                        $rs_checkMark=$conn->query($checkMark);
                        $num_checkmark=$rs_checkMark->num_rows;
                        if ($num_checkmark=="") {
                            $insert_mark = "INSERT INTO positions_mark VALUES ('', '$dateMark', '$last_id','', '$r_select[devi_id]')";
                            $rs_insert_mark=$conn->query($insert_mark);
                        }

                    }
                    if ($timeMark > '00:10:00') {
                        $update_mark = "UPDATE positions_mark SET posit_mark_end = '$last_id' WHERE posit_mark_date='$dateMark' AND device_code='$r_select[devi_id]'";
                        $rs_update_mark=$conn->query($update_mark);

                        $check_endupdate="SELECT posit_mark_id FROM positions_mark WHERE posit_mark_date = '$dateMark' AND device_code = '$r_select[devi_id]'";
                        $check_endupdate_query=$conn->query($check_endupdate);
                        $check_endupdate_num=$check_endupdate_query->num_rows;

                        if ($check_endupdate_num=='0') {

                            echo $insert_mark = "INSERT INTO positions_mark VALUES ('', '$dateMark', '','$last_id', '$r_select[devi_id]')";
                            $rs_insert_mark=$conn->query($insert_mark);
                        }
                    }

                    }else{
                        echo "Insert Fail \n";
                    }

                }else{
                    echo "ไ่มพบ Imei \n";
                }
                $file = fopen("/var/www/html/protocol/save/log_$convData[datetime].txt","a");
                fwrite($file,$last_id." | ".$convData['datetime']." | ".$dataHex."\n");
            }else{
                echo "| ข้อมูลไม่ตรงกัน \n";
            }
            // echo $update;
            // echo "| จำนวน".$countHex." | \n";
            print_r($convData);
        }
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        echo "Time Process------ ".$totaltime." sec\n";
            // send this to all the clients in the $clients array (except the first one, which is a listening socket)
            foreach ($clients as $send_sock)
            {
                if ($send_sock == $sock)
                    continue;
            } // end of broadcast foreach

        }

    } // end of reading foreach
}

// close the listening socket
socket_close($sock);
 ?>
