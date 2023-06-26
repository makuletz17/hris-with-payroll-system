<?php

$program_code = 1;
require_once('../common/functions.php');
include '../common/master_journal.php';

switch ($_POST["cmd"]) {
    case "get-position":
        get_positions();
    break;
    case "get-store":
        get_store();
    break;
    case "get-group":
        get_group();
    break;
    case "save-data":
        $record = json_decode(html_entity_decode($_POST["record"]), true);
        $record["bdate"] = (new DateTime($record["bday"]))->format("Y-m-d");
        $record["edate"] = (new DateTime($record["edate"]))->format("Y-m-d");
        $fname = $_POST["first_name"];
        $lname = $_POST["last_name"];
        $mname = $_POST["middle_name"];
        $bday1 = $_POST["bday"];
        $edate = (new DateTime($_POST["edate"]))->format("Y-m-d");
        $gender = $_POST["gender"];
        $cs = $_POST["status"];
        $position = $_POST["position"];
        $c_address = $_POST["c_address"];
        $p_address = $_POST["p_address"];
        $contact = $_POST["contact"];
        $store = $_POST["store"];
        $atm = $_POST["atm"];
        $emp_no = $_POST["emp_no"];
        $tin = $_POST["tin"];
        $sss = $_POST["sss"];
        $love = $_POST["love"];
        $phealth = $_POST["phealth"];
        $remarks = $_POST["remarks"];
        $ctax1 = $_POST["ctax"];
        if($ctax1 == 'true'){ $ctax='1';}else{$ctax='0';}
        $csss1 = $_POST["csss"];
        if($csss1 == 'true'){ $csss='1';}else{$csss='0';}
        $clove1 = $_POST["clove"];
        if($clove1 == 'true'){ $clove='1';}else{$clove='0';}
        $cph1 = $_POST["cph"];
        if($cph1 == 'true'){ $cph='1';}else{$cph='0';}
        $function = $_POST["save_update"];
        $bday = new DateTime($bday1);
        $now = new DateTime();
        $int = $now->diff($bday); //check if employee is above 18
        if (number_format($int->y, 0, '.', '') < number_format(18, 0)) {
            echo json_encode(array("status" => "error", "message" => "Invalid birthdate.  Employee must at least 18 years old. Age is " . $int->y, "age" => $int->y));
        }else{
            $bday = $_POST["bday"];
            if($function == 'add'){
                save_data($emp_no,$fname,$lname,$mname,$bday,$edate,$gender,$cs,$position,$c_address,$p_address,$contact,$store,$atm,$tin,$sss,$love,$phealth,$remarks,$ctax,$csss,$clove,$cph);
            }else{
                update_data($emp_no,$fname,$lname,$mname,$bday,$edate,$gender,$cs,$position,$c_address,$p_address,$contact,$store,$atm,$tin,$sss,$love,$phealth,$remarks,$ctax,$csss,$clove,$cph);
            }
        }
    break;
    case "get-emp-data":
        $emp_no = substr($_POST["emp_no"], 3);
        get_emp_data($emp_no);
    break;
    case "save-rate":
        $rate = $_POST["rate"];
        $inctv = $_POST["incentive"];
        $emp_no = $_POST["emp_no"];
        $total_pay = $_POST["total"];
        $rm = $_POST["rm"];
        save_update_rate($rate,$inctv,$emp_no,$total_pay,$rm);
    break;
    case "save-status":
        $emp_status = $_POST["emp_status"];
        $remarks = $_POST["remarks"];
        $emp_no = $_POST["emp_no"];
        save_update_status($emp_status,$remarks,$emp_no);
    break;
    case "new-vl":
        new_vl(array("recid" => $_POST["recid"], "date" => $_POST["date"], "days" => number_format($_POST["days"] * 1 + 0, 0, '.', '')));
    break;
    case "cancel-vl":
        cancel_vl(array("recid" => $_POST["recid"], "date" => $_POST["date"], "remark" => $_POST["remark"], "no" => $_POST["no"]));
    break;
    case "get-journal":
        $pin = $_POST["pin"];
        $df = (new DateTime($_POST["df"]))->format("Y-m-d");
        $dt = (new DateTime($_POST["dt"]))->format("Y-m-d");
        $records = get_journal($df, $dt, $pin);
        echo json_encode(array("status" => "success", "records" => $records));
    break;
    case "del-emp":
        $pin = substr($_POST["pin"],3);
        del_emp($pin);
    break;
    case "recall-emp":
        $emp_no = substr($_POST["emp_no"],3);
        recall_emp($emp_no);
    break;
    case "get-master-data":
        $filter = $_POST['filter'];
        $records = get_master_data($filter);
        echo json_encode(array("status" => "success", "records" => $records));
    break;
    case "update-workschedule":
        save_worksched(array("sched" => $_POST["sched"], "sched_desc" => $_POST["sched_desc"], "recid" => substr($_POST["recid"],3), "set" => $_POST["set"], "remark" => $_POST["remark"]));
    break;
    case "refresh-workschedule":
        $_SESSION["wksfr"] = (new DateTime($_POST["fr"]))->format("Y-m-d");
        $_SESSION["wksto"] = (new DateTime($_POST["to"]))->format("Y-m-d");
        echo json_encode(array("status" => "success", "message" => "error"));
    break;
}


