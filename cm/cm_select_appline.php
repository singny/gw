<?php 
require_once "../../lib/include.php";
require_once "../common/biz_ini.php";
require_once "../common/func.php";


/* BEGIN DEBUG 환경 구성 */
ini_set("display_errors", "On");
//@error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
error_reporting(E_ALL & ~E_NOTICE);

$RemoteAddr = $_SERVER["REMOTE_ADDR"];
$isDebug = false;
if($RemoteAddr == "10.10.103.221")
{
	$isDebug = false;
}
if($isDebug == true)
{
	require_once _LIB_PATH_ . "fun.class.php";
	//unset($_SESSION["user"]);
	if ( !isset($_SESSION["user"]) || !is_array($_SESSION["user"]) || !$_SESSION["user"] || count($_SESSION["user"]) <= 0 )
	{
		$isLogin = $user->setLogin("jhpark", "Cimage1004@", $login_message);		
	}
	
	//$Fun->print_r($members);
	$mode = $_POST["mode"];
	if($mode == "APP_LINE_SEARCH_USER")
	{
		
	}
	
	//exit;
}
/* END DEBUG 환경 구성 */

//세션 만료일 경우
if (!isset($_SESSION["user"]["uno"])) {
    echo json_encode(array("session_out" => true));
    //종료
    exit();
}

//작업모드
$mode = $_POST["mode"];

$members = $user->getUserMemberAll();
$depts = $user->getUserDeptInfo();
if($isDebug && isset($userDB) && $user)
{
    //print_r($depts);
    //exit;
}

