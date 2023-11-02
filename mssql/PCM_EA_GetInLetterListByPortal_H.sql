USE [Neo_BizBox_20210107]
GO
/****** Object:  StoredProcedure [dbo].[PCM_EA_GetInLetterListByPortal_H]    Script Date: 2023-11-02 오후 3:14:34 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/******************************************************************************
 작 성 자 : 
 작 성 일 : 2022-05-20
 설    명 : 포탈메인 - 전자결재 리스트 조회         
 수 정 일 : 
 수 정 자 : 
 수정설명 : @sDIV 값 6일 경우 추가( 참조 분리 작업 후 포털에 내가참조된 문서 리스트 보여주기 위한 부분 추가)
 수 정 자 : 
 설    명 : 계열사 지원 여부 옵션 적용
 실    행 : 
 실행 Com : 
*******************************************************************************/

ALTER Procedure [dbo].[PCM_EA_GetInLetterListByPortal_H]
  @nGRP_ID	INT
, @nCO_ID	INT
, @nUSER_ID	INT
, @sDIV     NVARCHAR(10)
, @nPageSize	AS INT
AS
SET NOCOUNT ON

DECLARE @sSQL_TEXT	NVARCHAR(3000)
DECLARE @sSQL_PARA	NVARCHAR(500)
DECLARE @nCoOrTotal INT 
DECLARE @nReceipOption	AS NVARCHAR(1)
DECLARE @sNowDate	NVARCHAR(8)

declare @sTotalOrCo			as nvarchar(1) -- 계열사 문서 열람 여부 
		
/* 계열사 문서 열람 여부 */
SELECT @sTotalOrCo = option_value FROM dbo.FCMT_GetModuleOpton(@nCO_ID) WHERE option_id = 53

/* 계열사 지원여부에따라 카운트를 달리 한다. 0 지원 / 1 미지원 */
SELECT @nCoOrTotal = val 
  FROM TCMG_ENVBYCO
 WHERE kind = 'CM_COTotal' AND co_id=@nCO_ID
 
/* 진행중인 수신참조 목록에대한 옵션값 : 0 완료문서만 / 1 진행중인 문서 포함 */
SELECT @nReceipOption = option_value FROM tcmg_option WHERE option_set_code = 'eOption_EA_Doing_Receip'

SET @sNowDate	= dbo.FSMV_DATEToC8(getdate())

--2	EMT_EA	모든결재
--3	EMT_EA	미결
--4	EMT_EA	수신참조
--5	EMT_EA	시행

IF(@sDIV = '3')
BEGIN
	SET @sSQL_TEXT = '
/* SELECT TOP 8
		''미결'' DIV
		, DOC_ID
		, SUBJECT
		, PRO_KIND
		, edited_dt
FROM dbo.FEAT_APPDOC_SIntray(@nGRP_ID, @nCO_ID, @nUSER_ID)
ORDER BY DOC_ID DESC */

/*		SELECT TOP 8
			  ''미결'' DIV
			 , ad.DOC_ID
			 , ad.SUBJECT
			 , ad.PRO_KIND
			 , ad.edited_dt
			 , ad.form_nm
	  	  FROM TEAG_APPDOC AS ad
	INNER JOIN TEAG_APP AS b ON ad.doc_id = b.doc_id AND b.sign_yn=0 AND b.sign_kind in (0,10) 
	INNER JOIN TCMG_CO AS c ON ad.co_id=c.CO_ID
		 WHERE b.doc_user_id in (select user_id from dbo.FEAT_ALTERWithMe(@nGRP_ID, @nCO_ID,@nUSER_ID, @sNowDate, ''1'')) 
	  AND (ad.Use_YN is null or ad.Use_YN = 1)
	  AND ad.doc_stat NOT IN (0,4)  AND ad.agr_stat <> 4
	  AND (ad.now_app = b.app_level OR (ad.sign_option = 4 and ((app_kind in (2, 6)) and sign_yn = 0 and sign_kind = 0)))
	  AND ad.doc_stat <> 4
      ORDER BY ad.DOC_ID DESC */

