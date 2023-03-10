<?php
$program_code = 3;
require_once('../common/functions.php');

?>
<div class="w3-container w3-panel" style="width: 100%;">
	<div class="w3-container w3-padding-small">
		<input name="emp_list" id="emp_list" class="w3-small w3-padding-small" type="list" style="width: 20%;" />
		<button name="get_emp" id="get_emp" class="w2ui-btn w3-small" onclick="get_emp()">GET EMPLOYEE</button>
        <button name="show_all" id="show_all" class="w2ui-btn w3-small" onclick="show_all()">SHOW ALL EMPLOYEE's WITH DEDUCTIONS</button>
        <button name="getBack" id="getBack" class="w2ui-btn w3-small w3-right w3-hide" onclick="getBack_all()"><i class="fa-solid fa-rotate-left"></i>&nbsp;Close</button>
        <button name="clear_emp" id="clear_emp" class="w2ui-btn w3-small w3-hide" onclick="clear_emp()">CLEAR</button>
    </div>
    <div style="width: 100%; height: 450px;" id="emp_ded"></div>
    <div style="width: 100%; height: 450px;" id="emp_ded_all" class="w3-hide"></div>
</div>
<script type="text/javascript">

	$(document).ready(function(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/employee_deduction",
            type: "post",
            data: {
                cmd: "get-default-ded"
            },
            success: function (data){
                if (data !== ""){
                    $("#emp_ded").html(data);
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    });

</script>