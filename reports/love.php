<?php
$program_code = 3;
define('INCLUDE_CHECK', true);
require_once('../common/functions.php');

?>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row" style="overflow-y: scroll;">
    <div class="w3-col s12 love-toolbar" style="height: 30px;"></div>
    <div class="w3-col s12 love-window w3-mobile w3-responsive"></div>
</div>
<script>
    const src = "page/reports";

        var config = {
                payperiod: [],
                group: [],
                toolbar: {
                    name: 'love_toolbar',
                    items: [],
                    onRender: function (event) {
                        event.onComplete = function () {
                            $(":input#paydate").w2field("list", {items: config.payperiod});
                            $(":input#pay_group").w2field("list", {items: config.group});
                        };
                    },
                    onClick: function (event) {
                        switch (event.target) {
                        case "gen":
                            get_records();
                            break;
                        case "print":
                            break;
                        case "export":
                            if (w2ui.love_grid.records.length > 0) {
                                let _date = $('#paydate').val();
                                let _group = $('#pay_group').w2field().get().id;
                                window.open("page/reports.php?paydate=" + _date + "&pay_group=" + _group + "&cmd=export-love");
                            } else {
                                w2alert("Please generate report!");
                            }
                            break;
                        }
                    }
                },
                love_grid: {
                    name: 'love_grid',
                    show: {
                        footer: true,
                        toolbarReload: false,
                        toolbar: true,
                        lineNumbers: true
                    },
                    onRender: function (event) {
                        event.onComplete = function () {
                            setTimeout(function () {
                                if (w2ui.toolbar) {
                                    w2ui.toolbar.destroy();
                                }
                                $("div.love-toolbar").w2toolbar(config.toolbar);
                            }, 500);
                        };
                    },
                    multiSelect: true,
                    columnGroups: [],
                    columns: []
                }
            };

            function get_records() {
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                    cmd: "get-loverecords",
                    paydate: $("#paydate").val(),
                    pay_group: $("#pay_group").w2field().get().id
                    },
                    dataType: "json",
                    success: function (jObject) {
                    console.log(jObject);
                    if (jObject.status === "success") {
                        w2ui.love_grid.clear();
                        w2ui.love_grid.add(jObject.records);
                    } else if (jObject.status === "error") {
                        w2alert(jObject.message);
                    }
                    },
                    error: function () {
                    alert("Sorry, there was a problem in server connection!");
                    }
                });
            }

    $(document).ready(function () {
        var c = $("div.love-window");
        var h = window.innerHeight - 185;
        c.css("height", h);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "set-grid-love"
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    config.group = jObject.group;
                    config.payperiod = jObject.cutoff;
                    config.toolbar.items = jObject.tool;
                    config.love_grid.columns = jObject.column;
                    $("div.love-window").w2grid(config.love_grid);
                } else if (jObject.status === "error") {
                    w2alert(jObject.message);
                }
            },
            error: function () {
                alert("Sorry, there was a problem in server connection!");
            }
        });
    });
  </script>