/* 2013-07-10 연구원 신민호 (계열사 문서 열람 옵션 적용 추가) */
      
		SELECT TOP 8
			  ''미결'' DIV
			 , ad.DOC_ID
			 , ad.SUBJECT
			 , ad.PRO_KIND
			 , ad.edited_dt
			 , ad.form_id
			 , ad.form_nm
	  	  FROM TEAG_APPDOC AS ad
	INNER JOIN TEAG_APP AS b ON ad.doc_id = b.doc_id AND b.sign_yn=0 AND b.sign_kind in (0,10) 
	INNER JOIN TCMG_CO AS c ON ad.co_id=c.CO_ID
		 WHERE b.doc_user_id in (select user_id from dbo.FEAT_ALTERWithMe(@nGRP_ID, @nCO_ID,@nUSER_ID, @sNowDate, ''1'')) 
	  AND (ad.Use_YN is null or ad.Use_YN = 1)
	  AND ad.doc_stat NOT IN (0,4)  AND ad.agr_stat <> 4
	  AND (ad.now_app = b.app_level OR (ad.sign_option = 4 and ((app_kind in (2, 6)) and sign_yn = 0 and sign_kind = 0)))
	  AND ad.doc_stat <> 4
	  AND ad.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ad.co_id END)
	  AND ad.form_id = ''10040''
      ORDER BY ad.DOC_ID DESC
      
'
END
ELSE IF(@sDIV = '4')
BEGIN

	IF @nReceipOption = 0
	BEGIN
		SET @sSQL_TEXT = ' SELECT TOP 8
				''수신참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
         FROM TEAG_APPDOC AS ad
      INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''RECEIP''
        WHERE ro.user_id =@nUSER_ID
		AND ad.doc_stat NOT IN (0,4,6) AND ad.agr_stat NOT IN (4,6)
          AND ((ad.AppLineType <> 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3) AND ad.agr_stat IN (0,2,3))))
          OR (ad.AppLineType = 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3,13))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3,13) AND ad.agr_stat IN (0,2,3,12)))))
            and ro.cnt = 0
            and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END)
            AND (ad.Use_YN is null or ad.Use_YN = 1)
			AND ad.form_id = ''10040''
             ORDER BY ad.DOC_ID DESC '
	END
	ELSE
	BEGIN
		SET @sSQL_TEXT = ' SELECT TOP 8
				''수신참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
        FROM TEAG_APPDOC ad
		INNER JOIN TCMG_CO c ON ad.co_id = c.CO_ID
        INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''RECEIP''
          WHERE ro.user_id = @nUser_ID
         AND ad.doc_stat NOT IN (0, 4, 6)
         and ro.cnt = 0
        and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END)
        AND (ad.Use_YN is null or ad.Use_YN = 1)
		AND ad.form_id = ''10040''
            ORDER BY ad.DOC_ID DESC '
	END
END
ELSE IF(@sDIV = '5')
BEGIN
	SET @sSQL_TEXT = '
/*
SELECT TOP 8
		''시행'' DIV
		, adEnd.DOC_ID
		, adEnd.SUBJECT
		, adEnd.PRO_KIND
		, adEnd.edited_dt
FROM TEAG_RECEIPOPER ro
INNER JOIN (
			SELECT @nUSER_ID work_id
				 , ''U'' work_kind
			UNION
			SELECT dept_id
				 , co_work_dept_kind
			FROM dbo.FCMT_UserDept(@nUSER_ID) 
			WHERE co_id = @nCO_ID 
			) ud ON (ud.work_id = ro.co_dept_user_id AND ud.work_kind=ro.co_dept_user_kind)
INNER JOIN dbo.FEAT_APPDOC_SCOTotal(@nGRP_ID) adEnd on ro.doc_id = adEnd.doc_id
WHERE ro.receipt_oper_kind = 0
AND ro.rec_chk = 0 */

		  SELECT TOP 8
			   ''시행'' DIV
			   , ad.DOC_ID
			   , ad.SUBJECT
			   , ad.PRO_KIND
			   , ad.edited_dt
			   , ad.form_id
		   FROM TEAG_APPDOC ad
	 INNER JOIN TEAG_RECEIPOPER ro ON ad.doc_id = ro.doc_id 
		  WHERE ro.co_dept_user_id = @nUSER_ID
			AND	ro.receipt_oper_kind	=	0
			AND	ro.co_dept_user_kind	=	''U''
			AND ro.rec_chk = 0
			AND	(
				 ( sign_option = 0 AND doc_stat in (2,3) )
			  OR ( sign_option = 2 AND doc_stat in (2,3) AND agr_stat in (0,2,3) )
			    ) 
			AND doc_stat NOT IN (4, 6) AND agr_stat NOT IN (4, 6)
			AND (ad.Use_YN is null or ad.Use_YN = 1)
			AND ad.co_id = (CASE WHEN @nCoOrTotal = 1 THEN @nCO_ID ELSE ad.co_id END)
			AND ad.form_id = ''10040''
			ORDER BY ad.DOC_ID DESC