if ("SAVE_APP_LINE" == $mode) {
    $targetAppline = $_POST["targetAppline"];
    //결재문서 고유번호
    $docId = $_POST["docId"];
    //결재/합의
    $appAgrLine = $_POST["appAgrLine"];
    //결재/합의 결재 정보
    $appAgrLineSign = $_POST["appAgrLineSign"];
    //이관문서함
    $ebMenuId = $_POST["ebMenuId"];
    //수신참조
    $recipientIds = $_POST["recipientIds"];
    //시행자
    $operatorIds = $_POST["operatorIds"];

    $proceed = true;

    if ("all" == $targetAppline) {
        $appAgrLineSignList = array();
        foreach($appAgrLineSign as $line) {
            $temp = explode("|", $line);
            $appAgrLineSignList[$temp[0]] = array(
                "signKind" => $temp[1],
                "signYn" => $temp[2]
            );
        }

        $appAgrLineList = array();
        foreach($appAgrLine as $line) {
            $temp = explode("|", $line);
            $appAgrLineList["userId"][] = $temp[0];
            $appAgrLineList["coId"][] = $temp[1];
            $appAgrLineList["deptId"][] = $temp[2];
            $appAgrLineList["gradeNm"][] = $temp[4];
            $appAgrLineList["dutyNm"][] = $temp[5];
            $appAgrLineList["kind"][] = $temp[6];
            $appAgrLineList["level"][] = $temp[7];
            if (array_key_exists($temp[0], $appAgrLineSignList)) {
                $appAgrLineList["signKind"][] = $appAgrLineSignList[$temp[0]]["signKind"];
                $appAgrLineList["signYn"][] = $appAgrLineSignList[$temp[0]]["signYn"];
            }
            else {
                $appAgrLineList["signKind"][] = "0";
                $appAgrLineList["signYn"][] = "0";
            }
        }

        $params = array();
        $SQL  = "[dbo].[PEA2_AppAgr_CLine_Edit] ";
        //@nGrpID			AS INT
        $params[] = $grpId;
        //, @nCOID			AS INT
        $params[] = $_SESSION["user"]["company_id"];
        //, @nDeptID			AS INT
        $params[] = $_SESSION["user"]["team_id"];
        //, @nUserID			AS INT
        $params[] = $user->uno;
        //, @nDocID			AS INT
        $params[] = $docId;
        //, @sAppAgrKinds		AS NVARCHAR(4000)/*결재합의 종류 1:결재,2:합의*/
        $params[] = implode(",", $appAgrLineList["kind"]);
        //, @sAppAgrLevels	AS NVARCHAR(4000)/*결재합의라인 결재합의순서(병렬도 있기때문에 파라미터로 받는다)*/
        $params[] = implode(",", $appAgrLineList["level"]);
        //, @sAppAgrUserIDs	AS NVARCHAR(4000)/*결재합의라인 사용자 아이디*/
        $params[] = implode(",", $appAgrLineList["userId"]);
        //, @sAppAgrDeptIDs	AS NVARCHAR(4000)/*결재합의라인 부서 아이디*/
        $params[] = implode(",", $appAgrLineList["deptId"]);
        //, @sAppAgrCOIDs		AS NVARCHAR(4000)/*결재합의라인 사용자 회사 아이디*/
        $params[] = implode(",", $appAgrLineList["coId"]);
        //, @sAppAgrGradeNMs	AS NVARCHAR(4000)/*결재합의라인 사용자 직급*/
        $params[] = implode(",", $appAgrLineList["gradeNm"]);
        //, @sAppAgrDutyNMs	AS NVARCHAR(4000)/*결재합의라인 사용자 직책*/
        $params[] = implode(",", $appAgrLineList["dutyNm"]);
        //, @sAppAgrSignKind	AS NVARCHAR(4000)/*결재합의라인 싸인구분*/
        $params[] = implode(",", $appAgrLineList["signKind"]);
        //, @sAppAgrSignYN	AS NVARCHAR(4000)/*결재합의라인 싸인여부*/
        $params[] = implode(",", $appAgrLineList["signYn"]);
        //, @sAppAgrPre2s	AS NVARCHAR(4000) = '0' /*결재합의라인 전결권한*/
        $params[] = "0";
        //, @nEBMove			AS INT = 0   OUTPUT  -- 문서이관 종결시 처리일 경우만 1 반환한다.
        $params[] = 0;
        //, @nRtnStat			AS INT = 0	 OUTPUT  -- 문서 상태 (종결인지 아닌지) (1 : 종결)
        $params[] = 0;
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        $userDB->next_record();
        $row = $userDB->Record;
        if ($row["return_value"] > 0) {

            //문서이관 종결시 처리
            if (1 == $row["eb_move"] && 1 == $row["rtn_stat"]) {
                //이관문서번호 옵션
                $params = array();
                $SQL  = "SELECT option_id, option_set_code, option_value ";
                $SQL .= "FROM FCMT_GetModuleOpton(?) ";
                $SQL .= "WHERE option_id = 11 ";
                $params[] = $_SESSION["user"]["company_id"];
                $userDB->query($SQL, $params);
                while($userDB->next_record()) {
                    $row = $userDB->Record;

                    $ebOptionDocCd = $row["option_value"];
                }

                $ebCdBase = "";
                //1:이관문서함기준 재생성
                if (in_array($ebOptionDocCd, array("1", "2"))) {
                    //$ebCdBase = "";
                }

                $params = array();
                $SQL  = "[dbo].[PEA2_APPDOC_CMove_TOEB] ";
                //@nGrpID			AS INT
                $params[] = $grpId;
                //, @nDocID			AS INT
                $params[] = $docId;
                //, @nAppUserID		AS INT
                $params[] = $user->uno;
                //, @nMenuID			AS INT = 0	/*0이면 자동분류, 0이 아니면 해당하는 메뉴로 무조건 이동*/
                $params[] = $ebMenuId;
                //, @sEBCDBase		AS NVARCHAR(255)
                $params[] = $ebCdBase;
                //, @sDBCDBaseOption	AS NCHAR(1)	/* 1:이관문서함기준 재생성 3:이관문서번호 품의번호로 대체 4:품의번호 리셋 , 5:이관문서번호 품의번호로 대체(반려이력포함)  */
                $params[] = $ebOptionDocCd;
                //, @nCOID			AS INT = 0
                $params[] = 0;
                for($i = 0; $i < count($params); $i++) {
                    if ($i > 0) {
                        $SQL .= ", ";
                    }
                    $SQL .= "?";
                }
                $userDB->query($SQL, $params);
            }
        }
        else {
            $proceed = false;
            $msg = "결재를 저장하는 중 에러가 발생하였습니다.";
        }
    }

    if ($proceed && "all" == $targetAppline) {
        $params = array();
        $SQL  = "[dbo].[PEA2_SYNC_PDA_C] ?, ?, ?, ? ";
        $params[] = $grpId;
        $params[] = $user->uno;
        $params[] = $docId;
        $params[] = "E";
        $userDB->query($SQL, $params);
    }

    if ($proceed && in_array($targetAppline, array("all", "recipient"))) {
        $recipientList = getUserInfoIdListStr($recipientIds);
        if (count($recipientList) > 0) {
            $params = array();
            $SQL  = "[dbo].[PEA2_RECEIP_EDIT] ";
            //@nGrpID			AS INT
            $params[] = $grpId;
            //, @nCOID			AS INT
            $params[] = $_SESSION["user"]["company_id"];
            //, @nUserID			AS INT
            $params[] = $user->uno;
            //, @nDocID			AS INT
            $params[] = $docId;
            //, @sReceipKinds		AS NVARCHAR(4000)
            $params[] = implode(",", $recipientList[0]);
            //, @sReceipCOIDs		AS NVARCHAR(4000)
            $params[] = implode(",", $recipientList[1]);
            //, @sReceipUserIDs	AS NVARCHAR(4000)
            $params[] = implode(",", $recipientList[2]);
            //, @sReceipDeptIDs	AS NVARCHAR(4000) = ''
            $params[] =  implode(",", $recipientList[3]);
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            $row = $userDB->Record;
            if ($row["return_value"] < 0) {
                $proceed = false;
                $msg = "수신참조를 저장하는 중 에러가 발생하였습니다.";
            }
        }
    }

    if ($proceed && in_array($targetAppline, array("all", "operator"))) {
        $operatorList = getUserInfoIdListStr($operatorIds);
        if (count($operatorList) > 0) {
            $params = array();
            $SQL  = "[dbo].[PEA2_OPER_EDIT] ";
            //@nGrpID			AS INT
            $params[] = $grpId;
            //, @nCOID			AS INT
            $params[] = $_SESSION["user"]["company_id"];
            //, @nUserID			AS INT
            $params[] = $user->uno;
            //, @nDocID			AS INT
            $params[] = $docId;
            //, @sReceipKinds		AS NVARCHAR(4000)
            $params[] = implode(",", $operatorList[0]);
            //, @sReceipCOIDs		AS NVARCHAR(4000)
            $params[] = implode(",", $operatorList[1]);
            //, @sReceipUserIDs	AS NVARCHAR(4000)
            $params[] = implode(",", $operatorList[2]);
            //, @sReceipDeptIDs	AS NVARCHAR(4000) = ''
            $params[] = implode(",", $operatorList[3]);
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            if ($row["return_value"] < 0) {
                $proceed = false;
                $msg = "시행자를 저장하는 중 에러가 발생하였습니다.";
            }
        }
    }

    $result = array(
        "proceed" => $proceed,
        "msg" => $msg
    );

    echo json_encode($result);
}
else if ("EDIT_APP_LINE" == $mode) {
    //결재합의라인
    $appAgrLine = $_POST["appAgrLine"];
    //수신참조
    $recipientIds = $_POST["recipientIds"];
    //시행자
    $operatorIds = $_POST["operatorIds"];

    //사원 목록
    $userList = array();
    $params = array();
    $SQL  = "[dbo].[PSM_USERDEPT_DeptWithSub_H] ?, ?, ?, ? ";
    $params[] = $grpId;
    $params[] = $_SESSION["user"]["company_id"];
    $params[] = $_SESSION["user"]["team_id"];
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $id1 = $row["user_id"] . "_" . $row["co_id"];
        if (!empty($row["dept_id"])) {
            $id1 .= "_" . $row["dept_id"];
        }

        $id2 = $row["user_id"] . "|" . $row["co_id"];
        if (!empty($row["dept_id"])) {
            $id2 .= "|" . $row["dept_id"];
        }

        $dept_nm2 = $row["dept_nm2"];
        
        if(isset($members) && is_array($members) && isset($members[$user->uno]) && is_array($members[$user->uno]) && $members[$user->uno] && isset($depts) && is_array($depts))
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }

        $userList[] = array(
            "userId" => $id1,
            "userId2" => $id2,
            "deptNm" => $dept_nm2,
            "userNm" => $row["user_nm"],
            "gradeDuty" => $row["grade_nm"] . "(" . $row["duty_nm"] . ")",
            //{0}|{1}|{2}|{3}|{4}|{5}|{6}|{7}|{8}|{9}|{10}|{11} dataItem["co_id"], dataItem["co_nm"], dataItem["dept_id"], dataItem["dept_nm"], dataItem["grade"], dataItem["grade_nm"], dataItem["duty"], dataItem["duty_nm"], dataItem["user_id"], dataItem["user_nm"], dataItem["grade_order"], dataItem["duty_order"]
            "userVal" => $row["user_id"] . "|" . $row["co_id"] . "|" . $row["dept_id"] . "|" . $row["user_nm"] . "|" . $row["grade_nm"] . "|" . $row["duty_nm"],
            "userDisp" => $row["user_nm"] . "|" . $row["dept_nm"] . "|" . $row["co_nm"]
        );
    }

    //결재합의라인
    if (count($appAgrLine) > 0) {
        $appAgrLineList = array();
        $appAgrInfoList = array();
        foreach($appAgrLine as $line) {
            $temp = explode("|", $line);
            $key = $temp[count($temp) - 1];
            //APP_LEVEL
            $appAgrLineList[$key] = $line;
            $appAgrInfoList[$temp[0] . "|" . $temp[1] . "|" . $temp[2]] = array(
                "line" => substr($line, 0, strrpos($line,"|")),
                "kind" => $temp[count($temp) - 2]
            );
        }
        ksort($appAgrLineList);
        $tempList = getUserInfoIdList($appAgrLineList);
        $appAgrList = array();
        if (count($tempList) > 0) {
            $params = array();
            $SQL  = "[dbo].[PSM_USERDEPT_SelectedSolve] ";
            //@nGrpID	AS INT
            $params[] = $grpId;
            //, @sKinds	AS NVARCHAR(4000)
            $params[] = implode(",", $tempList[0]);
            //, @sCOIDs	AS NVARCHAR(4000)
            $params[] = implode(",", $tempList[1]);
            //, @sUserIDs	AS NVARCHAR(4000)
            $params[] = implode(",", $tempList[2]);
            //, @sDeptIDs	AS NVARCHAR(4000) = ''
            $params[] = implode(",", $tempList[3]);
            //, @sModule  AS NVARCHAR(20) = ''
            $params[] = "EA";
            for($i = 0; $i < count($params); $i++) {
                if ($i > 0) {
                    $SQL .= ", ";
                }
                $SQL .= "?";
            }
            $userDB->query($SQL, $params);
            while($userDB->next_record()) {
                $row = $userDB->Record;
                
                $info = $appAgrInfoList[$row["receipopervalue2"]];
                $appKindLabel = "";
                if (in_array($info["kind"], array(1, 5))) {
                    $appKindLabel = "[결재] ";
                }
                else if (in_array($info["kind"], array(2, 6))) {
                    $appKindLabel = "[합의] ";
                }

                $appAgrList[] = array(
                    "userId" => $row["receipopervalue2"],
                    "userNmDisp" => $appKindLabel . $row["receipoperdisp"],
                    "userVal" => $info["line"],
                    "appKind" => $info["kind"]
                );
            }
        }
    }

    //수신참조 명단
    $tempList = getUserInfoIdListStr($recipientIds);

    $recipientList = array();
    if (count($tempList) > 0) {
        $params = array();
        $SQL  = "[dbo].[PSM_USERDEPT_SelectedSolve] ";
        //@nGrpID	AS INT
        $params[] = $grpId;
        //, @sKinds	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[0]);
        //, @sCOIDs	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[1]);
        //, @sUserIDs	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[2]);
        //, @sDeptIDs	AS NVARCHAR(4000) = ''
        $params[] = implode(",", $tempList[3]);
        //, @sModule  AS NVARCHAR(20) = ''
        $params[] = "EA";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        while($userDB->next_record()) {
            $row = $userDB->Record;

            $userId = "";
            if ("U" == $row["kind"]) {
                $userId = $row["receipopervalue2"];
            }
            else if ("D" == $row["kind"]) {
                $userId = "D" . $row["receipopervalue"];
            }
            $recipientList[] = array(
                "userId" => $userId,
                "userNmDisp" => $row["receipoperdisp"],
                "userNm" => $row["user_nm"]
            );
        }
    }

    //시행자 명단
    $tempList = getUserInfoIdListStr($operatorIds);
    $operatorList = array();
    if (count($tempList) > 0) {
        $params = array();
        $SQL  = "[dbo].[PSM_USERDEPT_SelectedSolve] ";
        //@nGrpID	AS INT
        $params[] = $grpId;
        //, @sKinds	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[0]);
        //, @sCOIDs	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[1]);
        //, @sUserIDs	AS NVARCHAR(4000)
        $params[] = implode(",", $tempList[2]);
        //, @sDeptIDs	AS NVARCHAR(4000) = ''
        $params[] = implode(",", $tempList[3]);
        //, @sModule  AS NVARCHAR(20) = ''
        $params[] = "EA";
        for($i = 0; $i < count($params); $i++) {
            if ($i > 0) {
                $SQL .= ", ";
            }
            $SQL .= "?";
        }
        $userDB->query($SQL, $params);
        while($userDB->next_record()) {
            $row = $userDB->Record;

            $operatorList[] = array(
                "userId" => $row["receipopervalue2"],
                "userNmDisp" => $row["receipoperdisp"],
                "userNm" => $row["user_nm"]
            );
        }
    }

    $result = array(
        "appAgrInfoList" => $appAgrInfoList,
        "userList" => $userList,
        "appAgrList" => $appAgrList,
        "recipientList" => $recipientList,
        "operatorList" => $operatorList,
        "pjtMemberList" => getSearchPjtMember()
    );

    echo json_encode($result);
}
//결재라인 명 선택
else if ("LIST_APP_LINE" == $mode) {
    $appLineId = $_POST["ddlAppLine"];

//     [dbo].[PEA2_APPLINE_DetailByID]
    //결재합의라인
    $appAgrList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_APPLINE_DetailByID_H_APP] ?, ?, ?, ? ";
    //@nGrpID	AS INT
    $params[] = $grpId;
    //, @nCoID		AS INT = 0
    $params[] = $_SESSION["user"]["company_id"];
    //, @nLineID 	AS INT
    $params[] = $appLineId;
    //, @nLangKind AS NVARCHAR(20) = 'KR'
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $id = $row["user_id"] . "|" . $row["co_id"];
        if (!empty($row["dept_id"])) {
            $id .= "|" . $row["dept_id"];
        }
        $nm = $row["user_nm"] . "|" . $row["dept_nm"] . "|" . $row["co_nm"];

        $appKindLabel = "";
        if (in_array($row["app_kind"], array(1, 5))) {
            $appKindLabel = "[결재] ";
        }
        else if (in_array($row["app_kind"], array(2, 6))) {
            $appKindLabel = "[합의] ";
        }

        $appAgrList[] = array(
            "userId" => $id,
            "userNmDisp" => $appKindLabel . $nm,
            "userVal" => $row["user_id"] . "|" . $row["co_id"] . "|" . $row["dept_id"] . "|" . $row["user_nm"] . "|" . $row["grade_nm"] . "|" . $row["duty_nm"],
            "appKind" => $row["app_kind"]
        );
    }

    //수신참조 명단
    $recipientList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_APPLINE_DetailByID_H_RECEIP] ?, ?, ?, ? ";
    //@nGrpID	AS INT
    $params[] = $grpId;
    //, @nCoID		AS INT = 0
    $params[] = $_SESSION["user"]["company_id"];
    //, @nLineID 	AS INT
    $params[] = $appLineId;
    //, @nLangKind AS NVARCHAR(20) = 'KR'
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $userId = "";
        //직원일 경우
        if ("U" == $row["co_dept_user_kind"]) {
            $userId = $row["receipopervalue2"];
        }
        //부서일 경우
        else if ("D" == $row["co_dept_user_kind"]) {
            $userId = "D" . $row["receipopervalue"];
        }
        $recipientList[] = array(
            "userId" => $userId,
            "userNmDisp" => $row["receipoperdisp"],
            "userNm" => $row["user_nm"]
        );
    }

    //시행자 명단
    $operatorList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_APPLINE_DetailByID_H_OPER] ?, ?, ?, ? ";
    //@nGrpID	AS INT
    $params[] = $grpId;
    //, @nCoID		AS INT = 0
    $params[] = $_SESSION["user"]["company_id"];
    //, @nLineID 	AS INT
    $params[] = $appLineId;
    //, @nLangKind AS NVARCHAR(20) = 'KR'
    $params[] = "KR";
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $operatorList[] = array(
            "userId" => $row["receipopervalue"],
            "userNmDisp" => $row["receipoperdisp"],
            "userNm" => $row["user_nm"]
        );
    }

    $result = array(
        "appAgrList" => $appAgrList,
        "recipientList" => $recipientList,
        "operatorList" => $operatorList
    );

    echo json_encode($result);
}
//사원 검색
else if ("APP_LINE_SEARCH_USER" == $mode) {
    $alSearchUser = $_POST["alSearchUser"];

    //사원 목록
    $userList = array();
    $params = array();
    //검색어가 없을 경우
    if (empty($alSearchUser)) {
        //로그인 유저 소속 부서 사원
        $SQL  = "[dbo].[PSM_USERDEPT_DeptWithSub_H] ";
        $params[] = $grpId;
        $params[] = $_SESSION["user"]["company_id"];
        $params[] = $_SESSION["user"]["team_id"];
        $params[] = "KR";
    }
    else {
        $SQL  = "[dbo].[PSM_USERDEPT_SEARCHWithSub_H] ";
        $params[] = $grpId;
        $params[] = $_SESSION["user"]["company_id"];
        $params[] = $alSearchUser;
        $params[] = "KR";
        //0 단위회사, 1 그룹-회사
        $params[] = "0";
    }
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $id1 = $row["user_id"] . "_" . $row["co_id"];
        if (!empty($row["dept_id"])) {
            $id1 .= "_" . $row["dept_id"];
        }

        $id2 = $row["user_id"] . "|" . $row["co_id"];
        if (!empty($row["dept_id"])) {
            $id2 .= "|" . $row["dept_id"];
        }

        $dept_nm2 = $row["dept_nm2"];
        
        if(isset($members) && is_array($members) && isset($members[$user->uno]) && is_array($members[$user->uno]) && $members[$user->uno] && isset($depts) && is_array($depts))
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }

        $userList[] = array(
            "userId" => $id1,
            "userId2" => $id2,
            "deptNm" => $dept_nm2,
            "userNm" => $row["user_nm"],
            "gradeDuty" => $row["grade_nm"] . "(" . $row["duty_nm"] . ")",
            "userVal" => $row["user_id"] . "|" . $row["co_id"] . "|" . $row["dept_id"] . "|" . $row["user_nm"] . "|" . $row["grade_nm"] . "|" . $row["duty_nm"],
            "userDisp" => $row["user_nm"] . "|" . $row["dept_nm"] . "|" . $row["co_nm"]
        );
    }

    $result = array(
        "userList" => $userList
    );

    echo json_encode($result);
}
// 프로젝트 멤버 검색
else if ("APP_LINE_PJT_MEMBER" == $mode) {
    $result = array(
        "pjtMemberList" => getSearchPjtMember()
    );

    echo json_encode($result);
}
//수신참조리스트
else if ("LIST_RECIPIENT" == $mode) {
//     $docId = $_POST["docId"];
    $docId = $_POST["roDocId"];

    $ROList = array();
    $cntReadYn = array(
        "Y" => 0,
        "N" => 0
    );
    $params = array();
    $SQL  = "[dbo].[PEA2_RECEIPOPER_LIST_0300100_S_H_RECEIP_LIST] ?, ? ";
    $params[] = $grpId;
    $params[] = $docId;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $readYn = "";
        if ("0" == $row["cd_id"]) {
            $cntReadYn["N"] = $cntReadYn["N"] + 1;
            $readYn = "미열람";
        }
        else {
            $cntReadYn["Y"] = $cntReadYn["Y"] + 1;
            $readYn = "열람";
        }
        $firstReadDate = "";
        if (!empty($row["first_read_dt"])) {
            $firstReadDate = $row["first_read_dt"];
        }
        $editedDate = "";
        if (!empty($row["edited_dt"])) {
            $editedDate = $row["edited_dt"];
        }

        $dept_nm2 = $row["dept_nm"];
        if(isset($members) && is_array($members) && isset($members[$user->uno]) && is_array($members[$user->uno]) && $members[$user->uno] && isset($depts) && is_array($depts))
        {
            $member = $members[$user->uno];
            $dept_id = $row["dept_id"];
            $dept_nm2 = $depts[$dept_id]["display_name"];
            //echo $dept_nm2;
        }
        $ROList[] = array(
            "deptName" => $dept_nm2,
            "userName" => $row["work_nm"],
            "readYn" => $readYn,
            "firstReadDate" => $firstReadDate,
            "editedDate" => $editedDate
        );
    }
    $txtReadYn = "열람 : " . $cntReadYn["Y"] . " 명 미열람 : " . $cntReadYn["N"] . " 명";

    $result = array(
        "txtReadYn" => $txtReadYn,
        "ROList" => $ROList
    );

    echo json_encode($result);
}
//시행리스트
else if ("LIST_OPERATOR" == $mode) {
//     $docId = $_POST["docId"];
    $docId = $_POST["roDocId"];

    $ROList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_RECEIPOPER_LIST_0300100_S_H_OPER] ?, ? ";
    $params[] = $grpId;
    $params[] = $docId;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $ROList[] = array(
            "deptName" => $row["dept_nm"],
            "userName" => $row["work_nm"],
            "recChkName" => $row["rec_chk_nm"],
            "note" => $row["note"]
        );
    }

    $result = array(
        "ROList" => $ROList
    );

    echo json_encode($result);
}
//수정 내역 목록
else if ("LIST_RO_HIS" == $mode) {
    $docId = $_POST["docId"];
    $target = $_POST["targetAppline"];
    $roKind = 1;
    if ("operator" == $target) {
        $roKind = 0;
    }
    else if ("recipient" == $target) {
        $roKind = 1;
    }

    $hisList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_RECEIP_SHIS] ?, ?, ?, ? ";
    //@nGrpID		AS INT
    $params[] = $grpId;
    //, @nDocID		AS INT
    $params[] = $docId;
    //, @nStep		AS INT
    $params[] = 1;
    //, @nReceipOperKind	AS int	= 1
    $params[] = $roKind;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $hisList[] = array(
            "step" => $row["step"],
            "createdDate" => $row["created_dt"],
            "userName" => $row["user_nm"]
        );
    }

    $result = array(
        "hisList" => $hisList
    );

    echo json_encode($result);
}
//수정 내역 상세
else if ("DETAIL_RO_HIS" == $mode) {
    $docId = $_POST["docId"];
    $target = $_POST["targetAppline"];
    $step = $_POST["stepHistory"];
    $roKind = 1;
    if ("operator" == $target) {
        $roKind = 0;
    }
    else if ("recipient" == $target) {
        $roKind = 1;
    }

    $ROList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_RECEIP_SHIS_H_INFO] ?, ?, ?, ? ";
    //@nGrpID		AS INT
    $params[] = $grpId;
    //, @nDocID		AS INT
    $params[] = $docId;
    //, @nStep		AS INT
    $params[] = $step;
    //, @nReceipOperKind	AS int	= 1
    $params[] = $roKind;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $ROList[] = array(
            "deptName" => $row["dept_nm"],
            "workName" => $row["work_nm"]
        );
    }

    $result = array(
        "ROList" => $ROList
    );

    echo json_encode($result);
    
}
//초기 화면
else if ("INIT" == $mode) {
    //사용자지정 결재라인
    $appLineList = array();
    $params = array();
    $SQL  = "[dbo].[PEA2_APPLINE_S] ";
    //@nGrpID 	AS INT
    $params[] = $grpId;
    //, @nCOID	AS INT
    $params[] = $_SESSION["user"]["company_id"];
    //, @nDeptID	AS INT
    $params[] = $_SESSION["user"]["team_id"];
    //, @nUserID	AS INT
    $params[] = $user->uno;
    //, @nLangKind AS NVARCHAR(20) = 'KR'
    $params[] = "KR";
    //, @sAppLineKind AS NVARCHAR(1) = ''
    $params[] = "";
    //, @nTargetDeptID AS INT = 0
    $params[] = 0;
    for($i = 0; $i < count($params); $i++) {
        if ($i > 0) {
            $SQL .= ", ";
        }
        $SQL .= "?";
    }
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $appLineList[] = array(
            "key" => $row["line_id"],
            "val" => $row["applinekind_nm"]
        );
    }

    //부서 목록
    $userDeptData = array();
    $params = array();
    $SQL  = "[dbo].[PSM_DEPT_0100200_SCO] ?, ? ";
    $params[] = $grpId;
    $params[] = 1;
    $userDB->query($SQL, $params);
    while($userDB->next_record()) {
        $row = $userDB->Record;

        $userDeptData["D" . $row["dept_id"]] = array(
            "deptId" => "D" . $row["dept_id"],
            "parDeptId" => "D" . $row["par_dept_id"],
            "deptCd" => $row["dept_cd"],
            "deptNm" => $row["dept_nm"],
            "useYn" => $row["use_yn"],
            "kind" => $row["co_work_dept_kind"]
        );
    }

    $deptList = getTreeDeptList($userDeptData, "D0", "AL", true, false);

    $result = array(
        "appLineList" => $appLineList,
        "deptList" => $deptList
    );

    echo json_encode($result);
}

