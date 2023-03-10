<?php
$program_code = 5;
require_once('../common/functions.php');

include("../common_function.class.php");
$cfn = new common_functions();

include("../function/sysconfig.php");
include("../function/compute_payroll.php");

$change = sysconfig("change");
$group_name = $_GET["_group"];
$store = $_GET["_store"];
$payroll_group = mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_name'");
if (@mysqli_num_rows($payroll_group)) {
  $payroll_group_data = mysqli_fetch_array($payroll_group);
  $log_cutoff = $payroll_group_data["cutoff_date"];
  $payroll_cutoff = $payroll_group_data["payroll_date"];
  if (number_format(substr($payroll_cutoff, -2)) <= number_format(15)) $schedule = "1"; else  $schedule = "2";
  $deduction_query = "SELECT * FROM `deduction` WHERE `is_computed` AND !`is_inactive` AND `schedule` LIKE '%$schedule%'";
  $deduction = mysqli_query($con,$deduction_query);
  $total_no_of_deduction = number_format(@mysqli_num_rows($deduction) + 1, 0, '.', '');
  set_time_limit(300);

  mysqli_query($con,"DELETE FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND (SELECT COUNT(*) FROM `master_id`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `pay_group`='$payroll_group_data[group_name]') AND !`is_posted`") or die(mysqli_error($con));

  $master_query = "SELECT * FROM `master_data`, `master_id` WHERE !`is_inactive` AND `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`group_no`='$payroll_group_data[group_name]' AND `master_data`.`store`='$store' AND ((SELECT COUNT(*) FROM `time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date`>='$log_cutoff' AND `trans_date`<='$payroll_cutoff' LIMIT 1))";
  $master = mysqli_query($con,$master_query) or die(mysqli_error($con));
  $cnt = 0;
  if (@mysqli_num_rows($master)) {
    while ($master_data = mysqli_fetch_array($master)) {
      $payroll_trans=  mysqli_query($con,"SELECT * FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND `employee_no`='$master_data[pin]' AND !`is_posted`") or die(mysqli_error($con));
      if(!@mysqli_num_rows($payroll_trans)) compute_payroll($master_data["employee_no"], $payroll_cutoff, $change);
    }
    ?>
    <div class="w3-panel w3-border w3-padding w3-center w3-round-medium">
      <span class="w3-text-blue">STATURTORY CONTRIBUTION SHARE/OTHER DEDUCTION</span>
      <table class="w3-table-all w3-border w3-small">
        <thead>
          <tr>
            <th></th>
            <th class="w3-center w3-border">PIN</th>
            <th class="w3-center w3-border">NAME</th>
            <th class="w3-center w3-border">GROSS PAY</th>
            <?php
            $deduction = mysqli_query($con,$deduction_query);
            if (@mysqli_num_rows($deduction))
              while ($deduction_data = mysqli_fetch_array($deduction)) { ?>
                <th class="w3-center w3-border"><?php echo $deduction_data["deduction_label"]; ?></th>
              <?php } ?>
            <th class="w3-center w3-border">OTHER DED</th>
            <th class="w3-center w3-border">NET PAY</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $cnt = 0;
        $master = mysqli_query($con,"SELECT * FROM `master_data` WHERE !`is_inactive` AND (SELECT COUNT(*) FROM `master_id` WHERE `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `pay_group`='$payroll_group_data[group_name]') AND (SELECT COUNT(*) FROM `payroll_trans` WHERE `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `payroll_date`='$payroll_cutoff') ORDER BY `family_name`, `given_name`, `middle_name`") or die(mysqli_error($con));
        while ($master_data = mysqli_fetch_array($master)) {
          $payroll_trans_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `payroll_trans`,`master_data` WHERE `payroll_trans`.`employee_no`='$master_data[employee_no]' AND `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans`.`payroll_date`='$payroll_cutoff'"));
          ?>
          <tr>
            <td><?php echo number_format(++$cnt); ?>.</td>
            <td class="w3-border"><?php echo $master_data["pin"]; ?></td>
            <td class="w3-border"><?php echo $master_data["family_name"] . ", " . $master_data["given_name"] . " " . substr($master_data["middle_name"], 0, 1); ?></td>
            <td style="text-align: right;" class="w3-border"><?php echo number_format($payroll_trans_data["gross_pay"], 2); ?></td>
            <?php
            $total_deduction = $payroll_trans_data["deduction"];
            $deduction = mysqli_query($con,$deduction_query);
            if (@mysqli_num_rows($deduction))
              while ($deduction_data = mysqli_fetch_array($deduction)) {
                ?>
                <td style="text-align: right;" class="w3-border"><?php
                  $payroll_trans_ded = mysqli_query($con,"SELECT * FROM `payroll_trans_ded`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans_ded`.`employee_no`='$master_data[employee_no]' AND `payroll_trans_ded`.`payroll_date`='$payroll_cutoff' AND `payroll_trans_ded`.`deduction_no`='$deduction_data[deduction_no]'");
                  if (@mysqli_num_rows($payroll_trans_ded)) {
                    $payroll_trans_ded_data = mysqli_fetch_array($payroll_trans_ded);
                    $total_deduction-=$payroll_trans_ded_data["deduction_amount"];
                    echo number_format($payroll_trans_ded_data["deduction_amount"], 2);
                  }
                  ?></td>
                <?php
              }
            ?>
            <td style="text-align: right;" class="w3-border"><?php echo number_format($total_deduction, 2); ?></td>
            <td style="text-align: right;" class="w3-border"><?php echo number_format($payroll_trans_data["net_pay"], 2); ?></td>
          </tr>
          <?php
        }
      }
      $payroll_trans_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`gross_pay`) AS `gross_pay`, SUM(`deduction`) AS `deduction`, SUM(`net_pay`) AS `net_pay` FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND (SELECT COUNT(*) FROM `master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$payroll_group_data[group_name]')"));
      ?>
    </tbody>
    <tfoot>
      <tr class="w3-text-blue">
        <th colspan="3"  style="text-align: right;" class="w3-border">GRAND TOTAL</th>
        <th style="text-align: right;" class="w3-border"><?php echo number_format($payroll_trans_data["gross_pay"], 2); ?></th>
        <?php
        $total_deduction = $payroll_trans_data["deduction"];
        $deduction = mysqli_query($con,$deduction_query);
        $col_count = @mysqli_num_rows($deduction) + 5;
        if (@mysqli_num_rows($deduction))
          while ($deduction_data = mysqli_fetch_array($deduction)) {
            ?>
            <th style="text-align: right;" class="w3-border"><?php
              $payroll_trans_ded = mysqli_query($con,"SELECT SUM(`deduction_amount`) AS `deduction_amount` FROM `payroll_trans_ded`, `master_data` WHERE `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_name' AND `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `payroll_trans_ded`.`payroll_date`='$payroll_cutoff' AND `payroll_trans_ded`.`deduction_no`='$deduction_data[deduction_no]'");
              if (@mysqli_num_rows($payroll_trans_ded)) {
                $payroll_trans_ded_data = mysqli_fetch_array($payroll_trans_ded);
                $total_deduction-=$payroll_trans_ded_data["deduction_amount"];
                echo number_format($payroll_trans_ded_data["deduction_amount"], 2);
              }
              ?></th>
            <?php
          }
        ?>
        <th style="text-align: right;" class="w3-border"><?php echo number_format($total_deduction, 2); ?></th>
        <th style="text-align: right;" class="w3-border"><?php echo number_format($payroll_trans_data["net_pay"], 2); ?></th>
      </tr>
      <?php
      $payroll_trans_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`net_pay`) AS `net_pay` FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND `net_pay`>0 AND (SELECT COUNT(*) FROM `master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$payroll_group_data[group_name]')"));
      ?>
      <tr class="w3-text-blue">
        <th colspan="<?php echo $col_count; ?>" style="text-align: right;" class="w3-border">TOTAL NET PAY</th>
        <th style="text-align: right;" class="w3-border"><?php echo number_format($payroll_trans_data["net_pay"], 2); ?></th>
      </tr>
    </tfoot>
  </table>
</div>
  <?php
}

?>