//save changes in work schedule
function save_worksched($record) {
    global $db, $db_hris;
    $shift = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $shift->execute(array(":no" => $record["recid"]));
    if ($shift->rowCount()) {
        $shift_data = $shift->fetch(PDO::FETCH_ASSOC);
        $sched = get_sched($record["sched"]);
        $sched_desc = get_sched($record["sched_desc"]);
        if ($shift_data["work_schedule"] === $sched AND number_format($shift_data["shift_set_no"], 0, '.', '') === number_format($record["set"], 0, '.', '')) {
            echo json_encode(array("status" => "error", "message" => "No changes found!", "sched" => $sched, "record" => $record));
        } else {
            $m = $db->prepare("UPDATE $db_hris.`master_data` SET `work_schedule`=:sked, `shift_set_no`=:set, `user_id`=:uid WHERE `employee_no`=:no");
            $m->execute(array(":sked" => $sched, ":set" => $record["set"], ":no" => $record["recid"], ":uid" => $_SESSION["name"]));
            if ($m->rowCount()) {
                if ($shift_data["work_schedule"] !== $sched) {
                    $ws = $db->prepare("SELECT * FROM $db_hris.`master_journal` WHERE `employee_no`=:no AND `reference` LIKE :ref ORDER BY `time_stamp` DESC LIMIT 1");
                    $ws->execute(array(":no" => $shift_data["pin"], ":ref" => 'Work Schedule'));
                    if($ws->rowCount()){
                        $ws_data = $ws->fetch(PDO::FETCH_ASSOC);
                        master_journal($ws_data["change_to"], $sched_desc , "Work Schedule", $record["remark"], $shift_data["pin"]);
                    }
                    
                }
                if (number_format($shift_data["shift_set_no"], 0, '.', '') !== number_format($record["set"], 0, '.', '')) {
                    $shift_set = $db->prepare("SELECT * FROM $db_hris.`shift_set` WHERE `shift_set_no`=:shift_no");
                    $shift_set->execute(array(":shift_no" => $shift_data["shift_set_no"]));
                    if ($shift_set->rowCount()) {
                        $shift_set_data = $shift_set->fetch(PDO::FETCH_ASSOC);
                        $shift_set1 = $db->prepare("SELECT * FROM $db_hris.`shift_set` WHERE `shift_set_no`=:shift_no");
                        $shift_set1->execute(array(":shift_no" =>  $record["set"]));
                        if ($shift_set1->rowCount()) {
                            $shift_set_data1 = $shift_set1->fetch(PDO::FETCH_ASSOC);
                            master_journal($shift_set_data["description"], $shift_set_data1["description"], "Shift", $record["remark"], $shift_data["pin"]);
                        }
                    }
                }
                echo json_encode(array("status" => "success"));
            } else {
                echo json_encode(array("status" => "error", "message" => "No changes found!", "sched" => $sched, "record" => $record, "e" => $m->errorInfo(), "d" => array(":sked" => $sched, ":set" => $record["set"], ":no" => $record["recid"], ":uid" => $_SESSION["name"])));
            }
        }
    }
}

//get the schedule
function get_sched($sched) {
    $wks = explode(",", $sched);
    $newsked = [0, 0, 0, 0, 0, 0, 0];
    $date = new DateTime(date("m/d/Y"));
    for ($index = 0; $index <= 6; $index++) {
        if (number_format($date->format("N"), 0) === number_format(7, 0)) {
            $day = 0;
        } else {
            $day = $date->format("N");
        }
        $newsked[$day] = $wks[$index];
        $date->modify("+1 day");
    }
    $sked = implode(",", $newsked);
    return $sked;
}

