<?php
$program_code = 8;
require_once('../common/functions.php');

?>
<style>
.credit {
    width: 250px;
    padding: 2px 4px 2px 4px;
    border: 0.5px solid silver;
    border-radius: 4px;
    outline: none;
    color: black;
}
</style>
<div class="w3-container w3-panel" style="width: 100%;" id="pay_adjust">
</div>
<script type="text/javascript">

	$(document).ready(function(){
        var c = $("div#pay_adjust");
        var h = window.innerHeight - 100;
        c.css("height", h);
        $(":input.date").w2field("date");
		var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/pay_adjustment",
            type: "post",
            data: {
                cmd: "get-adjust-default"
            },
            success: function (data){
                $('#pay_adjust').html(data);
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    });

</script>