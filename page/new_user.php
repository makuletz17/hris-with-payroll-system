<?php

$program_code = 8;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "enroll":
        $name = $_POST["name"];
        $lvl = $_POST["lvl"];
        enroll_user($name,$lvl);
    break;
    case "enable-disable":
        enable_disable(substr($_POST["recid"], 3));
    break;
    case "reset-account":
        reset_account(substr($_POST["recid"], 3));
    break;
    case "get-user":
        get_user(substr($_POST["user_id"], 3));
    break;
    case "update":
        $name = $_POST["name"];
        $lvl = $_POST["lvl"];
        $user_id = $_POST["user_id"];
        update_user($name,$lvl,$user_id);
    break;
    case "get-user-info":
        get_user_info($_SESSION['name']);
    break;
    case "update-profile":
        $acc_id = $_POST["acc_id"];
        $uid = $_POST["uid"];
        $name = $_POST["name"];
        $user_pass = $_POST["user_pass"];
        $user_pass1 = $_POST["user_pass1"];
        $user_pass2 = $_POST["user_pass2"];
        update_profile($acc_id,$uid,$name,$user_pass,$user_pass1,$user_pass2);
    break;
}

function update_profile($acc_id,$uid,$name,$user_pass,$user_pass1,$user_pass2){
    global $db, $db_hris;

    $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:aid");
    $user->execute(array(":aid" => $acc_id));
    if ($user->rowCount()) {
        $user_data = $user->fetch(PDO::FETCH_ASSOC);
        if (md5($user_pass) === $user_data["user_password"]) {
            if ($user_pass1 !== "" AND md5($user_pass1) === md5($user_pass2)) {
                $pass = md5($user_pass2);
                $set = $db->prepare("UPDATE $db_hris.`_user` SET `user_password`=:pass WHERE `account_id`=:aid");
                $set->execute(array(":pass" => $pass, ":aid" => $acc_id));
                if ($set->rowCount()) {
                    echo json_encode(array("status" => "success", "message" => "Password Changed!"));
                }
            }else{
                echo json_encode(array("status" => "error", "message" => "Password does not match!"));
            }
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Out of focus, Please try again later!", "e" => $user->errorInfo()));
    }
}

function get_user_info($name) {
    global $db, $db_hris;

    $get_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id`=:name");
    $get_user->execute(array(":name" => $name));
    if ($get_user->rowCount()){
        while ($user_data = $get_user->fetch(PDO::FETCH_ASSOC)) {
            $recid = $user_data["account_id"];
            $uid = $user_data["user_id"];
            $fname = $user_data["name"];
            $level = $user_data["user_level"];
            $last_login = $user_data["last_login_time"];
            if($level=='10'){
                $lvl = 'Administrator';
            }else if($level=='9'){
                $lvl = 'System Owner';
            }else if($level=='8'){
                $lvl = 'Admin';
            }else{
                $lvl = "User Level-".$level;
            }
        }
    }
    echo json_encode(array("status" => "success", "recid" => $recid, "uid" => $uid, "fname" => $fname, "last_login" => $last_login, "lvl" => $lvl));       
}


function enroll_user($name,$lvl) {
    global $db, $db_hris;

    $account_id = get_new_account_id();

    $new_user = $db->prepare("INSERT INTO $db_hris.`_user` (`name`, `account_id`, `user_level`,`granted_by`,`station_id`) VALUES (:name, :acc_id, :lvl, :by, :ip)");

    $new_user->execute(array(":name" => $name, ":acc_id" => $account_id, ":lvl" => $lvl, ":by" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));

    echo json_encode(array("status" => "success"));
}

function enable_disable($recid) {
    global $db, $db_hris;

    $a = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no AND `user_id`!=:name");
    $a->execute(array(":no" => $recid, ":name"=> $_SESSION['name']));
    if ($a->rowCount()){
        $a_data = $a->fetch(PDO::FETCH_ASSOC);
        if($a_data["is_active"]){
            $is_active = 0;
        }else{
            $is_active = 1;
        }
        $a = $db->prepare("UPDATE $db_hris.`_user` SET `is_active`=:actv, `granted_by`=:uid, `station_id`=:ip WHERE `user_no`=:no");
        $a->execute(array(":actv" => $is_active, ":no" => $recid, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));
        if ($a->rowCount()){
            echo json_encode(array("status" => "success"));
        }
    }else{
        echo json_encode(array("status" => "hold"));
    }
}

function reset_account($recid) {
    global $db, $db_hris;

    $reset = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no");
    $reset->execute(array(":no" => $recid));
    if ($reset->rowCount()){

        $reset_account = $db->prepare("UPDATE $db_hris.`_user` SET `user_id`=:user_id, `user_password`=:pwd, `is_active`=:actv, `granted_by`=:uid, `station_id`=:ip WHERE `user_no`=:no");
        $reset_account->execute(array(":user_id" => "", ":pwd" => "", ":actv" => 0, ":no" => $recid, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));

        echo json_encode(array("status" => "success"));
    }
}

function update_user($name,$lvl,$user_id) {
    global $db, $db_hris;

    $user_update = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no");
    $user_update->execute(array(":no" => $user_id));
    if ($user_update->rowCount()){

        $update = $db->prepare("UPDATE $db_hris.`_user` SET `name`=:name, `user_level`=:lvl WHERE `user_no`=:no");
        $update->execute(array(":name" => $name, ":lvl" => $lvl, ":no" => $user_id));

        echo json_encode(array("status" => "success"));
    }
}

function get_new_account_id() {
    global $db, $hris;
    $user = $db->prepare("SELECT * FROM $hris.`_user` WHERE `account_id`=:id");
    $value = 100000000;
    $count = 0;
    while ($count < $value) {
        $random = substr(number_format(RAND(1, $value) + $value, 0, '.', ''), -6);
        $user->execute(array(":id" => $random));
        if ($user->rowCount()) {
            if ($count++ > $value) {
                $random = 0;
                break;
            }
        } else {
            break;
        }
    }
    $id = date("ym") . $random;
    return $id;
}

function get_user($user_id) {
    global $db, $db_hris;

    $get_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:user_no");
    $get_user->execute(array(":user_no" => $user_id));
    if ($get_user->rowCount()){
        while ($user_data = $get_user->fetch(PDO::FETCH_ASSOC)) {
            $user_no = $user_data["user_no"];
            $user_name = $user_data["name"];
            $user_level = $user_data["user_level"];
            if($user_level=='9'){
                $lvl = 'Admin';
            }else if($user_level=='8'){
                $lvl = 'Supervisor';
            }else if($user_level=='3'){
                $lvl = 'User Level 3';
            }else if($user_level=='2'){
                $lvl = 'User Level 2';
            }else{
                $lvl = 'User Level 1';
            }
        }
    }
    echo json_encode(array("status" => "success", "id" => $user_no, "name" => $user_name, "level" => $user_level));       
}