// 프로젝트 멤버 검색
function getSearchPjtMember() {
    global $db;

    $pjtNm = $_POST["pjtNm"];
    $alSearchPjtMember = $_POST["alSearchPjtMember"];

    $pjtMemberList = array();
    if($pjtNm) {
        $SQL = "SELECT DISTINCT M.UNO, M.MEMBER_NAME, M.FUNC_TITLE, M.GRADE_NAME, L.CO_ID, U.TEAM_ID AS DEPT_ID, U.DUTY_NAME, D.DEPT_NAME, U.COMPANY_NAME AS CO_NM,
                                L.FUNC_CODE, L.AREA, U.DEPT_CD, U.DUTY_VIEW_ORDER, U.USER_NAME, M.SORT_NO
                FROM JOB_INFO J
                RIGHT OUTER JOIN V_JOB_MEMBER_ALL_LIST M ON J.JNO = M.JNO
                INNER JOIN JOB_MEMBER_LIST L ON M.MNO = L.MNO
                INNER JOIN SYS_USER_SET U ON M.UNO = U.UNO
                INNER JOIN V_SYS_DEPT_SET D ON U.TEAM_ID = D.DEPT_NO
                WHERE J.JOB_NAME LIKE '%{$pjtNm}%'
                AND M.UNO IS NOT NULL
                AND U.IS_USE = 'Y'
                AND (M.MEMBER_NAME LIKE '%{$alSearchPjtMember}%' OR UPPER(M.FUNC_TITLE) LIKE UPPER('%{$alSearchPjtMember}%'))
                ORDER BY DECODE(U.TEAM_ID, 48, 1), L.FUNC_CODE, L.AREA, U.DEPT_CD, U.DUTY_VIEW_ORDER ASC, U.USER_NAME ASC, M.SORT_NO";
        $db->query($SQL);

        while($db->next_record()) {
            $row = $db->Record;
            
            $id1 = $row["uno"] . "_" . $row["co_id"];
            if (!empty($row["dept_id"])) {
                $id1 .= "_" . $row["dept_id"];
            }
    
            $id2 = $row["uno"] . "|" . $row["co_id"];
            if (!empty($row["dept_id"])) {
                $id2 .= "|" . $row["dept_id"];
            }

            $pjtMemberList[] = array(
                "uno" => $row["uno"],
                "memberNm" => $row["member_name"],
                "funcTitle" => $row["func_title"],
                "gradeNm" => $row["grade_name"],
                "userId" => $id1,
                "userId2" => $id2,
                "userVal" => $row["uno"] . "|" . $row["co_id"] . "|" . $row["dept_id"] . "|" . $row["member_name"] . "|" . $row["grade_name"] . "|" . $row["duty_name"],
                "userDisp" => $row["member_name"] . "|" . $row["dept_name"] . "|" . $row["co_nm"]
            );
        }

        return $pjtMemberList;
    }
}