//get the employee list by filter
function get_master_data($filter) {
    global $db, $db_hris;
    $records = array();

    if($filter == 1){
        $filtered = ' WHERE !`master_data`.`is_inactive` ';
    }else{
        $filtered = '';
    }
    $master = $db->prepare("SELECT * FROM $db_hris.`master_data`$filtered ORDER BY `family_name` ASC");
    $master->execute();
    if ($master->rowCount()) {
        $store = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `StoreCode`=:scode");
        $position = $db->prepare("SELECT * FROM $db_hris.`position` WHERE `position_no`=:pno");
        $employment = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code`=:ecode");
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $store->execute(array(":scode" => $master_data['store']));
            if($store->rowCount()){
                $store_data = $store->fetch(PDO::FETCH_ASSOC);
                $assign_store = $store_data['StoreName'];
            }else{
                $assign_store = '';
            }
            $position->execute(array(":pno" => $master_data['position_no']));
            if($position->rowCount()){
                $position_data = $position->fetch(PDO::FETCH_ASSOC);
                $pos_desc = $position_data['position_description'];
            }else{
                $pos_desc = '';
            }
            $employment->execute(array(":ecode" => $master_data['group_no']));
            if($employment->rowCount()){
                $employment_data = $employment->fetch(PDO::FETCH_ASSOC);
                $e_status = $employment_data['description'];
            }else{
                $e_status = '';
            }

            $middle_name=$master_data['middle_name'];
            if($middle_name != ''){
                $mname=substr($master_data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            if($master_data['is_inactive']){
                $emp_no = "100".$master_data['employee_no'];
                $pin="<span class='w3-text-red'>".$master_data['pin']."</span>";
                $lname="<span class='w3-text-red'>".$master_data['family_name']."</span>";
                $fname="<span class='w3-text-red'>".$master_data['given_name']."</span>";
                $mname="<span class='w3-text-red'>".$mname."</span>";
                $pos="<span class='w3-text-red'>".$pos_desc."</span>";
                $grp="<span class='w3-text-red'>".$assign_store."</span>";
                $status="<span class='w3-text-red'>".$e_status."</span>";
            }else{
                $emp_no = "100".$master_data['employee_no'];
                $pin=$master_data['pin'];
                $lname=$master_data['family_name'];
                $fname=$master_data['given_name'];
                $mname=$mname;
                $pos=$pos_desc;
                $grp=$assign_store;
                $status = $e_status;
            }

            $record = array("recid" => $emp_no, "pin"=> $pin, "lname" => $lname, "fname" => $fname, "mname" => $mname, "pos" => $pos, "grp" => $grp, "status" => $status);
            $records[] = $record;
        }
    }
    return $records;
}

//delete employee
function del_emp($pin) {
    global $db, $db_hris;

    $del_emp = $db->prepare("UPDATE $db_hris.`master_data` SET `is_inactive`=:actv WHERE `employee_no`=:pin");
    $del_emp->execute(array(":pin"=>$pin, ":actv"=> 1));
    if($del_emp->rowCount()){
        echo json_encode(array("status" => "success"));
    }else{
        echo json_encode(array("status" => "error", "message" => "This employee is already InActive!"));
    }
}


//recall specific employee if deleted
function recall_emp($emp_no) {
    global $db, $db_hris;

    $recall_emp = $db->prepare("UPDATE $db_hris.`master_data` SET `is_inactive`=:actv WHERE `employee_no`=:eno");
    $recall_emp->execute(array(":eno"=>$emp_no, ":actv"=> 0));
    if($recall_emp->rowCount()){
        echo json_encode(array("status" => "success"));
    }else{
        echo json_encode(array("status" => "error", "message" => "Error! Recalling this employee!"));
    }
}


//get specific history of employee
function get_journal($df, $dt, $pin) {
    global $db, $db_hris;
    $records = array();

    $df .= " 00:00:00";
    $dt .= " 23:59:59";

    $journal = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`master_journal` WHERE `master_journal`.`employee_no`=`master_data`.`pin` and `master_data`.`employee_no`=:pin and `master_journal`.`time_stamp` BETWEEN :df and :dt ORDER BY `master_journal`.`seq_no` DESC");
    $journal->execute(array(":df" => $df, ":dt" => $dt, ":pin"=>$pin));
    if ($journal->rowCount()) {
        while ($data = $journal->fetch(PDO::FETCH_ASSOC)) {
            $ref = $data['reference'];
            $cf=$data['change_from'];
            $ct=$data['change_to'];
            $uid=$data['user_id'];
            $ip=$data['station_id'];
            $ts=$data['time_stamp'];
            $rm=$data['remarks'];

            $record = array("recid" => $data['seq_no'], "ref"=> $ref, "cf" => $cf, "ct" => $ct, "uid" => $uid, "ip" => $ip, "ts" => $ts, "rm" => $rm);
            $records[] = $record;
        }
    }
    return $records;
}


//saving changes of rates
function save_update_rate($rate,$inctv,$emp_no,$total_pay,$rm) {
    global $db, $db_hris;

    $check_rate = $db->prepare("SELECT * FROM $db_hris.`employee_rate`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`employee_rate`.`employee_no` AND `employee_rate`.`employee_no`=:no");
    $check_rate->execute(array(":no" => $emp_no));
    if ($check_rate->rowCount()){
        $rate_data = $check_rate->fetch(PDO::FETCH_ASSOC);
        $employee_no = $rate_data['pin'];
        $change = 0;
        if ($rate != $rate_data["daily_rate"]) {
            $change = master_journal($rate_data["daily_rate"], $rate, "Daily Rate", $rm, $employee_no);
        }
        if ($inctv != $rate_data["incentive_cash"]) {
            $change = master_journal($rate_data["incentive_cash"], $inctv, "Incentive Cash", $rm, $employee_no);
        }
        if ($change) {

            $update_rate = $db->prepare("UPDATE $db_hris.`employee_rate` SET `daily_rate`=:rate, `incentive_cash`=:cash, `user_id`=:uid, `station_id`=:ip, `remark`=:rm, `total_pay`=:total WHERE `employee_no`=:id");
            $update_rate->execute(array(":id" => $emp_no, ":rate" => $rate, ":cash" => $inctv, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":rm" => $rm, ":total" => $total_pay));

            $master1 = $db->prepare("UPDATE $db_hris.`master_id` SET `employee_no`=:emp WHERE `pin_no`=:pin");

            $update1 = array(":emp" => $emp_no, ":pin" => $employee_no);

            $master1->execute($update1);

            echo json_encode(array("status" => "success", "emp_no" => $emp_no, "rate" => number_format($rate,2), "inctv" => number_format($inctv,2), "total" => number_format($total_pay,2)));
        }else{
            echo json_encode(array("status" => "error", "message" => "No changes detected!"));
        }
    }else{
        $check_rate = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `master_data`.`employee_no`=:no");
        $check_rate->execute(array(":no" => $emp_no));
        if ($check_rate->rowCount()){
            $rate_data = $check_rate->fetch(PDO::FETCH_ASSOC);
            $employee_no = $rate_data['pin'];
            
            master_journal('0.00', number_format($rate,2), "Daily Rate", $rm, $employee_no);
            master_journal('0.00', number_format($inctv,2), "Incentive Cash", $rm, $employee_no);

            $new_rate = $db->prepare("INSERT INTO $db_hris.`employee_rate`(`employee_no`, `daily_rate`, `incentive_cash`, `user_id`, `station_id`, `remark`, `total_pay`) VALUES (:id, :rate, :cash, :uid, :ip, :rm, :total)");

            $new_rate->execute(array(":id" => $emp_no, ":rate" => $rate, ":cash" => $inctv, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":rm" => $rm, ":total" => $total_pay));

            $master1 = $db->prepare("UPDATE $db_hris.`master_id` SET `employee_no`=:emp WHERE `pin_no`=:pin");

            $update1 = array(":emp" => $emp_no, ":pin" => $employee_no);

            $master1->execute($update1);

            echo json_encode(array("status" => "success", "emp_no" => $emp_no, "rate" => number_format($rate,2), "inctv" => number_format($inctv,2), "total" => number_format($total_pay,2)));
        }
    }
}

//save changes in status
function save_update_status($emp_status,$remarks,$emp_no) {
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $master->execute(array(":no" => $emp_no));
    if ($master->rowCount()){
        $master_data = $master->fetch(PDO::FETCH_ASSOC);

        $master1 = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code`=:emp_code");
        $master1->execute(array(":emp_code" => $emp_status));
        if ($master1->rowCount()){
            $master_data1 = $master1->fetch(PDO::FETCH_ASSOC);

            $emp_desc = $master_data1['description'];

            $master2 = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code`=:group_no");
            $master2->execute(array(":group_no" => $master_data["group_no"]));
            if ($master2->rowCount()){
                $master_data2 = $master2->fetch(PDO::FETCH_ASSOC);

                $group_desc = $master_data2['description'];

                $employee_no = $master_data['pin'];
                $change = 0;
                if ($emp_status != $master_data["group_no"]) {
                    $change = master_journal($group_desc, $emp_desc, "Employment Status", $remarks, $employee_no);
                }
                if ($change) {

                    $update_group = $db->prepare("UPDATE $db_hris.`master_data` SET `group_no`=:group WHERE `employee_no`=:id");

                    $update_group->execute(array(":id" => $emp_no, ":group" => $emp_status));

                    $master1 = $db->prepare("UPDATE $db_hris.`master_id` SET `employee_no`=:emp ,`pay_group`=:group WHERE `pin_no`=:pin");

                    $update1 = array(":emp" => $emp_no, ":pin" => $employee_no, ":group" => $emp_status);

                    $master1->execute($update1);

                    echo json_encode(array("status" => "success"));
                }else{
                    echo json_encode(array("status" => "error", "message" => "No changes detected!"));
                }
            }
        }
    }
}


//saving the changes
function update_data($emp_no,$fname,$lname,$mname,$bday,$edate,$gender,$cs,$position,$c_address,$p_address,$contact,$store,$atm,$tin,$sss,$love,$phealth,$remarks,$ctax,$csss,$clove,$cph) {
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`master_id` WHERE `master_data`.`pin`=`master_id`.`pin_no` AND `master_data`.`pin`=:no");
    $master->execute(array(":no" => $emp_no));
    if ($master->rowCount()) {
        $remark = $remarks;
        $employee_no = $emp_no;
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $pin_no = $master_data["pin"];
        $_emp_no = $master_data["employee_no"];
        $change = 0;
        //start checking if have changes
        if ($fname != $master_data["given_name"]) {
            $change = master_journal($master_data["given_name"], $fname, "Given name", $remark, $employee_no);
        }
        if ($lname != $master_data["family_name"]) {
            $change = master_journal($master_data["family_name"], $lname, "Family name", $remark, $employee_no);
        }
        if ($mname != $master_data["middle_name"]) {
            $change = master_journal($master_data["middle_name"], $mname, "Middle name", $remark, $employee_no);
        }
        if ($bday != $master_data["birth_date"]) {
            $change = master_journal($master_data["birth_date"], $bday, "Birth date", $remark, $employee_no);
        }
        if ($edate != $master_data["employment_date"]) {
            $change = master_journal($master_data["employment_date"], $edate, "Employment Date", $remark, $employee_no);
        }
        if ($gender != $master_data["sex"]) {
            $change = master_journal($master_data["sex"], $gender, "Gender", $remark, $employee_no);
        }
        if ($cs != $master_data["civil_status"]) {
            $change = master_journal($master_data["civil_status"], $cs, "Civil Status", $remark, $employee_no);
        }
        if ($position != $master_data["position_no"]) {
            $get_pos = $db->prepare("SELECT * FROM $db_hris.`position` WHERE `position_no`=:no");
            $get_pos->execute(array(":no" => $position));
            if($get_pos->rowCount()){
                $get_prev_pos = $db->prepare("SELECT * FROM $db_hris.`position` WHERE `position_no`=:no");
                $get_pos_data = $get_pos->fetch(PDO::FETCH_ASSOC);
                $get_prev_pos->execute(array(":no" => $master_data["position_no"]));
                if($get_prev_pos->rowCount()){
                    $get_prev_pos_data = $get_prev_pos->fetch(PDO::FETCH_ASSOC);
                    $change = master_journal($get_prev_pos_data["position_description"], $get_pos_data["position_description"], "Position", $remark, $employee_no);
                }
            }
        }
        if ($c_address != $master_data["address"]) {
            $change = master_journal($master_data["address"], $c_address, "Current Address", $remark, $employee_no);
        }
        if ($p_address != $master_data["permanent_address"]) {
            $change = master_journal($master_data["permanent_address"], $p_address, "Permanent Address", $remark, $employee_no);
        }
        if ($contact != $master_data["contact"]) {
            $change = master_journal($master_data["contact"], $contact, "Contact", $remark, $employee_no);
        }
        if ($store != $master_data["store"]) {
            $change = master_journal($master_data["store"], $store, "Store", $remark, $employee_no);
        }
        if ($atm != $master_data["bank_account"]) {
            $change = master_journal($master_data["bank_account"], $atm, "Bank Account", $remark, $employee_no);
        }
        if ($tin != $master_data["tin"]) {
            $change = master_journal($master_data["tin"], $tin, "TIN ID", $remark, $employee_no);
        }
        if ($sss != $master_data["sss"]) {
            $change = master_journal($master_data["sss"], $sss, "SSS ID", $remark, $employee_no);
        }
        if ($love != $master_data["pag_ibig"]) {
            $change = master_journal($master_data["pag_ibig"], $love, "Pag-Ibig ID", $remark, $employee_no);
        }
        if ($phealth != $master_data["phil_health"]) {
            $change = master_journal($master_data["phil_health"], $phealth, "PhilHealth ID", $remark, $employee_no);
        }
        if ($ctax != $master_data["compute_tax"]) {
            $change = master_journal($master_data["compute_tax"], $ctax, "Compute Tax", $remark, $employee_no);
        }
        if ($cph != $master_data["compute_philhealth"]) {
            $change = master_journal($master_data["compute_philhealth"], $cph, "Compute PhilHealth", $remark, $employee_no);
        }
        if ($csss != $master_data["compute_sss"]) {
            $change = master_journal($master_data["compute_sss"], $csss, "Compute SSS", $remark, $employee_no);
        }
        if ($clove != $master_data["compute_pagibig"]) {
            $change = master_journal($master_data["compute_pagibig"], $clove, "Compute Pag-Ibig", $remark, $employee_no);
        }
        if ($change) { //update if have changes
            $get_em_no = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `master_data`.`pin`=:no");
            $get_em_no->execute(array(":no" => $emp_no));
            if ($get_em_no->rowCount()) {
                $get_em_no_data = $get_em_no->fetch(PDO::FETCH_ASSOC);
                $_emp_no = $get_em_no_data["employee_no"];

                $master = $db->prepare("UPDATE $db_hris.`master_data` SET `given_name`=:gname, `middle_name`=:mname, `family_name`=:fname, `birth_date`=:bday, `employment_date`=:edate, `position_no`=:position, `sex`=:sex, `civil_status`=:cs, `address`=:add, `permanent_address`=:padd, `contact`=:contact, `user_id`=:uid, `station_id`=:ip, `store`=:store WHERE `pin`=:no");
                $update = array(":no" => $emp_no, ":gname" => strtoupper($fname), ":mname" => strtoupper($mname), ":fname" => strtoupper($lname), ":bday" => $bday, ":edate" => $edate, ":position" => $position, ":sex" => $gender, ":cs" => $cs, ":add" => $c_address, ":padd" => $p_address, ":contact" => $contact, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":store" => $store);

                $master1 = $db->prepare("UPDATE $db_hris.`master_id` SET `sss`=:sss, `compute_sss`=:csss, `compute_pagibig`=:clove, `compute_philhealth`=:cph, `compute_tax`=:ctax, `pag_ibig`=:love, `phil_health`=:ph, `tin`=:tin, `bank_account`=:bank, `user_id`=:uid, `station_id`=:ip, `employee_no`=:emp WHERE `pin_no`=:pin");
                $update1 = array(":pin" => $pin_no, ":sss" => $sss, ":csss" => $csss, ":clove" => $clove, ":cph" => $cph, ":ctax" => $ctax, ":love" => $love, ":ph" => $phealth, ":tin" => $tin, ":bank" => $atm, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":emp" => $_emp_no);

                $master->execute($update);
                $master1->execute($update1);
                if($master->rowCount()){
                    echo json_encode(array("status" => "success", "emp" => $_emp_no));
                }elseif($master1->rowCount()){
                    echo json_encode(array("status" => "success", "emp" => $_emp_no));
                }else{
                    echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $master->errorInfo(), "q" => $master, "d" => $update));
                }
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "No changes detected!", "master" => $master_data));
        }
    }
}


//saving the personal data
function save_data($emp_no,$fname,$lname,$mname,$bday,$edate,$gender,$cs,$position,$c_address,$p_address,$contact,$store,$atm,$tin,$sss,$love,$phealth,$remarks,$ctax,$csss,$clove,$cph) {
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `family_name` LIKE :fname AND `given_name` LIKE :gname AND `middle_name` LIKE :mname AND !`is_inactive`");
    $master->execute(array(":fname" => $lname, ":gname" => $fname, ":mname" => $mname));
    if ($master->rowCount()) {
        echo json_encode(array("status" => "error", "message" => "This is existing employee!"));
    } else {
        $master = $db->prepare("INSERT INTO $db_hris.`master_data` (`pin`, `given_name`, `middle_name`, `family_name`, `birth_date`, `position_no`, `employment_date`, `sex`, `civil_status`, `address`, `permanent_address`, `contact`, `user_id`, `station_id`, `id_picture`, `store`, `is_inactive`, `main_pin`, `date_hired`, `work_schedule`) VALUES (:pin, :fname, :mname, :lname, :bday, :position, :edate, :sex, :cs, :address1, :address2, :contact, :user, :station, :pic, :store, :isInactive, :mpin, :datehired, :worksched)");
        $update = array(":pin" => $emp_no, ":fname" => strtoupper($fname), ":mname" => strtoupper($mname), ":lname" => strtoupper($lname), ":bday" => $bday, ":position" => $position, ":edate" => $edate, ":sex" => $gender, ":cs" => $cs, ":address1" => $c_address, ":address2" => $p_address, ":contact" => $contact, ":user" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR'], ":pic" => '', ":store" => $store, ":isInactive" => 0, ":mpin" => $emp_no, ":datehired" => date('Y-m-d'), ":worksched" => '000000,000000,000000,000000,000000,000000,000000');

        $master->execute($update);
        if ($master->rowCount()) {
            $employee_no = $emp_no;
            master_journal("", "NEW-RECORD", "CREATION", $remarks, $employee_no);
            $master_id = $db->prepare("INSERT INTO $db_hris.`master_id`(`pin_no`, `sss`, `compute_sss`, `pag_ibig`, `compute_pagibig`, `max_pagibig_prem`, `phil_health`, `compute_philhealth`, `tax_code`, `tin`, `compute_tax`, `bank_account`, `pay_group`, `user_id`, `station_id`) VALUES (:pin, :sss, :c_sss, :love, :c_love, :love_prem, :ph, :c_ph, :tax, :tin, :ctax, :bank, :group, :user, :station)");

            $master_id->execute(array(":pin" => $emp_no, ":sss" => $sss, ":c_sss" => $csss, ":love" => $love, ":c_love" => $clove, ":love_prem" => 100, ":ph" => $phealth, ":c_ph" => $cph, ":tax" => 0, ":tin" => $tin, ":ctax" => $ctax, ":bank" => $atm, ":group" => 0, ":user" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));

            echo json_encode(array("status" => "success", "recid" => $employee_no));
        } else {
            echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $master->errorInfo(), "q" => $master, "d" => $update));
        }
    }
}

//generate unique pin
function get_pin_id() {
    global $db, $hris;
    $user = $db->prepare("SELECT * FROM $hris.`master_data` WHERE `pin`=:id");
    $value = 100000000;
    $count = 0;
    while ($count < $value) {
        $random = substr(number_format(RAND(1, $value) + $value, 0, '.', ''), -2);
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

//get all available positions
function get_positions() {
    global $db, $db_hris;

    $pos_list = $db->prepare("SELECT `position`.`position_no`,`position`.`position_description` FROM $db_hris.`position`");
    $position = array();
    $pos_list->execute();
    if ($pos_list->rowCount()) {
        while ($data = $pos_list->fetch(PDO::FETCH_ASSOC)) {
            $position[] = array("id" => $data["position_no"], "text" => $data["position_description"]);
        }
    }
    echo json_encode(array("status" => "success", "position" => $position));
}

//get all available groups
function get_group() {
    global $db, $db_hris;

    $group_list = $db->prepare("SELECT `employment_status`.`employment_status_code`,`employment_status`.`description` FROM $db_hris.`employment_status`");
    $group = array();
    $group_list->execute();
    if ($group_list->rowCount()) {
        while ($data = $group_list->fetch(PDO::FETCH_ASSOC)) {
            $group[] = array("id" => $data["employment_status_code"], "text" => $data["description"]);
        }
    }
    echo json_encode(array("status" => "success", "group" => $group));
}

//get all available stores
function get_store() {
    global $db, $db_hris;

    $store = $db->prepare("SELECT `store`.`StoreCode`,`store`.`StoreName` FROM $db_hris.`store`");
    $store_list = array();
    $store->execute();
    if ($store->rowCount()) {
        while ($store_data = $store->fetch(PDO::FETCH_ASSOC)) {
            $store_list[] = array("id" => $store_data["StoreCode"], "text" => $store_data["StoreName"]);
        }
    }
    echo json_encode(array("status" => "success", "store" => $store_list));
}

//get specific employee data
function get_emp_data($emp_no) {
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no");
    $master->execute(array(":emp_no" => $emp_no));
    if ($master->rowCount()){
        $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `pin_no`=:pin");
        $store = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `StoreCode`=:scode");
        $position = $db->prepare("SELECT * FROM $db_hris.`position` WHERE `position_no`=:pno");
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $master_id->execute(array(":pin" => $master_data["pin"]));
            if ($master->rowCount()){
                $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);
            }
            $store->execute(array(":scode" => $master_data['store']));
            if($store->rowCount()){
                $store_data = $store->fetch(PDO::FETCH_ASSOC);
                $assign_store = $store_data['StoreName'];
                $assign_store_code = $store_data['StoreCode'];
            }else{
                $assign_store = '';
                $assign_store_code = '';
            }
            $position->execute(array(":pno" => $master_data['position_no']));
            if($position->rowCount()){
                $position_data = $position->fetch(PDO::FETCH_ASSOC);
                $pos_desc = $position_data['position_description'];
            }else{
                $pos_desc = '';
            }
            $emp_no = $master_data["pin"];
            $first_name = $master_data["given_name"];
            $middle_name = $master_data["middle_name"];
            $last_name = $master_data["family_name"];
            $bday = $master_data["birth_date"];
            $pos_no = $master_data["position_no"];
            $position_name = $pos_desc;
            $edate = $master_data["employment_date"];
            if($master_data["sex"]=='1'){
                $sex = 'Male';
                $sex_id = '1';
            }else{
                $sex = 'Female';
                $sex_id = '2';
            }
            $cs = $master_data["civil_status"];
            $address1 = $master_data["address"];
            $address2 = $master_data["permanent_address"];
            $contact = $master_data["contact"];
            $store = $assign_store;
            $store_id = $assign_store_code;
            $tin = $master_id_data["tin"];
            $sss = $master_id_data["sss"];
            $love = $master_id_data["pag_ibig"];
            $love_prem = number_format($master_id_data["max_pagibig_prem"],0);
            $phealth = $master_id_data["phil_health"];
            $atm = $master_id_data["bank_account"];
            $tin = $master_id_data["tin"];
            $com_sss = $master_id_data["compute_sss"];
            $com_love = $master_id_data["compute_pagibig"];
            $com_phealth = $master_id_data["compute_philhealth"];
            $com_tax = $master_id_data["compute_tax"];
            if($master_data["id_picture"] == ''){
                $profile_pic = 'images/no_profile_pic.gif';
            }else{
                $profile_pic = 'data:image/jpeg;base64,'.base64_encode($master_data["id_picture"]);
            }
        }
    }
    echo json_encode(array("status" => "success", "emp_no" => $emp_no, "first_name" => $first_name, "middle_name" => $middle_name, "last_name" => $last_name, "bday" => $bday, "position_name" => $position_name, "edate" => $edate, "gender" => $sex, "cs" => $cs, "c_address" => $address1, "p_address" => $address2, "contact" => $contact, "store" => $store, "tin" => $tin, "sss" => $sss, "love" => $love, "love_prem" => $love_prem, "phealth" => $phealth, "atm" => $atm, "pos_no"=> $pos_no, "sex_id" => $sex_id, "store_id"=> $store_id, "com_sss"=> $com_sss, "com_love" => $com_love, "com_phealth"=> $com_phealth, "com_tax"=> $com_tax, "profile_pic" => $profile_pic));       
}

//vacation leave
function new_vl($record) {
    global $db, $db_hris;
    $year = date("Y");
    $a = $db->prepare("SELECT * FROM $db_hris.`employee_allowable_vl` WHERE `employee_no`=:no AND `year`=:yr");
    $a->execute(array(":no" => $record["recid"], ":yr" => $year));
    if ($a->rowCount()) {
        $data = $a->fetch(PDO::FETCH_ASSOC);
        $u = $db->prepare("UPDATE $db_hris.`employee_allowable_vl` SET `no_of_days`=:day, `user_id`=:uid WHERE `employee_allowable_vl_no`=:no");
        $u->execute(array(":day" => $record["days"], ":no" => $data["employee_allowable_vl_no"], ":uid" => $_SESSION["name"]));
    } else {
        $i = $db->prepare("INSERT INTO $db_hris.`employee_allowable_vl` (`no_of_days`, `employee_no`, `year`, `date_created`, `user_id`) VALUES (:days, :no, :yr, NOW(), :uid)");
        $i->execute(array(":days" => $record["days"], ":no" => $record["recid"], ":yr" => $year, ":uid" => $_SESSION["name"]));
    }
    if ($record["date"] !== "") {
        $tdate = new DateTime($record["date"]);
        $date = $tdate->format("Y-m-d");
        $year = $tdate->format("Y");
        $vs = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND `vl_date` LIKE :date AND !`is_cancelled`");
        $vs->execute(array(":eno" => $record["recid"], ":date" => $date));
        if ($vs->rowCount()) {
            echo json_encode(array("status" => "error", "message" => "Date encoded already!", "date" => $date));
        } else {
            $v = $db->prepare("INSERT INTO $db_hris.`employee_vl` (`employee_no`, `vl_date`, `date_filed`, `user_id`, `year`) VALUES (:eno, :date, NOW(), :uid, :yr)");
            $v->execute(array(":eno" => $record["recid"], ":date" => $date, ":uid" => $_SESSION["name"], ":yr" => $year));
            if ($v->rowCount()) {
            echo json_encode(array("status" => "success"));
            } else {
            echo json_encode(array("status" => "error", "message" => "Please try again later!", "date" => $date, "record" => $record, "e" => $v->errorInfo(), "d" => array(":eno" => $record["recid"], ":date" => $date, ":uid" => $_SESSION["name"], ":yr" => $year), "e" => $v->errorInfo()));
            }
        }
    } else {
        echo json_encode(array("status" => "success"));
    }
}

function cancel_vl($record) {
    global $db, $db_hris;
    $date = (new DateTime($record["date"]))->format("Y-m-d");
    $vl = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND `employee_vl_no`=:no AND `vl_date` LIKE :date AND !`is_cancelled` AND !`is_served`");
    $vl->execute(array(":eno" => $record["recid"], ":date" => $date, ":no" => $record["no"]));
    if ($vl->rowCount()) {
        $vu = $db->prepare("UPDATE $db_hris.`employee_vl` SET `is_cancelled`=1, `reason_for_cancellation`=:rem, `user_id`=:uid WHERE `employee_no`=:eno AND `employee_vl_no`=:no AND `vl_date` LIKE :date AND !`is_cancelled` AND !`is_served`");
        $vu->execute(array(":eno" => $record["recid"], ":date" => $date, ":no" => $record["no"], ":rem" => $record["remark"], ":uid" => $_SESSION["name"]));
        if ($vu->rowCount()) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "error", "Please try again later!  Out of focus.", "e" => $vu->errorInfo(), "record" => array(":eno" => $record["recid"], ":date" => $date, ":no" => $record["no"], ":rem" => $record["remark"], ":uid" => $_SESSION["name"])));
        }
    } else {
        echo json_encode(array("status" => "error", "Please try again later!  Out of focus.", "e" => $vl->errorInfo(), "record" => $record));
    }
}