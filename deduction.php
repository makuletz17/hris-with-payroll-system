<?php

include('session.php');
include('modules/system/system.config.php');


$check_level = mysqli_query($con, "SELECT `user_level` FROM `_user` where `user_id`='".$session_name."'");
$level = mysqli_fetch_array($check_level);

$program_code = 3;

if($level['user_level'] < $program_code){
    echo "No Access rights!!";
    exit();
};
include('common_function.class.php');
$cfn = new common_functions();

?>
<div class="w3-orange w3-text-white w3-padding">DEDUCTION MAINTENANCE</div>
<div id="deduction_list">
  <table class="w3-table-all w3-small w3-hoverable">
    <thead>
        <tr>
          <th></th>
          <th>DEDUCTION NAME</th>
          <th>TYPE</th>
          <th>SCHEDULE</th>
          <th>USER ID</th>
          <th>STATION ID</th>
          <th>TIME STAMP</th>
          <th>STATUS</th>
        </tr>
      </thead>
      <tbody>
        <tr>
            <td><input type="hidden" name="ded_id" id="ded_id" value="" /></td>
            <td><input type="text" name="ded_name" id="ded_name" value="" required style="width: 100%;" /></td>
            <td><input name="ded_option" type="list" maxlength="100" id="ded_option" style="width: 100%;"></td>
            <td>
              <input type="checkbox" name="mid" id="mid" value="1">&nbsp;<label>MID</label>&nbsp;
              <input type="checkbox" name="end" id="end" value="2">&nbsp;<label>END</label>
            </td>
            <td colspan="4">
              <button class="w2ui-btn" id="save" style="cursor: pointer;" onclick="save_ded()">SAVE</button>
              <button class="w2ui-btn w3-hide" id="reset" style="cursor: pointer;" onclick="clear_this()">RESET</button>
            </td>
          </tr>
      <?php
          
      $cnt=0;
      if($level['user_level'] > 8){
        $filter = "";
      }else{
        $filter = "WHERE !`is_computed`";
      }
      $deduction=  mysqli_query($con, "SELECT * FROM `deduction` $filter ORDER BY `deduction_label`");
      if(@mysqli_num_rows($deduction))
        while($deduction_data=  mysqli_fetch_array($deduction)){
          if(number_format($deduction_data["deduction_type"])==  number_format(1)){
            $deduction_type = 'Invoice';
          }else{
            $deduction_type = 'Others';
          } ?>
          
          <tr style="cursor: pointer;" onclick="edit_ded(<?php echo $deduction_data['deduction_no']; ?>)">
            <td><?php echo number_format(++$cnt); ?>.</td>
            <td><?php echo $deduction_data["deduction_label"]; ?></td>
            <td><?php echo $deduction_type; ?></td>
            <td><input disabled type="checkbox" id="<?php echo $deduction_data["deduction_no"]; ?>" <?php if(number_format(substr($deduction_data["schedule"], 0,1))==  number_format(1))echo "checked=\"checked\""; ?> />&nbsp;<label>MID</label>&nbsp;
                <input disabled type="checkbox" id="<?php echo $deduction_data["deduction_no"]; ?>" <?php if(number_format(substr($deduction_data["schedule"], -1))==  number_format(2))echo "checked=\"checked\""; ?> />&nbsp;<label>END</label>
            </td>
            <td><?php echo $deduction_data["user_id"]; ?></td>
            <td><?php echo $deduction_data["station_id"]; ?></td>
            <td><?php echo $cfn->datefromdb(substr($deduction_data["time_stamp"], 0, 10)).substr($deduction_data["time_stamp"], 10, 10); ?></td>
            <td align="center"><?php if($deduction_data["is_inactive"]) echo "Inactive"; else echo "Active"; ?></td>
          </tr>
      <?php            
      }
    ?>
    </tbody>
  </table>
</div>
<script type="text/javascript">
  $(":input#ded_name").w2field("text");

  const src = "modules/deduction.php";

  var ded_opt = [{id :'0',text: 'Others'}, {id: '1',text: 'Invoice'}];
  $('input#ded_option').w2field('list', { items: ded_opt });

  function edit_ded($ded_id){
    if($('input#mid[value="1"]').is(":checked") && $('input#end[value="2"]').is(":checked")){
      $('input#ded_id').val('');
      $('input#ded_name').val('');
      $('input#ded_option').w2field('list', { items: ded_opt });
      $('input#mid[value="1"]').click();
      $('input#end[value="2"]').click();
    }
    $.ajax({
      url: src,
      method: "POST",
      data:{
        cmd: "get_ded_data",
        ded_id : $ded_id
      },
      success: function (data){
        if (data !== ""){
          var _return = jQuery.parseJSON(data);
          if(_return.status === "success"){
            $('input#ded_id').val($ded_id);
            $('input#ded_name').val(_return.ded_name);
            $('input#ded_option').w2field().set({id: _return.ded_id, text: _return.ded_type});
            $('input#mid[value="' + _return.mid + '"]').click();
            $('input#end[value="' + _return.end + '"]').click();
            $('#reset').removeClass("w3-hide");
          }else{
            w2alert("Sorry, No DATA found!");
          }
        }
      },
      error: function (){
        w2alert("Sorry, there was a problem in server connection!");
      }
    })
  }

  function save_ded(){
    var ded_type = $('input#ded_option').w2field().get().id;
    if($('input#mid[value="1"]').is(":checked")){
        var ded_sched = 1;
    }
    if($('input#end[value="2"]').is(":checked")){
      var ded_sched = 2;
    }
    if($('input#mid[value="1"]').is(":checked") && $('input#end[value="2"]').is(":checked")){
      var ded_sched = "1,2";
    }
    if($('input#ded_id').val() == ""){
      var ded_id = 0;
    }else{
      var ded_id = $('input#ded_id').val();
    }
    $.ajax({
      url: src,
      method: "POST",
      data:{
        cmd: "add_ded_data",
        ded_id: $('input#ded_id').val(),
        ded_name: $('input#ded_name').val(),
        ded_type: ded_type,
        ded_sched: ded_sched
      },
      success: function (data){
        system_menu(18);
      },
      error: function (){
        w2alert("Sorry, there was a problem in server connection!");
      }
    })
  }

  function clear_this(){
    $('input#ded_id').val('');
    $('input#ded_name').val('');
    $('input#ded_option').w2field('list', { items: ded_opt });
    $('input#mid[value="1"]').click();
    $('input#end[value="2"]').click();
    $('#reset').addClass("w3-hide");
  }

</script>