'
END
ELSE IF(@sDIV = '6')
BEGIN

	IF @nReceipOption = 0
	BEGIN          
		SET @sSQL_TEXT = ' SELECT TOP 8
				''참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
         FROM TEAG_APPDOC AS ad
      INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''CC''
        WHERE ro.user_id =@nUSER_ID
		AND ad.doc_stat NOT IN (0,4,6) AND ad.agr_stat NOT IN (4,6)
          AND ((ad.AppLineType <> 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3) AND ad.agr_stat IN (0,2,3))))
          OR (ad.AppLineType = 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3,13))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3,13) AND ad.agr_stat IN (0,2,3,12)))))
            and ro.cnt = 0
            and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END)
            AND (ad.Use_YN is null or ad.Use_YN = 1)
			AND ad.form_id = ''10040''
             ORDER BY ad.DOC_ID DESC '
	END           
	ELSE          
	BEGIN          
		SET @sSQL_TEXT = ' SELECT TOP 8
				''참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
        FROM TEAG_APPDOC ad
		INNER JOIN TCMG_CO c ON ad.co_id = c.CO_ID
        INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''CC''
          WHERE ro.user_id = @nUser_ID 
         AND ad.doc_stat NOT IN (0, 4, 6)
         and ro.cnt = 0
        and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END)
        AND (ad.Use_YN is null or ad.Use_YN = 1)
		AND ad.form_id = ''10040''
            ORDER BY ad.DOC_ID DESC '
      END

