<?php

$program_code = 7;
require_once('../common/functions.php');

include("../common_function.class.php");
$cfn = new common_functions();


switch ($_REQUEST["cmd"]) {
    case "generate":
        generate($_REQUEST["datef"], $_REQUEST["datet"]);
        break;
    case "get-records":
        get_records();
        break;
    case "post_to_payroll":
        make_trans();
        break;
}

function get_year() {
    global $db, $db_hris;
    $date = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :config");
    $date->execute(array(":config" => "trans date"));
    $date_data = $date->fetch(PDO::FETCH_ASSOC);
    $year = date("Y", strtotime($date_data["config_value"]));
    return $year;
}

function generate($datef, $datet) {
    global $db_hris, $db;
    set_time_limit(3600);
    $user_id = $_SESSION['name'];
    $station_id = $_SERVER['REMOTE_ADDR'];
    $date_f = date("Y-m-d", strtotime($datef));
    $date_t = date("Y-m-d", strtotime($datet));
    $year = get_year();
    $ws_13th_del = $db->prepare("DELETE FROM $db_hris.`ws_13th` WHERE `year`=:year");
    $ws_13th_del->execute(array(":year" => $year));
    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`>=:datef AND `payroll_date`<=:datet AND `is_posted`");
    $payroll_trans->execute(array(":datef" => $date_f, ":datet" => $date_t));
    if ($payroll_trans->rowCount()) {
        $payroll_pay = $db->prepare("SELECT SUM(`credit`) AS `credit`, SUM(`pay_amount`) AS `amount` FROM $db_hris.`payroll_trans_pay`, $db_hris.`payroll_type` WHERE `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_type`.`is_subject_to_13th` AND `employee_no`=:no AND `payroll_date` LIKE :date");
        $ws_13th_ins = $db->prepare("INSERT INTO $db_hris.`ws_13th` (`year`, `period_from`, `period_to`, `ytd_credit`, `ytd_pay`, `employee_no`, `user_id`, `station_id`) VALUES (:year, :dfr, :dto, :credit, :pay, :no, :user, :station)");
        $ws_13th_upd = $db->prepare("UPDATE $db_hris.`ws_13th` SET `ytd_credit`=`ytd_credit`+:credit, `ytd_pay`=`ytd_pay`+:pay WHERE `employee_no`=:no AND `year`=:year");
        $ws_13th = $db->prepare("SELECT * FROM $db_hris.`ws_13th` WHERE `employee_no`=:no AND `year`=:year");
        while ($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            $payroll_pay->execute(array(":no" => $payroll_trans_data["employee_no"], ":date" => $payroll_trans_data["payroll_date"]));
            $payroll_pay_data = $payroll_pay->fetch(PDO::FETCH_ASSOC);
            if (number_format($payroll_pay_data["amount"], 2, '.', '') > number_format(0, 2)) {
                $ws_13th->execute(array(":no" => $payroll_trans_data["employee_no"], ":year" => $year));
                $pay_credit = $payroll_pay_data["credit"];
                $pay_amount = $payroll_pay_data["amount"];
                if ($ws_13th->rowCount()) {
                    $ws_13th_upd->execute(array(":no" => $payroll_trans_data["employee_no"], ":year" => $year, ":credit" => $pay_credit, ":pay" => $pay_amount));
                } else {
                    $ws_13th_ins->execute(array(":year" => $year, ":dfr" => $date_f, ":dto" => $date_t, ":credit" => $pay_credit, ":pay" => $pay_amount, ":no" => $payroll_trans_data["employee_no"], ":user" => $user_id, ":station" => $station_id));
                }
            }
        }
    }
    get_records();
}

function get_records(){
    global $db_hris, $db;
    $master = $db->prepare("SELECT * FROM $db_hris.`master_data`, $db_hris.`ws_13th` WHERE `master_data`.`employee_no`=`ws_13th`.`employee_no` AND !`ws_13th`.`is_payroll_generated` AND `ws_13th`.`year`=:year ORDER BY `family_name`, `given_name`");
    $year = get_year();
    $master->execute(array(":year" => $year));
    if ($master->rowCount()) {
        $records = array();
        $payroll_group = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code`=:no");
        $store_id = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `StoreCode`=:no");
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            $payroll_group->execute(array(":no" => $master_data["group_no"]));
            if ($payroll_group->rowCount()) {
                $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
                $group_name = $payroll_group_data["description"];
            } else {
                $group_name = "";
            }
            $store_id->execute(array(":no" => $master_data["store"]));
            if ($store_id->rowCount()) {
                $store = $store_id->fetch(PDO::FETCH_ASSOC);
                $store_name = $store["StoreName"];
            } else {
                $store_name = "";
            }
            array_push($records, array("recid" => $master_data["employee_no"], "group" => $group_name, "empno" => $master_data["pin"], "name" => $master_data["family_name"] . ", " . $master_data["given_name"], "credit" => $master_data["ytd_credit"], "store" => $store_name, "amount" => $master_data["ytd_pay"],  "net" => $master_data["ytd_pay"] / 12));
        }
        echo json_encode(array("status" => "success", "total" => count($records), "records" => $records));
    } else {
        echo json_encode(array("status" => "error", "message" => "no records extracted"));
    }
}

