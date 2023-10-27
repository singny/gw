<style>
.divBtnListSelectAppLine {
    display: -ms-flexbox!important;
    display: flex!important;
    -ms-flex-direction: column!important;
    flex-direction: column!important;
}
.divBtnListSelectAppLine .btn {
    margin-top: 1.5rem;
}
.divBtnListSelectAppLine .fa-angle-right {
    display: inline;
}
.divBtnListSelectAppLine .fa-angle-down {
    display: none;
}

@media (max-width: 767px) {
    .divBtnListSelectAppLine {
        -ms-flex-direction: row!important;
        flex-direction: row!important;
        -ms-flex-pack: distribute!important;
        justify-content: space-around!important;
    }
    .divBtnListSelectAppLine .fa-angle-right {
        display: none;
    }
    .divBtnListSelectAppLine .fa-angle-down {
        display: inline;
    }
}
</style>
<script>
$(document).ready(function(){
    //작업모드
    $("#mode").val("INIT");
    html = "";

    $("#divRowReceip").show();
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            var html = "";
            //결재라인 명
            $(result["appLineList"]).each(function(i, info) {
                html += '<option value="' + info["key"] + '">' + info["val"] + '</option>';
            });
            $("#ddlAppLine").append(html);

            $("#alSearchUser").data('oldVal', "");

            //부서 목록
            $("#alDeptList").append(result["deptList"]);
            $("#alDeptList > ul").treed();
            $("#alDeptList li").trigger('click');
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });

    //결재라인지정 창 닫기
    $("#modalEditAppLine").on('hide.bs.modal', function() {
        //상신 후 반영 버튼 비활성화
        $("#btnSaveAppLine").prop("disabled", true);
        //반영 버튼 비활성화
        $("#btnApplyAppLine").prop("disabled", true);

        //부서 선택 초기화
        $("#alDeptList input:checkbox").prop("checked", false);
        $("#alDeptList input:checkbox").prop("disabled", false);

        $("#alSearchUser").data('oldVal', "");
        $("#alSearchUser").val("");

        $('#tblALUserList').closest('div.tableFixHead-modal').scrollTop(0);
        $("#tblALUserList tbody").empty();

        //결재라인 명 - 직접선택
        $("#ddlAppLine").val("0");

        $("#listAppAgr").empty();
        $("#listOper").empty();
        $("#listReceip").empty();
        $("#listReceipDept").empty();
    });

    //IE일 경우
    if (!!navigator.userAgent.match(/Trident\/7\./)) {
         $("#modalEditAppLine").removeClass("fade");
         $("#modalROList").removeClass("fade");
         $("#modalROHis").removeClass("fade");
    }

    var thALUserList = $('#tblALUserList').find('thead th');
    $('#tblALUserList').closest('div.tableFixHead-modal').on('scroll', function() {
        thALUserList.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    //결재라인지정 - 결재라인 명 선택
    $("#ddlAppLine").on("change", onDdlAppLineChange);
    //사원 검색
    $("#alSearchUser").on("keydown", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키 입력
        if (cd == 13) {
            onBtnALSearchUserClick();
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    //프로젝트 멤버 검색
    $("#alSearchPjtMember").on("keydown", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키 입력
        if (cd == 13) {
            onBtnALSearchPjtMemberClick();
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    //결재라인지정 - 검색 버튼 클릭
    $("#btnALSearchUser").on("click", onBtnALSearchUserClick);
    //결재라인지정 - 프로젝트 멤버 검색 버튼 클릭
    $("#btnALSearchPjtMember").on("click", onBtnALSearchPjtMemberClick);
    //결재라인지정 - 결재 버튼 클릭
    $("#btnAddApp").on("click", {kind : '1'}, onBtnAddAppAgrClick);
    //결재라인지정 - 합의 버튼 클릭
    $("#btnAddAgr").on("click", {kind : '2'}, onBtnAddAppAgrClick);
    //결재라인지정 - 시행자 버튼 클릭
    $("#btnAddOper").on("click", {type : 'Oper'}, onBtnAddROClick);
    //결재라인지정 - 수신참조 버튼 클릭
    $("#btnAddReceip").on("click", {type : 'Receip'}, onBtnAddROClick);
    //결재라인지정 - 상신 후 반영 버튼 클릭
    $("#btnSaveAppLine").on("click", onBtnSaveAppLineClick);
    //결재라인지정 - 반영 버튼 클릭
    $("#btnApplyAppLine").on("click", onBtnApplyAppLineClick);

    $("#listAppAgr").sortable({
        cursor: "move",
        placeholder: "ui-state-highlight",
        change: function(event, ui) {
            //결재선 기안자 포함
            if ($("#appUserYn").val() == "1") {
                //첫 번째 결재자로 지정이 되어야 함
                if (ui.placeholder.index() < 1) {
                    $('.ui-state-disabled').after(ui.placeholder);
                }
            }
        },
        update: function(event, ui) {
            $("#listAppAgr li").each(function(index) {
                var no = index + 1;
                $(this).find("span.cntAppAgr").text(no + ". ");
            });
        }
    });
    $("#listOper").sortable({
        placeholder: "ui-state-highlight"
    });
    $("#listReceip").sortable({
        placeholder: "ui-state-highlight"
    });

    $('.nav-tabs a').on('shown.bs.tab', function(event){
        $("input[type='checkbox']").not("#alDeptList input[type='checkbox']").prop("checked", false);
    });
});

//결재라인지정
function onBtnSelectAppLineClick(actType, target, manageEaAppDoc = "N") {
    if ($('#modalEditAppLine').hasClass('show')) {
        return false;
    }
    $("#alManageEaAppDoc").val(manageEaAppDoc);
    if ($("#alManageEaAppDoc").val() == "Y") {
        $("#ddlAppLine").closest("div.row").hide();
    }
    else {
        $("#ddlAppLine").closest("div.row").show();
    }
    $("#chkAllALUser").prop("checked", false);
    $("#targetAppline").val(target);

    // 사내공문 (프로젝트 선택시)
    // if($("#recipientDeptNms").length || $("#txtRecipientDeptNms").length) {
        if($("#txtPjt_nm_fr").val() || $("#txtDFPJT").text()) {
            $(".tabALProject").show();
            var pjtNm = '';
            if($("#txtPjt_nm_fr").val()) {
                pjtNm = $("#txtPjt_nm_fr").val();
            } else {
                pjtNm = $("#txtDFPJT").text();
            }
            $("#pjtNm").val(pjtNm);
        } else {
            $(".tabALProject").hide();
        }
    // } else {
    //     $(".tabALProject").hide();
    // }

    //작업모드
    $("#mode").val("EDIT_APP_LINE");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            //사원 탭 표시
            $('#modalEditAppLine .nav-tabs li:eq(1) a').tab('show');

            showALUserList(result["userList"]);
            showALPjtMemberList(result["pjtMemberList"]);

            var essentialList = [];
            $("input[type='hidden'][name='essentialAppAgr[]']").each(function() {
                essentialList.push($(this).val());
            });
            //결재/합의
            $(result["appAgrList"]).each(function(i, info) {
                addAppLineUser("AppAgr", info["userNmDisp"], info["userId"], info["userVal"], info["appKind"], ($.inArray(info["userId"], essentialList) < 0));
            });

            //시행자 존재 시
            if ($("#operatorNms").length || $("#txtOperatorNms").length) {
                essentialList = [];
                $("input[type='hidden'][name='essentialOper[]']").each(function() {
                    essentialList.push($(this).val());
                });
                //시행자
                $(result["operatorList"]).each(function(i, info) {
                    addAppLineUser("Oper", info["userNmDisp"], info["userId"], info["userNm"], "", ($.inArray(info["userId"], essentialList) < 0));
                });
            }

            essentialList = [];
            $("input[type='hidden'][name='essentialReceip[]']").each(function() {
                essentialList.push($(this).val());
            });
            //수신참조
            $(result["recipientList"]).each(function(i, info) {
                var id = info["userId"];
                addAppLineUser("Receip", info["userNmDisp"], id, info["userNm"], "", ($.inArray(id, essentialList) < 0));
                if (id.substring(0, 1) == "D") {
                    // 수신부서
                    addAppLineUser("ReceipDept", info["userNmDisp"], id, info["userNm"], "", false);
                    $("#dept_AL_" + id + " > input[type='checkbox']").prop("checked", true);;
                    $("#dept_AL_" + id + " ul").find("input[type='checkbox']").prop("checked", true);
                    $("#dept_AL_" + id + " ul").find("input[type='checkbox']").prop("disabled", true);
                }
            });

            //상신 후
            if (actType == "save") {
                $("#btnSaveAppLine").closest("div").show();
                $("#btnApplyAppLine").closest("div").hide();
            }
            else if (actType == "apply") {
                $("#btnSaveAppLine").closest("div").hide();
                $("#btnApplyAppLine").closest("div").show();
            }

            //시행자
            if (target == "appAgr") {
                $("#ddlAppLine").prop("disabled", true);
                $("#divRowAppAgr").show();
                $("#divRowOper").hide();
                $("#divRowReceip").hide();
            }
            //시행자
            else if (target == "operator") {
                $("#ddlAppLine").prop("disabled", true);
                $("#divRowAppAgr").hide();
                $("#divRowOper").show();
                $("#divRowReceip").hide();
            }
            //수신참조
            else if (target == "recipient") {
                $("#ddlAppLine").prop("disabled", true);
                $("#divRowAppAgr").hide();
                $("#divRowOper").hide();
                $("#divRowReceip").show();
            }
            else {
                $("#ddlAppLine").prop("disabled", false);
                $("#divRowAppAgr").show();
                //시행자가 존재할 경우
                if ($("#operatorNms").length || $("#txtOperatorNms").length) {
                    $("#divRowOper").show();
                }
                else {
                    $("#divRowOper").hide();
                }
                // 수신부서가 존재할 경우
                if($("#recipientDeptNms").length || $("#txtRecipientDeptNms").length) {
                    $("#divRowReceipDept").show();
                } else {
                    $("#divRowReceipDept").hide();
                }
                $("#divRowReceip").show();
            }

            $("#modalEditAppLine").modal("show");
        },
        complete: function() {
            chkExistApp();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//결재자가 존재하면 반영 버튼 활성화
function chkExistApp() {
    var valid = false;
    if ($("#alManageEaAppDoc").val() == "Y") {
        if ($("#targetAppline").val() == "appAgr") {
            if ($("#listAppAgr li").length) {
                valid = true;
            }
        }
        else {
            valid = true;
        }
    }
    else {
        var type = "AppAgr";
        $("span[class^='kind_" + type + "_']").each(function(i) {
            //결재는 필수
            if ($(this).text() == 1) {
                valid = true;
                return false;
            }
        });
    }

    //상신 후 반영 버튼 활성화
    $("#btnSaveAppLine").prop("disabled", !valid);
    //반영 버튼 활성화
    $("#btnApplyAppLine").prop("disabled", !valid);
}

//상신 후 반영 버튼 클릭
function onBtnSaveAppLineClick() {
    $("input[type='hidden'][name='appAgrLine[]'").remove();
    var type = "AppAgr";
    if ($(".id_" + type).length > 0) {

        $("span[class^='kind_" + type + "_']").each(function(i) {
            var info = $(".val_" + type).eq(i).text();
            $("<input>").attr({
                type: "hidden",
                name: "appAgrLine[]",
                value : info + "|" + (i + 1)
            }).appendTo($("#mainForm"));
        });
    }

    //시행자
    type = "Oper";
    if ($(".id_" + type).length > 0) {
        var str = "";
        $(".id_" + type).each(function() {
            str += "/";
            str += $(this).text();
        });
        $("#operatorIds").val(str);
        str = "";
        $(".val_" + type).each(function() {
            str += "/";
            str += $(this).text();
        });
        $("#operatorNms").val(str);
    }
    else {
        $("#operatorIds").val("");
        $("#operatorNms").val("");
    }

    //수신참조
    type = "Receip";
    if ($(".id_" + type).length > 0) {
        var str = "";
        $(".id_" + type).each(function() {
            str += "/";
            str += $(this).text();
        });
        $("#recipientIds").val(str);
        str = "";
        $(".val_" + type).each(function() {
            str += "/";
            str += $(this).text();
        });
        $("#recipientNms").val(str);
    }
    else {
        $("#operatorIds").val("");
        $("#operatorNms").val("");
    }

    //작업모드
    $("#mode").val("SAVE_APP_LINE");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            onBtnDetailAppDocClick($("#docId").val(), $("#formId").val());

            $("#modalEditAppLine").modal("hide");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//반영 버튼 클릭
function onBtnApplyAppLineClick() {
    $("#setAppLine").val("");

    var type = "AppAgr";
    if ($("#alManageEaAppDoc").val() == "Y") {
        if ($("#targetAppline").val() == "appAgr") {
            $("#divAppLine").empty();
            $("input[type='hidden'][name='appAgrLine[]'").remove();

            if ($("#list" + type + " li").length) {
                var html = "";
                $("#list" + type + " li").each(function(i) {
                    html += $(this).clone().children().remove().end().text() + "<br />";
                    var info = $(".val_" + type).eq(i).text();
                    $("<input>").attr({
                        type: "hidden",
                        name: "appAgrLine[]",
                        value : info + "|" + (i + 1)
                    }).appendTo($("#mainForm"));
                });
                $("#divAppLine").append(html);
            }
            validateAppAgrLine();
        }
    }
    else {
        $("#divAppLine").empty();
        var appLine = {};
        var tempApp = [];
        var tempAgr = [];
        $("span[class^='kind_" + type + "_']").each(function(i) {
            var kind = $(this).text();
            var info = $(".val_" + type).eq(i).text();
            infos = info.split("|");
            var temp = {
                docUserId: infos[0],
                appLevel: (i + 1),
                signYn: "0",
                signKind: "0",
                signKindNm: "",
                signKindColor: "",
                signImg: "appOrder",
                signUserNm: "",
                signDetail: infos[3],
                appGradeDutyNm: ($("#appType").val() != 2)?infos[5]:infos[4],
                appAgrValue: info + "|" + (i + 1)
            };
            if (kind == 1 || kind == 5) {
                tempApp.push(temp);
            }
            else if (kind == 2 || kind == 6) {
                tempAgr.push(temp);
            }
        });
        appLine["app"] = tempApp;
        appLine["agr"] = tempAgr;
        var html = drawAppLine(appLine);
        $("#divAppLine").append(html);
        validateAppLine();
        showAppLineMsg();
    }

    if (($("#alManageEaAppDoc").val() == "Y" && $("#targetAppline").val() == "operator") || $("#alManageEaAppDoc").val() == "N") {
        //시행자
        type = "Oper";
        if ($(".id_" + type).length > 0) {
            var str = "";
            $(".id_" + type).each(function() {
                str += "/";
                str += $(this).text();
            });
            $("#operatorIds").val(str);
            str = "";
            $(".val_" + type).each(function() {
                str += "/";
                str += $(this).text();
            });
            $("#operatorNms").val(str);
        }
        else {
            $("#operatorIds").val("");
            $("#operatorNms").val("");
        }
        if ($("#operatorNms").length) {
            if ($("#operatorNms")[0].hasAttribute("required")) {
                validateElement("operatorNms");
            }
        }
    }

    if (($("#alManageEaAppDoc").val() == "Y" && $("#targetAppline").val() == "recipient") || $("#alManageEaAppDoc").val() == "N") {
        //수신참조
        type = "Receip";
        if ($(".id_" + type).length > 0) {
            var str = "";
            $(".id_" + type).each(function() {
                str += "/";
                str += $(this).text();
            });
            $("#recipientIds").val(str);
            str = "";
            $(".val_" + type).each(function() {
                str += "/";
                str += $(this).text();
            });
            $("#recipientNms").val(str);
        }
        else {
            $("#recipientIds").val("");
            $("#recipientNms").val("");
        }
        if ($("#recipientNms")[0].hasAttribute("required")) {
            validateElement("recipientNms");
        }

        //수신부서
        if($("#recipientDeptNms").length > 0) {
            type = "ReceipDept";
            if ($(".id_" + type).length > 0) {
                str = "";
                $(".val_" + type).each(function() {
                    str += "/";
                    str += $(this).text();
                });
                $("#recipientDeptNms").val(str);
            }
        }
    }

    if ($("#alManageEaAppDoc").val() == "N") {
        //휴가신청서
        if ($("#formId").val() == 10012) {
            //시행자 변경 시
            countAnnualVacation();
        }
        $("#detectEditAppDoc").val("Y");
    }

    $("#modalEditAppLine").modal("hide");
}

//결재라인 명 선택
function onDdlAppLineChange() {
    //작업모드
    $("#mode").val("LIST_APP_LINE");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php", 
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var isExist = false, overApp = false, overAgr = false, msgList = [], overCnt = 0;
            var appAgrCnt = $("#appAgrCnt").val();
            $("#listAppAgr li").not("li.ui-state-disabled").remove();
//             var selList = [];
//             $("#listAppAgr").find(".id_AppAgr").each(function() {
//                 selList.push($(this).text());
//             });
            //결재/합의
            $(result["appAgrList"]).each(function(i, info) {
//                 if (($.inArray(info["userId"], selList) > -1)) {
//                     isExist = true;
//                     return true;
//                 }
                var cnt = $("span.kind_AppAgr_" + info["appKind"]).length;
                if (cnt >= appAgrCnt) {
                    if (info["appKind"] == 1) {
                        overApp = true;
                    }
                    else if (info["appKind"] == 1) {
                        overAgr = true;
                    }
                    return true;
                }
                addAppLineUser("AppAgr", info["userNmDisp"], info["userId"], (info["userVal"] + "|" + info["appKind"]), info["appKind"], true);
            });
            if (isExist) {
//                 msgList.push("저장된 결재라인과 문서의 결재라인에 중복된 사용자가 있어 삭제되었습니다.");
                if(overCnt == 0) {
                    msgList.push("결재라인 선택시 중복 인원은 제외되고 지정됩니다.");
                    overCnt ++;
                }
            }
            if (overApp) {
                msgList.push("[결재]는 최대 " + appAgrCnt + "명 입니다");
            }
            if (overAgr) {
                msgList.push("[합의]는 최대 " + appAgrCnt + "명 입니다");
            }

            //시행자 존재 시
            if ($("#operatorNms").length || $("#txtOperatorNms").length) {
                isExist = false;
                selList = [];
                $("#listOper").find(".id_Oper").each(function() {
                    selList.push($(this).text());
                });
                //시행자
                $(result["operatorList"]).each(function(i, info) {
                    if (($.inArray(info["userId"], selList) > -1)) {
                        isExist = true;
                        return true;
                    }
                    addAppLineUser("Oper", info["userNmDisp"], info["userId"], info["userNm"], "", true);
                });
                if (isExist) {
//                     msgList.push("저장된 시행자와 문서의 시행자에 중복된 사용자가 있어 삭제되었습니다.");
                    if(overCnt == 0) {
                        msgList.push("결재라인 선택시 중복 인원은 제외되고 지정됩니다.");
                        overCnt ++;
                    }
                }
            }

            isExist = false;
            selList = [];
            $("#listReceip").find(".id_Receip").each(function() {
                var arr = ($(this).text()).split("|");
                //부서 관계없이 사원만 비교
                selList.push(arr[0]);
            });
            //수신참조
            $(result["recipientList"]).each(function(i, info) {
                var id = info["userId"];
                var arr = id.split("|");
                //부서 관계없이 사원만 비교
                if (($.inArray(arr[0], selList) > -1)) {
                    isExist = true;
                    return true;
                }
                addAppLineUser("Receip", info["userNmDisp"], id, info["userNm"], "", true);
                if (id.substring(0, 1) == "D") {
                    // 수신부서
                    addAppLineUser("ReceipDept", info["userNmDisp"], id, info["userNm"], "", false);
                    $("#dept_AL_" + id + " > input[type='checkbox']").prop("checked", true);;
                    $("#dept_AL_" + id + " ul").find("input[type='checkbox']").prop("checked", true);
                    $("#dept_AL_" + id + " ul").find("input[type='checkbox']").prop("disabled", true);

                    var deptIdList = []; 
                    $("#dept_AL_" + id + " ul").find("li").each(function(){
                        var ids = this.id.split("_");
                        deptIdList.push(ids[1]);
                    });
                    if (deptIdList.length > 0) {
                        $("#listReceip").find(".id_Receip").each(function() {
                            if ($.inArray($(this).text(), deptIdList) > -1) {
                                $(this).closest("li").remove();
                            }
                        });
                    }
                }
            });
            if (isExist) {
//                 msgList.push("저장된 수신참조자와 문서의 수신참조자에 중복된 사용자가 있어 삭제되었습니다.");
                if(overCnt == 0) {
                    msgList.push("결재라인 선택시 중복 인원은 제외되고 지정됩니다.");
                    overCnt ++;
                }
            }

            if (msgList.length > 0) {
                // alert(msgList.join("\n"));
                $("#alertAppline .modal-body").text(msgList.join("\n"));

                $("#alertAppline").modal("show");
            }
        },
        complete: function() {
            chkExistApp();
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//사원 검색 버튼 클릭
function onBtnALSearchUserClick() {
    var elem = $("#alSearchUser");
    elem.val(elem.val().trim());
    if (elem.data("oldVal") != elem.val()) {
        elem.data('oldVal', elem.val());

        $("#tblALUserList tbody").empty();

        //작업모드
        $("#mode").val("APP_LINE_SEARCH_USER");
        $.ajax({ 
            type: "POST", 
            url: "/gw/cm/cm_select_appline.php", 
            data: $("#mainForm").serialize(),
            dataType: "json",  
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                showALUserList(result["userList"]);
            },
            beforeSend:function(){
                $("#modalEditAppLine").find("input").prop("disabled", true);
                $("#modalEditAppLine").find("select").prop("disabled", true);
                $("#modalEditAppLine").find("button:not(#btnSaveAppLine,#btnApplyAppLine)").prop("disabled", true);
                $("#btnALSearchUser").find("span.spinner-border").show();
            },
            complete: function() {
                $("#modalEditAppLine").find("input").prop("disabled", false);
                $("#modalEditAppLine").find("select").prop("disabled", false);
                $("#modalEditAppLine").find("button:not(#btnSaveAppLine,#btnApplyAppLine)").prop("disabled", false);
                $("#btnALSearchUser").find("span.spinner-border").hide();
            },
            error: function (request, status, error) {
                alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });
    }
}

//사원 목록
function showALUserList(list) {
    //체크박스 초기화
    $("#chkAllALUser").prop('checked', false);
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr id="trALUser_' + info["userId"] + '" class="row" ondblclick="onAddAppAgrDblclick(this)">';
        html += '<td class="col-md-1 col-2 col-w-chk">';
        html += '<input type="checkbox" name="chkALUser[]" value="' + info["userId"] + '" onclick="whenChkClick_chkAll(\'chkALUser\', \'chkAllALUser\');">';
        html += '<span class="userId" style="display: none;">' + info["userId2"] + '</span>';
        html += '<span class="userValue" style="display: none;">' + info["userVal"] + '</span>';
        html += '<span class="userDisp" style="display: none;">' + info["userDisp"] + '</span>';
        html += '</td>';
        html += '<td class="col-md col notAlign">';
        //부서
        html += info["deptNm"];
        html += '</td>';
        html += '<td class="col-md-3 d-none d-md-block">';
        //직급(직책)
        html += info["gradeDuty"];
        html += '</td>';
        html += '<td class="col-md-3 col-4">';
        //사원명
        html += info["userNm"];
        html += '</td>';
        html += '</tr>';
    });
    $("#tblALUserList tbody").append(html);
}

// 프로젝트 조직도
function showALPjtMemberList(list) {
    $("#chkAllALUser").prop('checked', false);
    var html = "";
    $(list).each(function(i, info) {
        html += '<tr id="trALUser_' + info["userId"] + '" class="row" ondblclick="onAddAppAgrDblclick(this)">';
        html += '<td class="col-md-1 col-2 col-w-chk">';
        html += '<input type="checkbox" name="chkALUser[]" value="' + info["userId"] + '" onclick="whenChkClick_chkAll(\'chkALUser\', \'chkAllALUser\');">';
        html += '<span class="userId" style="display: none;">' + info["userId2"] + '</span>';
        html += '<span class="userValue" style="display: none;">' + info["userVal"] + '</span>';
        html += '<span class="userDisp" style="display: none;">' + info["userDisp"] + '</span>';
        html += '</td>';
        html += '<td class="col-md col notAlign">';
        //부서
        html += info["funcTitle"];
        html += '</td>';
        html += '<td class="col-md-3 d-none d-md-block">';
        //직급(직책)
        html += info["gradeNm"];
        html += '</td>';
        html += '<td class="col-md-3 col-4">';
        //사원명
        html += info["memberNm"];
        html += '</td>';
        html += '</tr>';
    });
    $("#tblALPjtMemberList tbody").empty().append(html);
}

//부서 선택 시
function onDeptClick_AL(deptId, kind, deptCd, deptNm) {

}

//결재라인 직원 추가
function addAppLineUser(type, label, id, val, kind, del) {
    var cnt = 0;
    if (type == "AppAgr") {
        cnt = $("#list" + type).find("li").length + 1;
    }
    var html = "";
    html += "<li class='ui-state-default";
    //결재/합의에서 결재선 기안자 포함 일 경우
    if (type == "AppAgr" && $("#appUserYn").val() == "1" && id == $("#drafter").val()) {
        html += " ui-state-disabled"
    }
    html += "'>";
    if (type == "AppAgr") {
        html += '<span class="cntAppAgr">' + cnt + ". </span>";
    }
    html += label;
    html += '<span class="id_' + type + '" style="display: none;">' + id + '</span>';
    //결재/합의 일 경우
    if (type == "AppAgr") {
        html += '<span class="kind_' + type + '_' + kind + '" style="display: none;">' + kind + '</span>';
    }
    html += '<span class="val_' + type + '" style="display: none;">' + val + '</span>';
    if (del) {
        html += "<button type='button' name='btnDel' class='btn btn-warning btn-sm' onclick='onBtnDelAppLineUserClick(this);'>삭제</button>";
    }
    html += "</li>";
    $("#list" + type).append(html);
}

//결재라인 직원 삭제
function onBtnDelAppLineUserClick(obj) {
    var deptId = $(obj).closest("li").find("span.id_Receip").text();
    if (deptId.startsWith('D')) {
        $("#dept_AL_" + deptId).find("input[type='checkbox']").prop("checked", false);
        $("#dept_AL_" + deptId).find("input[type='checkbox']").prop("disabled", false);

        $("#listReceipDept").find(".id_ReceipDept").each(function() {
            if ($(this).text() == deptId) {
                $(this).closest("li").remove();
                return true;
            }
        });
    }

    $(obj).closest("li").remove();
}

//직원 더블클릭
function onAddAppAgrDblclick(obj) {
    if ($('#divRowAppAgr').is(':visible')) {
        var kind = 1, kindLabel = "[결재]";
        var type = "AppAgr";
        var appAgrCnt = $("#appAgrCnt").val();
        var cnt = $("span.kind_" + type + '_' + kind).length;
        var appAgrList = [];
        $("span.id_" + type).each(function() {
            appAgrList.push($(this).text());
        });
        var userId = $(obj).find(".userId").text();
        if ($.inArray(userId, appAgrList) > -1) {
            return false;
        }
        if (cnt >= appAgrCnt) {
            $("#alertAppline .modal-body").text(kindLabel + "는 최대 " + appAgrCnt + "명 입니다");
            $("#alertAppline").modal("show");
            return false;
        }
        var userValue = $(obj).find(".userValue").text();
        userValue += "|" + kind;
        var label = kindLabel + " " + $(obj).find(".userDisp").text();
        addAppLineUser(type, label, userId, userValue, kind, true);

        chkExistApp();
    }
}

//결재/합의 버튼 클릭
function onBtnAddAppAgrClick(e) {
    var kind = e.data.kind;
    var kindLabel = "";
    if (kind == 1) {
        kindLabel = "[결재]";
    }
    else if (kind == 2) {
        kindLabel = "[합의]";
    }
    var type = "AppAgr";
    var appAgrCnt = $("#appAgrCnt").val();
    var cnt = $("span.kind_" + type + '_' + kind).length;
    var appAgrList = [];
    $("span.id_" + type).each(function() {
        appAgrList.push($(this).text());
    });
    //직급이 높은 직원이 결재 후순위로 위치
    $($("input[type='checkbox'][name='chkALUser[]']:checked").get().reverse()).each(function() {
        var id = $(this).val();
        var tr = $("#trALUser_" + id);
        var userId = tr.find(".userId").text();
        if ($.inArray(userId, appAgrList) > -1) {
            return true;
        }
        if (cnt >= appAgrCnt) {
            $("#modalAlertMsg .modal-body").html(kindLabel + "는 최대 " + appAgrCnt + "명 입니다");
            $("#modalAlertMsg").modal("show");
            return false;
        }
        var userValue = tr.find(".userValue").text();
        userValue += "|" + kind;
//       var label = kindLabel + " " + tr.find("td:eq(1)").text() + "|" + tr.find("td:eq(2)").text() + "|" + tr.find("td:eq(3)").text();
        var label = kindLabel + " " + tr.find(".userDisp").text();
        addAppLineUser(type, label, userId, userValue, kind, true);
        cnt++;
    });
    $("#chkAllALUser").prop("checked", false);
    onChkAllClick($("#chkAllALUser"), "chkALUser");

    chkExistApp();
}

//시행자 버튼, 수신참조 버튼 클릭
function onBtnAddROClick(e) {
    var type = e.data.type;
    var roList = [];
    $("span.id_" + type).each(function() {
        var id = $(this).text();
        //수신참조일 경우
        if (type == "Receip") {
            var arr = id.split("|");
            //부서 관계없이 사원만 비교
            id = arr[0];
        }
        roList.push(id);
    });

    // 탭
    var target = $(".nav-tabs .active").attr("href");

    $(target + " input[type='checkbox'][name='chkALUser[]']:checked").each(function() {
        var id = $(this).val();
        var tr = $("#trALUser_" + id);
        var userId = tr.find(".userId").text();
        var uId = userId;
        //수신참조일 경우
        if (type == "Receip") {
            var arr = userId.split("|");
            //부서 관계없이 사원만 비교
            uId = arr[0];
        }
        if ($.inArray(uId, roList) > -1) {
            return true;
        }
        var userValue = tr.find("td:eq(3)").text();
        var label = tr.find(".userDisp").text();
        addAppLineUser(type, label, userId, userValue, "", true);
    });
    $("#chkAllALUser").prop("checked", false);
    onChkAllClick($("#chkAllALUser"), "chkALUser");
}

//부서 선택 시
function onChkDeptClick_AL(deptId) {
    //선택된 경우
    if ($("#dept_AL_" + deptId + " > input[type='checkbox']").is(":checked")) {
        $("#dept_AL_" + deptId + " ul").find("input[type='checkbox']").prop("checked", true);
        $("#dept_AL_" + deptId + " ul").find("input[type='checkbox']").prop("disabled", true);

        var deptIdList = []; 
        $("#dept_AL_" + deptId + " ul").find("li").each(function(){
            var ids = this.id.split("_");
            deptIdList.push(ids[1]);
        });
        if (deptIdList.length > 0) {
            $("#listReceip").find(".id_Receip").each(function() {
                if ($.inArray($(this).text(), deptIdList) > -1) {
                    $(this).closest("li").remove();
                }
            });
            $("#listReceipDept").find(".id_ReceipDept").each(function() {
                if ($.inArray($(this).text(), deptIdList) > -1) {
                    $(this).closest("li").remove();
                }
            });
        }
        var li = $("#dept_AL_" + deptId);
        var val = li.find("a").first().text();
        addAppLineUser("Receip", li.find("a").first().text(), deptId, val, "", true);
        addAppLineUser("ReceipDept", li.find("a").first().text(), deptId, val, "", false);
    }
    //선택 해제된 경우
    else {
        $("#dept_AL_" + deptId + " ul").find("input[type='checkbox']").prop("checked", false);
        $("#dept_AL_" + deptId + " ul").find("input[type='checkbox']").prop("disabled", false);
        $("#listReceip").find(".id_Receip").each(function() {
            if ($(this).text() == deptId) {
                $(this).closest("li").remove();
                return true;
            }
        });
        $("#listReceipDept").find(".id_ReceipDept").each(function() {
            if ($(this).text() == deptId) {
                $(this).closest("li").remove();
                return true;
            }
        });
    }
}

//수신참조 리스트
function onBtnListRecipientClick(docId) {
    $("#tblRecipientList tbody").empty();
    $("#tblOperatorList").closest("div").hide();

    $("#roDocId").val(docId);
    //작업모드
    $("#mode").val("LIST_RECIPIENT");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            $("#txtReadYn").text(result["txtReadYn"]);

            var html = "";
            $(result["ROList"]).each(function(i, info) {
                html += '<tr>';
                html += '<td>' + info["deptName"] + '</td>';
                html += '<td>' + info["userName"] + '</td>';
                html += '<td>' + info["readYn"] + '</td>';
                html += '<td>' + info["firstReadDate"] + '</td>';
                html += '<td>' + info["editedDate"] + '</td>';
                html += '</tr>';
            });
            $("#tblRecipientList tbody").append(html);
            $("#tblRecipientList").closest("div").show();

            $("#modalROList .modal-title").text("수신참조");
            $("#modalROList").modal("show");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//시행자 리스트
function onBtnOperatorListClick(docId) {
    $("#tblOperatorList tbody").empty();
    $("#tblRecipientList").closest("div").hide();

    $("#roDocId").val(docId);
    //작업모드
    $("#mode").val("LIST_OPERATOR");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var html = "";
            $(result["ROList"]).each(function(i, info) {
                html += '<tr>';
                html += '<td>' + info["deptName"] + '</td>';
                html += '<td>' + info["userName"] + '</td>';
                html += '<td>' + info["recChkName"] + '</td>';
                html += '<td>' + info["note"] + '</td>';
                html += '</tr>';
            });
            $("#tblOperatorList tbody").append(html);
            $("#tblOperatorList").closest("div").show();

            $("#modalROList .modal-title").text("시행자");
            $("#modalROList").modal("show");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//수정 내역 목록
function onBtnListROHisClick(target) {
    $("#targetAppline").val(target);
    $("#tblListROHis tbody").empty();
    $("#tblROHis tbody").empty();

    //작업모드
    $("#mode").val("LIST_RO_HIS");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var html = "";
            $(result["hisList"]).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-2">';
                html += '<div class="h-100 d-flex align-items-center justify-content-center">';
                html += (i + 1);
                html += '</div>';
                html += '</td>';
                html += '<td class="col">';
                html += '<div class="h-100 d-flex align-items-center justify-content-center">';
                html += info["createdDate"];
                html += '</div>';
                html += '</td>';
                html += '<td class="col-3 col-w-btn">';
                html += '<div class="h-100 d-flex align-items-center justify-content-center">';
                html += '<button type="button" class="btn btn-primary" onclick="onBtnDetailROHisClick(\'' + info["step"] + '\')">상세</button>';
                html += '</div>';
                html += '</td>';
                html += '</tr>';
            });
            $("#tblListROHis tbody").append(html);

            var title = "";
            if (target == "recipient") {
                title = "수신참조 수정 이력";
            }
            else if (target == "operator") {
                title = "시행자 수정 이력";
            }
            $("#modalROHis .modal-title").text(title);
            $("#modalROHis").modal("show");
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

//수정 내역 상세
function onBtnDetailROHisClick(step) {
    $("#stepHistory").val(step);
    $("#tblROHis tbody").empty();

    //작업모드
    $("#mode").val("DETAIL_RO_HIS");
    $.ajax({ 
        type: "POST", 
        url: "/gw/cm/cm_select_appline.php",
        data: $("#mainForm").serialize(),
        dataType: "json",  
        success: function(result) {
            //세션 만료일 경우
            if (result["session_out"]) {
                //로그인 화면으로 이동
                onLogoutClick();
            }

            var html = "";
            $(result["ROList"]).each(function(i, info) {
                html += '<tr class="row">';
                html += '<td class="col-8 text-center">';
                html += info["deptName"];
                html += '</td>';
                html += '<td class="col-4 text-center">';
                html += info["workName"];
                html += '</td>';
                html += '</tr>';
            });
            $("#tblROHis tbody").append(html);
        },
        error: function (request, status, error) {
            alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });
}

// 프로젝트 멤버 검색
function onBtnALSearchPjtMemberClick() {
    //작업모드
    $("#mode").val("APP_LINE_PJT_MEMBER");

    var elem = $("#alSearchPjtMember");
    elem.val(elem.val().trim());
    if (elem.data("oldVal") != elem.val()) {
        elem.data('oldVal', elem.val());

        //작업모드
        $("#mode").val("APP_LINE_PJT_MEMBER");
        $.ajax({ 
            type: "POST", 
            url: "/gw/cm/cm_select_appline.php", 
            data: $("#mainForm").serialize(),
            dataType: "json",  
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                showALPjtMemberList(result["pjtMemberList"]);
            },
            beforeSend:function(){
                $("#modalEditAppLine").find("input").prop("disabled", true);
                $("#modalEditAppLine").find("select").prop("disabled", true);
                $("#modalEditAppLine").find("button:not(#btnSaveAppLine,#btnApplyAppLine)").prop("disabled", true);
                $("#btnALSearchPjtMember").find("span.spinner-border").show();
            },
            complete: function() {
                $("#modalEditAppLine").find("input").prop("disabled", false);
                $("#modalEditAppLine").find("select").prop("disabled", false);
                $("#modalEditAppLine").find("button:not(#btnSaveAppLine,#btnApplyAppLine)").prop("disabled", false);
                $("#btnALSearchPjtMember").find("span.spinner-border").hide();
            },
            error: function (request, status, error) {
                alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });
    }
}
</script>

<div class="modal fade" id="modalEditAppLine" data-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="row" style="flex: 1;">
                    <div class="col">
                        <h4 class="modal-title text-nowrap">결재라인지정</h4>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-end">
                            <div style="display: none;">
                                <button type="button" class="btn btn-primary" id="btnSaveAppLine" name="btnSaveAppLine">반영</button>
                            </div>
                            <div style="display: none;">
                                <button type="button" class="btn btn-primary" id="btnApplyAppLine" name="btnApplyAppLine">반영</button>
                            </div>
                            <!-- 
                            <div style="margin-left: 1rem;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                            </div>
                            -->
                        </div>
                    </div>
                </div>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="nav nav-tabs nav-pills mx-3">
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabALDept">부서</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tabALUser">이름</a>
                            </li>
                            <li class="nav-item tabALProject">
                                <a class="nav-link" data-toggle="tab" href="#tabALProject">조직도</a>
                            </li>
                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div id="tabALUser" class="container tab-pane">
                                <div class="input-group my-1">
                                    <input type="text" class="form-control" id="alSearchUser" name="alSearchUser" maxlength="30" placeholder="부서/이름 검색"> 
                                    <div class="input-group-append">
                                        <button type="button" id="btnALSearchUser" class="btn btn-info">
                                            <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                                            <span class="fas fa-magnifying-glass"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="tableFixHead-modal">
                                    <table id="tblALUserList" class="table">
                                        <thead class="thead-light">
                                            <tr class="row">
                                                <th class="col-md-1 col-2 col-w-chk"><input type="checkbox" id="chkAllALUser" onclick="onChkAllClick(this, 'chkALUser');" /></th>
                                                <th class="col-md col">부서</th>
                                                <th class="col-md-3 d-none d-md-block">직급(직책)</th>
                                                <th class="col-md-3 col-4">이름</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <br />
                            </div>
                            <div id="tabALDept" class="container tab-pane">
                                <div id="alDeptList" class="blockList" style="margin-top: 2rem;"></div>
                                <br />
                            </div>
                            <div id="tabALProject" class="container tab-pane">
                                <div class="input-group my-1">
                                    <input type="text" class="form-control" id="alSearchPjtMember" name="alSearchPjtMember" maxlength="30" placeholder="공종/이름 검색"> 
                                    <div class="input-group-append">
                                        <button type="button" id="btnALSearchPjtMember" class="btn btn-info">
                                            <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                                            <span class="fas fa-magnifying-glass"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="tableFixHead-modal">
                                    <table id="tblALPjtMemberList" class="table">
                                        <thead class="thead-light">
                                            <tr class="row">
                                                <th class="col-md-1 col-2 col-w-chk"><input type="checkbox" id="chkAllALPjtMember" onclick="onChkAllClick(this, 'chkALUser');" /></th>
                                                <th class="col-md col">공종</th>
                                                <th class="col-md-3 d-none d-md-block">직급(직책)</th>
                                                <th class="col-md-3 col-4">이름</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <br />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row" style="padding-right: 15px;padding-left: 15px;">
                            <div class="col-md-2 col-4 pr-0">
                                <label for="ddlAppLine" class="colHeader">결재라인명</label>
                            </div>
                             <div class="col-md-10 col-8">
                                <select class="form-control" id="ddlAppLine" name="ddlAppLine">
                                    <option value="0">결재라인선택</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="divRowAppAgr" style="padding-right: 15px;padding-left: 15px;">
                            <div class="col-md-2 p-0 divBtnListSelectAppLine">
                                <button type="button" class="btn btn-info" id="btnAddApp" name="btnAddApp">결재 <i class="fa-solid fa-angle-right"></i><i class="fa-solid fa-angle-down"></i></button>
                                <button type="button" class="btn btn-info" id="btnAddAgr" name="btnAddAgr">합의 <i class="fa-solid fa-angle-right"></i><i class="fa-solid fa-angle-down"></i></button>
                            </div>
                            <div class="col-md-10" style="min-height: 25rem;">
                                <span class="colHeader">결재라인</span>
                                <ul id="listAppAgr" class="selUserDeptList"></ul>
                            </div>
                        </div>
                        <div class="row" id="divRowOper" style="padding-right: 15px;padding-left: 15px;">
                            <div class="col-md-2 p-0 divBtnListSelectAppLine">
                                <button type="button" class="btn btn-info" id="btnAddOper" name="btnAddOper">시행자 <i class="fas fa-angle-right"></i><i class="fa-solid fa-angle-down"></i></button>
                            </div>
                            <div class="col-md-10" style="min-height: 4rem;">
                                <span class="colHeader">시행자</span>
                                <ul id="listOper" class="selUserDeptList"></ul>
                            </div>
                        </div>
                        <div class="row" id="divRowReceip" style="padding-right: 15px;padding-left: 15px;">
                            <div class="col-md-2 p-0 divBtnListSelectAppLine">
                                <button type="button" class="btn btn-info" id="btnAddReceip" name="btnAddReceip">수신참조 <i class="fas fa-angle-right"></i><i class="fa-solid fa-angle-down"></i></button>
                            </div>
                            <div class="col-md-10" style="min-height: 16.5rem;">
                                <span class="colHeader">수신참조</span>
                                <ul id="listReceip" class="selUserDeptList"></ul>
                            </div>
                        </div>
                        <div class="row" id="divRowReceipDept" style="padding-right: 15px;padding-left: 15px;display:none">
                            <div class="col-md-2 p-0 divBtnListSelectAppLine">
                                <button type="button" class="btn btn-info" id="btnAddReceipDept" name="btnAddReceipDept">수신부서 <i class="fas fa-angle-right"></i><i class="fa-solid fa-angle-down"></i></button>
                            </div>
                            <div class="col-md-10" style="min-height: 16.5rem;">
                                <span class="colHeader">수신부서</span>
                                <ul id="listReceipDept" class="selUserDeptList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalROList" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title text-nowrap"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <!-- 수신참조 -->
                <div class="table-responsive" style="display: none;">
                    <div class="d-flex justify-content-end">
                        <div style="margin-right: 1rem;">
                            <span id="txtReadYn"></span>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered" id="tblRecipientList">
                        <thead class="thead-light">
                            <tr>
                                <th>부서</th>
                                <th>이름</th>
                                <th>열람여부</th>
                                <th>최초열람</th>
                                <th>마지막열람</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <!-- 시행자 -->
                <div class="table-responsive" style="display: none;">
                    <table class="table table-sm table-bordered" id="tblOperatorList">
                        <thead class="thead-light">
                            <tr>
                                <th>부서</th>
                                <th>이름</th>
                                <th>시행관리</th>
                                <th>기타사항</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 수정 내역 창 -->
<div class="modal fade" id="modalROHis" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title text-nowrap"></h4>
                <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <table class="table" id="tblListROHis">
                            <thead class="thead-light">
                                <tr class="row">
                                    <th class="col-2">No</th>
                                    <th class="col">수정일자</th>
                                    <th class="col-3 col-w-btn">상세</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div> 
                    <div class="col-md-8">
                        <table class="table" id="tblROHis">
                            <thead class="thead-light">
                                <tr class="row">
                                    <th class="col-8">부서</th>
                                    <th class="col-4">이름</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <div class="container-fluid">
                    <div class="d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 알림창 -->
<div class="modal fade" id="alertAppline">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
        
        <!-- Modal Header -->
        <!-- <div class="modal-header">
            <h4 class="modal-title">Modal Heading</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
         -->
        <!-- Modal body -->
        <div class="modal-body"></div>
        
        <!-- Modal footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">확인</button>
        </div>
        
        </div>
    </div>
</div>

<input type="hidden" id="roDocId" name="roDocId" />
<input type="hidden" id="targetAppline" name="targetAppline" />
<input type="hidden" id="alManageEaAppDoc" name="alManageEaAppDoc" />
<input type="hidden" id="stepHistory" name="stepHistory" />
<input type="hidden" id="pjtNm" name="pjtNm" />