END
ELSE
BEGIN
	SET @sSQL_TEXT = '
	SELECT TOP ' + CAST(@nPageSize AS NVARCHAR(8)) + ' 
		A.DIV, A.DOC_ID, A.SUBJECT, A.PRO_KIND, A.form_id, A.edited_dt
	FROM
	(
		 SELECT TOP 8
			  ''미결'' DIV
			 , ad.DOC_ID
			 , ad.SUBJECT
			 , ad.PRO_KIND
			 , ad.edited_dt
			 , ad.form_id
	  	  FROM TEAG_APPDOC AS ad
	INNER JOIN TEAG_APP AS b ON ad.doc_id = b.doc_id AND b.sign_yn=0 AND b.sign_kind in (0,10) 
	INNER JOIN TCMG_CO AS c ON ad.co_id=c.CO_ID
		 WHERE b.doc_user_id in (select user_id from dbo.FEAT_ALTERWithMe(@nGRP_ID, @nCO_ID,@nUSER_ID, @sNowDate, ''1'')) 
	  AND (ad.Use_YN is null or ad.Use_YN = 1)
	  AND ad.doc_stat NOT IN (0,4)  AND ad.agr_stat <> 4
	  AND (ad.now_app = b.app_level OR (ad.sign_option = 4 and ((app_kind in (2, 6)) and sign_yn = 0 and sign_kind = 0)))
	  AND ad.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ad.co_id END) --계열사 옵션적용
	  AND ad.form_id = ''10040''
      --ORDER BY ad.DOC_ID DESC

	UNION ALL '
			 
	
	IF @nReceipOption = 0
	BEGIN          
		SET @sSQL_TEXT = @sSQL_TEXT +  ' SELECT --TOP 8
				''수신참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
         FROM TEAG_APPDOC AS ad
      INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''RECEIP''
        WHERE ro.user_id =@nUSER_ID
		AND ad.doc_stat NOT IN (0,4,6) AND ad.agr_stat NOT IN (4,6)
          AND ((ad.AppLineType <> 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3) AND ad.agr_stat IN (0,2,3))))
          OR (ad.AppLineType = 6 AND ((ad.sign_option = 0 AND ad.doc_stat IN (2,3,13))
         OR (ad.sign_option IN (2,4) AND ad.doc_stat IN (2,3,13) AND ad.agr_stat IN (0,2,3,12)))))
            and ro.cnt = 0 
            and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END)
            AND (ad.Use_YN is null or ad.Use_YN = 1)
			AND ad.form_id = ''10040''
             --ORDER BY ad.DOC_ID DESC '
	END           
	ELSE          
	BEGIN          
		SET @sSQL_TEXT = @sSQL_TEXT +  ' SELECT --TOP 8
				''수신참조'' DIV
				, ad.DOC_ID
				, ad.SUBJECT
				, ad.PRO_KIND
				, ad.edited_dt
				, ad.form_id
        FROM TEAG_APPDOC ad
		INNER JOIN TCMG_CO c ON ad.co_id = c.CO_ID
        INNER JOIN TEAG_READ ro ON ad.doc_id = ro.doc_id AND ro.kind = ''RECEIP''
          WHERE ro.user_id = @nUser_ID
         AND ad.doc_stat NOT IN (0, 4, 6) 
         and ro.cnt = 0 
        and ro.co_id = (CASE WHEN @sTotalOrCo = ''1'' THEN @nCO_ID ELSE ro.co_id END) 
        AND (ad.Use_YN is null or ad.Use_YN = 1) 
		AND ad.form_id = ''10040''
            --ORDER BY ad.DOC_ID DESC '
	END   

			 
	SET @sSQL_TEXT = @sSQL_TEXT +  '
	
	/*UNION ALL

		  SELECT --TOP 8
			   ''시행'' DIV
			   , ad.DOC_ID
			   , ad.SUBJECT
			   , ad.PRO_KIND
			   , ad.edited_dt
			   , ad.form_id
		   FROM TEAG_APPDOC ad
	 INNER JOIN TEAG_RECEIPOPER ro ON ad.doc_id = ro.doc_id 
		  WHERE ro.co_dept_user_id = @nUSER_ID
			AND	ro.receipt_oper_kind	=	0
			AND	ro.co_dept_user_kind	=	''U''
			AND ro.rec_chk = 0
			AND	(
				 ( sign_option = 0 AND doc_stat in (2,3) )
			  OR ( sign_option = 2 AND doc_stat in (2,3) AND agr_stat in (0,2,3) )
			    ) 
			AND doc_stat NOT IN (4, 6) AND agr_stat NOT IN (4, 6)
			AND (ad.Use_YN is null or ad.Use_YN = 1)
			AND ad.co_id = (CASE WHEN @nCoOrTotal = 1 THEN @nCO_ID ELSE ad.co_id END)
			AND ad.form_id = ''10040''*/
			
) A
ORDER BY A.DOC_ID DESC
'
END



SET @sSQL_PARA = N'
  @nCO_ID	INT
, @nGRP_ID	INT
, @nUSER_ID	INT
, @sDIV     NVARCHAR(10)
, @sNowDate  NVARCHAR(8)
, @nCoOrTotal INT
, @sTotalOrCo nvarchar(1)
'

--PRINT @sSQL_TEXT

EXECUTE SP_EXECUTESQL 
  @sSQL_TEXT
, @sSQL_PARA
, @nGRP_ID  = @nGRP_ID
, @nCO_ID	= @nCO_ID
, @nUSER_ID = @nUSER_ID
, @sDIV		= @sDIV
, @sNowDate = @sNowDate
, @nCoOrTotal = @nCoOrTotal
, @sTotalOrCo = @sTotalOrCo