function make_trans() {
    $year = get_year();
    $payroll_type_no = get_paytype();
    $computed = compute_13th($payroll_type_no, $year);
    echo json_encode(array("status" => "error", "message" => $computed ? "13 Month Pay successfully generated to its designated payroll group" : "Failed to generated 13th month, probably already generated"));
}

function compute_13th($payroll_type_no, $year) {
    global $db, $db_hris;
    $w13th = $db->prepare("SELECT * FROM $db_hris.`ws_13th` WHERE !`is_deleted` AND `year`=:yr AND !`is_payroll_generated`");
    $w13th->execute(array(":yr" => $year));
    $return = 0;
    if ($w13th->rowCount()) {
        $user_id = $_SESSION['name'];
        $station_id = $_SERVER['REMOTE_ADDR'];
        $w13th_upd = $db->prepare("UPDATE $db_hris.`ws_13th` SET `is_payroll_generated`=1, `payroll_generated_by`=:uid, `payroll_generated_station`=:sid AND `payroll_generated_time`=NOW() WHERE `year`=:yr AND `employee_no`=:no");
        $payroll_trans_pay_ins = $db->prepare("INSERT INTO $db_hris.`payroll_adjustment` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`, `user_id`, `station_id`) VALUES (:eno, :pdate, :tno, :credit, :amt, :uid, :sid)");
        $payroll_trans_pay_sel = $db->prepare("SELECT * FROM $db_hris.`payroll_adjustment` WHERE `employee_no`=:eno AND `payroll_date` LIKE :pdate AND `payroll_type_no`=:tno");
        $payroll_trans_pay_upd = $db->prepare("UPDATE $db_hris.`payroll_adjustment` SET `credit`=:credit, `pay_amount`=:amt, `user_id`=:uid, `station_id`=:sid  WHERE `employee_no`=:eno AND `payroll_date` LIKE :pdate AND `payroll_type_no`=:tno");
        while ($w13th_data = $w13th->fetch(PDO::FETCH_ASSOC)) {
            $payroll_date = get_pay_date($w13th_data["employee_no"]);
            $payroll_trans_pay_sel->execute(array(":eno" => $w13th_data["employee_no"], ":pdate" => $payroll_date, ":tno" => $payroll_type_no));
            $credit = $w13th_data["ytd_pay"] + $w13th_data["adj"] + $w13th_data["additional"];
            $pay_amount = number_format($credit / 12, 2, ".", "");
            $genit = false;
            if ($payroll_trans_pay_sel->rowCount()) {
                $payroll_trans_pay_upd->execute(array(":eno" => $w13th_data["employee_no"], ":pdate" => $payroll_date, ":tno" => $payroll_type_no, ":credit" => $credit, ":amt" => $pay_amount, ":uid" => $user_id, ":sid" => $station_id));
                if ($payroll_trans_pay_upd->rowCount()) {
                    $genit = true;
                } else {
                    echo "pay upd";
                    print_r($payroll_trans_pay_upd->errorInfo());
                }
            } else {
                $payroll_trans_pay_ins->execute(array(":eno" => $w13th_data["employee_no"], ":pdate" => $payroll_date, ":tno" => $payroll_type_no, ":credit" => $credit, ":amt" => $pay_amount, ":uid" => $user_id, ":sid" => $station_id));
                if ($payroll_trans_pay_ins->rowCount()) {
                    $genit = true;
                } else {
                    echo "pay ins";
                    print_r($payroll_trans_pay_ins->errorInfo());
                }
            }
            if ($genit) {
                $w13th_upd->execute(array(":uid" => $user_id, ":sid" => $station_id, ":yr" => $year, ":no" => $w13th_data["employee_no"]));
                if ($w13th_upd->rowCount()) {
                    $return++;
                } else {
                    echo "wks upd";
                    print_r($w13th_upd->errorInfo());
                }
            }
        }
    } else {
        echo "wks fetch";
        print_r($w13th->errorInfo());
    }
    return $return;
}

function get_pay_group($employee_no) {
    global $db, $db_hris;
    $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `employee_no`=:eno");
    $master_id->execute(array(":eno" => $employee_no));
    if ($master_id->rowCount()) {
        $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);
        $group_no = $master_id_data["pay_group"];
    } else {
        $group_no = 0;
    }
    return $group_no;
}

function get_pay_date($employee_no) {
    global $db, $db_hris;
    $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `employee_no`=:eno");
    $master_id->execute(array(":eno" => $employee_no));
    $date = date("Y-m-d");
    if ($master_id->rowCount()) {
        $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);
        $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:pno");
        $payroll_group->execute(array(":pno" => $master_id_data["pay_group"]));
        if ($payroll_group->rowCount()) {
            $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
            $date = $payroll_group_data["payroll_date"];
        }
    }
    return $date;
}

function get_paytype() {
    global $db, $db_hris;
    $sysconfig = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :name");
    $sysconfig->execute(array(":name" => "13th code"));
    if ($sysconfig->rowCount()) {
        $sysconfig_data = $sysconfig->fetch(PDO::FETCH_ASSOC);
        $code = $sysconfig_data["config_value"];
    } else {
        $code = 0;
    }
    return $code;
}
