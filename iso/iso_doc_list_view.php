<style>
.border-none td {
    border-bottom: 0px solid #00ff0000 !important;
}

.iso-link {
    color: #007bff !important;
}

.tblIsoDetail th {
    font-size :x-large;
}

.font-lager {
    font-size :large;
}

#tblFunction td, #tblFunction th {
    padding: 0.1rem !important;
}

@media (min-width: 2000px) {
    .col-2dot5 {
        -ms-flex: 0 0 20%;
        flex: 0 0 20%;
        max-width: 20%;
    }

    .col-1dot5 {
        -ms-flex: 0 0 12%;
        flex: 0 0 12%;
        max-width: 12%;
    }
}
</style>
<script>
$(document).ready(function() {
    //소메뉴 숨기기
    // $("#divSubMenuContent").hide();
    $("#sidebar").hide();
    $("#divHeader ol").hide();
    $("#divHeader nav").css("margin-left", "-18rem");

    // 시트 가져오기
    getSheet();

    var th = $('#tblIsoList').find('thead th');
    $('#tblIsoList').closest("div.tableFixHead").on('scroll', function() {
        th.css('transform', 'translateY('+ this.scrollTop +'px)');
    });

    // 이미지맵
    var image = $('#isoDocImg');
    // $(window).resize(resizeMap);
    image.on('load', resizeMap);

    // 정렬
    $("#tblIsoList th").on('click', function() {
        var columnId = $(this).attr("id");

        if(columnId) {
            $(".sortIcon").html('');
    
            var queryString = '';
            var sortIcon = $(this).find(".sortIcon");
            if($(sortIcon).hasClass("sort-asc")) {
                $(sortIcon).html('<i class="fa-solid fa-arrow-up-wide-short"></i>');
                $(".sortIcon").removeClass("sort-desc");
                $(".sortIcon").removeClass("sort-asc");
                $(sortIcon).addClass("sort-desc");
                queryString = columnId + ' DESC, ';
            } else if ($(sortIcon).hasClass("sort-desc")) {
                $(sortIcon).html('');
                $(".sortIcon").removeClass("sort-desc");
                $(".sortIcon").removeClass("sort-asc");
            } else {
                $(sortIcon).html('<i class="fa-solid fa-arrow-down-short-wide"></i>');
                $(".sortIcon").removeClass("sort-desc");
                $(".sortIcon").removeClass("sort-asc");
                $(sortIcon).addClass("sort-asc");
                queryString = columnId + ' ASC, ';
            }

            $("#sortQuery").val(queryString);

            onConditionChange();
        }
    });

    // 업로드 버튼
    $("#btnExcelUpload").on('click', onBtnExcelUploadClick);
    // 검색 버튼
    $("#btnSearchDoc").on('click', onConditionChange);
    $("#txtSearchValue").on("keyup", function(e) {
        var cd = e.which || e.keyCode;
        //Enter 키
        if (cd == 13) {
            onConditionChange();
            e.preventDefault();
            e.stopPropagation();
        }
    });
    // 결과 내 재검색
    $("#rebrowsing").on('change', function() {
        var rebrowsing = $("#rebrowsing").prop('checked');
        
        if(!rebrowsing && $("#searchCondition").val()) {
            $("#searchCondition").val('');
            $("#rebrowsingList").empty();
            onConditionChange();
        } else if(rebrowsing && $("#txtSearchValue").val()) {
            onConditionChange();
        }
    });
})

function onBtnExcelUploadStdDocClick() {
    $("#modalUpload").modal("show");
}

//첨부파일 선택 시
function onAttachFileChange(obj) {
    var fileName = $(obj).val().split("\\").pop();
    $(obj).siblings(".custom-file-label").addClass("selected").html(fileName);
}

//첨부파일 삭제
function delAttachedFile(obj) {
    $("#isoExcel").val('');
    $("#isoExcel").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
}

// 업로드 버튼 클릭
function onBtnExcelUploadClick() {
    var proceed = true;
    var uploadFile = $("#isoExcel").val();
    //확장자분리
    var uploadExt = uploadFile.split('.').pop().toLowerCase();

    //파일이 없을 시
    if (!uploadFile) {
        proceed = false;
        $("#errorMsg").removeClass("alert-primary");
        $("#errorMsg").addClass("alert-danger");
        $("#errorMsg").empty().html('파일을 선택하세요.').fadeIn();
        $("#errorMsg").delay(5000).fadeOut();
    }
    //엑셀파일이 아닐 시
    else if ($.inArray(uploadExt, ['xlsx', 'xls']) == -1) {
        proceed = false;
        $("#errorMsg").removeClass("alert-primary");
        $("#errorMsg").addClass("alert-danger");
        $("#errorMsg").empty().html('엑셀파일을 선택하세요.').fadeIn();
        $("#errorMsg").delay(5000).fadeOut();
    }

    if (proceed) {
        var formdata = new FormData($("#mainForm")[0]);
        $.ajax({
            type: "POST",
            url: "/gw/iso/iso_excel_upload.php",
            data: formdata,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(result) {
                //세션 만료일 경우
                if (result["session_out"]) {
                    //로그인 화면으로 이동
                    onLogoutClick();
                }

                if (result["proceed"]) {
                    $("#errorMsg").removeClass("alert-danger");
                    $("#errorMsg").addClass("alert-primary");
                    $("#errorMsg").empty().html('성공적으로 업로드 되었습니다.').fadeIn();
                    $("#errorMsg").delay(5000).fadeOut();
                    getSheet();
                } else {
                    $("#errorMsg").removeClass("alert-danger");
                    $("#errorMsg").addClass("alert-primary");
                    $("#errorMsg").empty().html(result["errorMsg"]).fadeIn();
                    $("#errorMsg").delay(5000).fadeOut();
                }
            },
            beforeSend: function() {
                $("#btnExcelUpload").find("span.spinner-border").show();
            },
            complete: function() {
                $("#btnExcelUpload").find("span.spinner-border").hide();

                // 파일 삭제
                $("#isoExcel").val('');
                $("#isoExcel").siblings(".custom-file-label").removeClass("selected").html('<i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요');
            }
        })
    }
}

function onChangeSheet(sno, init = false) {
    // 결과 내 재검색
    var rebrowsing = $("#rebrowsing").prop('checked');

    if(rebrowsing) {
        $("#rebrowsing").prop('checked', false);
        $("#searchCondition").val('');
        rebrowsingHtml();
    }

    $("#sno").val(sno);

    $("#mode").val("CATEGORY");

    $.ajax({
        type: "POST",
        url: "/gw/iso/iso_doc_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            var categoryList = result["categoryList"];

            var html = '<option value="">전체</option>';
            $(categoryList).each(function(i, category) {
                html += '<option value="'+ category +'">' + category + '</option>';
            });

            $("#ddlCategory").empty().append(html);

            var html = '<option value="">전체</option>';
            $("#ddlKind").empty().append(html);
        },
        complete: function() {
            if(!init) {
                onCategoryChange();
            }
        }
    });
}

function onCategoryChange() {
    $("#mode").val("KIND");

    var category = $("#ddlCategory").val();

    if(category) {
        $.ajax({
            type: "POST",
            url: "/gw/iso/iso_doc_list.php",
            data: $("#mainForm").serialize(),
            dataType: "json",
            success: function(result) {
                var kindList = result["kindList"];
    
                var html = '<option value="">전체</option>';
                $(kindList).each(function(i, kind) {
                    html += '<option value="'+ kind +'">' + kind + '</option>';
                });
    
                $("#ddlKind").empty().append(html);
            }
        });
    } else {
        var html = '<option value="">전체</option>';
        $("#ddlKind").empty().append(html);
    }

    onConditionChange();
}

function onConditionChange() {
    $("#mode").val("LIST");

    isHome = $("#home").hasClass("active");
    var category = $("#ddlCategory").val();
    var searchValue = $("#txtSearchValue").val()
    var searchCondition = $("#searchCondition").val();

    if(isHome) {
        if(category || searchValue || searchCondition) {
            $('.nav-tabs li:eq(1) a').tab('show');
        }
        // $('#btnAll').trigger('click');
        // $('#btnAll').find("a").addClass("active");
        // $('#btnHome').find("a").removeClass("active");
        // $("#home").removeClass("active");
        // $("#list").addClass("active");
        // $('.nav-tabs a[href="#list"]').tab('show');
    }

    var rebrowsing = $("#rebrowsing").prop('checked');

    if(rebrowsing) {
        var searchKind = $("#ddlSearchKind").val();
        if(!searchKind) {
            searchKind = 'all';
        }
        
        if(searchValue) {
            if(searchCondition) {
                var addCondition = searchKind + "=" + searchValue;

                if(!searchCondition.includes(addCondition)) {
                    searchCondition += "♡" + addCondition;
                }
            } else {
                searchCondition += searchKind + "=" + searchValue;
            }

            $("#searchCondition").val(searchCondition);
        }
        rebrowsingHtml();
    } else {
        $("#searchCondition").val('');
    }
    $.ajax({
        type: "POST",
        url: "/gw/iso/iso_doc_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        beforeSend:function(){
            $("#modalLoading").modal("show");
            $("#tblIsoList tbody").empty();
        },
        success: function(result) {
            var isoList = result["isoList"]

            var html = '';
            $(isoList).each(function(i, row) {
                html += '<tr class="row">'
                html += '<td class="col-md-1 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += row["categoryNm"];
                html += '</div>'
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += row["categoryKind"];
                html += '</div>'
                html += '</td>';
                html += '<td class="col-md-2 col-1dot5 d-none d-md-block notAlign">';
                html += '<div class="h-100 d-flex align-items-center notAlign">';
                html += row["docCd"];
                html += '</div>'
                html += '</td>';
                html += '<td class="col-md col-7 notAlign">';
                html += '<div class="h-100 d-flex align-items-center notAlign" title="'+ row["docDetail"] + '">';
                html += '<div><strong>' + row["docNm"] + '</strong><div/>';
                html += '<div style="font-size:smaller;color:grey">' + row["hashTxt"] + '</div>';
                html += '</div>'
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += row["chargeDept"].replace("/", "<br/>");
                html += '</div>'
                html += '</td>';
                html += '<td class="col-md-1 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                html += row["chargeStaff"].replace("/", "<br/>");
                html += '</div>'
                html += '</td>';
                // PC
                html += '<td class="col-md-3 col-2dot5 d-none d-md-block">';
                html += '<div class="h-100 d-flex align-items-center">';
                var isManager = $("#isManager").val();
                if(row["canDownload"] == "Y" || isManager == "Y") {
                    html += '<table class="table-borderless" id="tblFunction">';
                    html += '<tr class="border-none">';
                    html += '<td class="text-left">Latest</td>';
                    html += '<td>:</td>';
                    html += '<td>';
                    if(row["ecmFileOid"]) {
                        html += `<a class="iso-link" href="javascript:void(0);" onclick="previewFile('${row["ecmFileOid"]}')">`;
                        html += '미리보기';
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += '미리보기';
                        html += '</span>'
                    }
                    html += '</td>';
                    html += '<td>|</td>';
                    html += '<td>';
                    if(row["ecmFileOid"]) {
                        html += `<a class="iso-link" href="javascript:void(0);" onclick="downloadFile('${row["ecmFileOid"]}')">`;
                        html += '다운로드';
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += '다운로드';
                        html += '</span>'
                    }
                    html += '</td>';
                    html += '<td></td>';
                    html += '<td></td>';
                    if(isManager == "Y") {
                        var viewCnt = 0;
                        if(row['viewCnt']) {
                            viewCnt = row['viewCnt'];
                        }
                        html += '<td rowspan="2" style="vertical-align: middle;text-align:center">(<span class="viewCnt">'+ viewCnt +'</span>)</td>';
                    }
                    html += '</tr>';
                    html += '<tr class="border-none">';
                    html += '<td class="text-left">ECM</td>';
                    html += '<td>:</td>';
                    html += '<td>';
                    if(row["ecmPropertyUrl"]) {
                        html += `<a class="iso-link" href="javascript:void(0);" onclick="showEcmProperty('${row["ecmPropertyUrl"]}', '${row["ecmFileOid"]}')">`;
                        html += '속성보기';
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += '속성보기';
                        html += '</span>'
                    }
                    html += '</td>';
                    html += '<td>|</td>';
                    html += '<td>';
                    var oid = encodeURIComponent(row["ecmFileOid"]);
                    if(row["ecmFullTxt"]) {
                        var ecmFullTxt = encodeURIComponent(row["ecmFullTxt"]);
                        html += `<a class="iso-link isoCopy" href="javascript:void(0);" onclick="saveClipBoard('${ecmFullTxt}', this, '${oid}', 'CopyUrl')">`;
                        html += 'URL복사';
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += 'URL복사';
                        html += '</span>'
                    }
                    html += '</td>';
                    html += '<td>|</td>';
                    html += '<td class="text-left">'
                    if(row["ecmDirPath"]) {
                        var ecmDirPath = encodeURIComponent(row["ecmDirPath"]);
                        html += `<a class="iso-link isoCopy" href="javascript:void(0);" onclick="saveClipBoard('${ecmDirPath}', this, '${oid}', 'CopyPath')">`;
                        html += '폴더경로 복사'
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += '폴더경로 복사'
                        html += '</span>'
                    }
                    html += '</td>';
                    html += '</tr>';
                    html += '</table>';
                } else {
                    html += '<table class="table-borderless">';
                    html += '<tr class="border-none">';
                    html += '<td>';
                    if(row["ecmFileOid"]) {
                        html += `<a class="iso-link" href="javascript:void(0);" onclick="previewFile('${row["ecmFileOid"]}')">`;
                        html += '미리보기';
                        html += '</a>';
                    } else {
                        html += '<span style="color:grey">'
                        html += '미리보기';
                        html += '</span>'
                    }
                    html += '</td>';
                    // html += '<td>|</td>';
                    // html += '<td>';
                    // if(row["ecmFileOid"]) {
                    //     html += `<a class="iso-link" href="javascript:void(0);" onclick="downloadFile('${row["ecmFileOid"]}')">`;
                    //     html += '다운로드';
                    //     html += '</a>';
                    // } else {
                    //     html += '<span style="color:grey">'
                    //     html += '다운로드';
                    //     html += '</span>'
                    // }
                    // html += '</td>';
                    html += '</tr>';
                    html += '</table>';
                }
                html += '</div>'
                html += '</td>';
                // 모바일
                html += '<td class="d-md-none d-block col-5">'; 
                html += '<div class="h-100 d-flex align-items-center">';
                // 테이블로 삽입
                html += '<table class="table-borderless">';
                html += '<tr class="border-none">';
                html += '<td>';
                if(row["ecmFileOid"]) {
                    html += `<a class="iso-link" href="javascript:void(0);" onclick="previewFile('${row["ecmFileOid"]}')">`;
                    html += '미리보기';
                    html += '</a>';
                } else {
                    html += '<span style="color:grey">'
                    html += '미리보기';
                    html += '</span>'
                }
                html += '</td>';
                // html += '<td>|</td>';
                // html += '<td>';
                // if(row["ecmFileOid"]) {
                //     html += `<a class="iso-link" href="javascript:void(0);" onclick="downloadFile('${row["ecmFileOid"]}')">`;
                //     html += '다운로드';
                //     html += '</a>';
                // } else {
                //     html += '<span style="color:grey">'
                //     html += '다운로드';
                //     html += '</span>'
                // }
                // html += '</td>';
                html += '</tr>';
                html += '</table>';
                html += '</div>'
                html += '</td>';
                html += '</tr>';
            })

            $("#tblIsoList tbody").append(html);
        },
        complete: function() {
            // 스크롤 초기화
            $('#tblIsoList').closest('div.tableFixHead').scrollTop(0);

            setTimeout(function () {
                if ($("#modalLoading").hasClass('show')) {
                    $("#modalLoading").modal("hide");
                }
            }, 500);

            // cnt 동적 상승
            $(".iso-link").on('click', function() {
                var isViewCnt = $(this).closest("table").find(".viewCnt").length;
                if(isViewCnt) {
                    var cnt = $(this).closest("table").find(".viewCnt").html();
                    cnt++;
                    $(this).closest("table").find(".viewCnt").html(cnt);
                }
            });

            $('.isoCopy').popover({content: `클립보드 복사됨 <i class="fa-solid fa-clipboard-check"></i>`, html: true, placement: "bottom"});
        }
    });
}

// 클립보드에 저장
function saveClipBoard(txt, obj, oid, type) {
    var textToCopy = decodeURIComponent(txt);
    var oid = decodeURIComponent(oid);

    // 임시 텍스트 영역을 생성
    var $temp = $('<textarea>');
    $('body').append($temp);
    $temp.val(textToCopy).select();

    // 클립보드에 복사
    document.execCommand('copy');

    // 임시 텍스트 영역 제거
    $temp.remove();

    setTimeout(function () {
        $(obj).popover('hide');
    }, 2000);

    // 로그남기기
    recordLog(type, oid);
}

// 파일 다운로드
function downloadFile(oid) {
    $.ajax({ 
        url: "https://ecm.htenc.co.kr/restApi/file/download/fileOID", 
        data: { fileOID: oid },  // HTTP 요청과 함께 서버로 보낼 데이터 
        type: "POST", 
        xhrFields: { 
            responseType: "blob" 
        } 
    })
    .done(function(data, status, xhr) { 
        let filename;  
        let disposition = xhr.getResponseHeader('Content-Disposition');  
        //let contentType = xhr.getResponseHeader('Content-Type'); 
        //decodeURI(xhr.getResponseHeader('Content-Disposition')); 
        //xhr.getResponseHeader('Content-Disposition');  
            
        if (disposition && disposition.indexOf('attachment') !== -1) {  
            let filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;  
            let matches = filenameRegex.exec(disposition);  
            if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');  
        }  

        filename = decodeURI(filename); 

        let blob = new Blob([data]);  
        //let blob = new Blob([data], {type: contentType});  
        let link = document.createElement('a');  
        link.href = window.URL.createObjectURL(blob);  
        link.download = filename;  
        link.click();  

        // 로그 남기기
        recordLog("Download", oid);
    }) 
}

// IMG 탭 이동
function moveImgMap(tap) {
    var imgObj = $('.nav-link').filter(function() {
        return $(this).text() === tap;
    });

    imgObj.trigger('click');
}

// 시트 가져오기
function getSheet() {
    $("#mode").val("SHEET");

    $.ajax({
        type: "POST",
        url: "/gw/iso/iso_doc_list.php",
        data: $("#mainForm").serialize(),
        dataType: "json",
        success: function(result) {
            $("#fno").val(result["fno"]);

            var sheetList = result["sheetList"];

            var html = `<li class="nav-item" id="btnHome" onclick="onChangeSheet('', true)">
                            <a class="nav-link active" data-toggle="tab" href="#home">HTE</a>
                        </li>
                        <li class="nav-item" id="btnAll" onclick="onChangeSheet('')">
                            <a class="nav-link" data-toggle="tab" href="#list">전체</a>
                        </li>`;
            $(sheetList).each(function(i, item) {
                html += '<li class="nav-item" onclick="onChangeSheet('+ item["sno"] +')">';
                html += '<a class="nav-link" data-toggle="tab" href="#list">' + item["sheetNm"] + '</a>';
                html += '</li>';
            });

            $("#tabList").empty().append(html);
            
            var hashTagList = result["hashTagList"]
            autocomplete($("#txtSearchValue"), hashTagList);

            var isManager = result["isManager"];
            $("#isManager").val(isManager);

            if(isManager == 'Y') {
                if($(".searchBtn").length == 0) {
                    $("#divSearch .row").append(`<div class="col-lg-2 col-xl-2 text-right mr-3 searchBtn" style="padding:0 !important; padding-top:0.2rem !important">
                                                    <button type="button" class="btn btn-primary mr-2" id="btnExcelUploadStdDoc" name="btnExcelUploadStdDoc" style="display:none">업로드</button>
                                                    <button type="button" class="btn btn-primary" id="btnExcelDownStdDoc" name="btnExcelDownStdDoc" style="display:none">다운로드</button>
                                                </div>`);
                }
                $("#btnExcelUploadStdDoc").show();
                $("#btnExcelDownStdDoc").show();
            } else {
                $("#btnExcelUploadStdDoc").hide();
                $("#btnExcelDownStdDoc").hide();
            }
        },
        complete: function() {
            $('#btnHome').trigger('click');
            $("#list").removeClass("active");
            $("#home").addClass("active");
            // 업로드 버튼
            $("#btnExcelUploadStdDoc").on('click', onBtnExcelUploadStdDocClick);
            // 다운로드
            $("#btnExcelDownStdDoc").on('click', onBtnExcelDownClick);
        }
    });
}

// 목록 다운로드
function onBtnExcelDownClick() {
    var fno = $("#fno").val()
    location.href = '/gw/iso/iso_excel_download.php?fno=' + fno;
}

function resizeMap() {
    var image = $('#isoDocImg');
    var originalWidth = image[0].naturalWidth;
    var map = $('map[name="isoDocMap"]');
    var areas = map.find('area');

    var scale = image.width() / originalWidth;
    areas.each(function() {
        var coords = $(this).attr('coords').split(',').map(Number);
        var scaledCoords = coords.map(function(coord) {
            return coord * scale;
        });
        $(this).attr('coords', scaledCoords.join(','));
    });
}

// 속성보기
function showEcmProperty(url, oid) {
    window.open(url, '_blank');

    recordLog("Property", oid);
}

// 결과 내 재검색 HTML
function rebrowsingHtml() {
    var searchCondition = $("#searchCondition").val();

    var html = '';
    if(searchCondition) {
        var conditionArray = searchCondition.split("♡");
    
        var arrangeArray = {};
        $(conditionArray).each(function(i, con) {
            var parts = con.split('=');
            var key = parts[0];
            var value = parts[1];
    
            if (!arrangeArray[key]) {
                arrangeArray[key] = value;
            } else {
                arrangeArray[key] += '♡' + value;
            }
        });

        $.each(arrangeArray, function(i, text) {
            var optionNM = '';
            if(i == 'all') {
                optionNm = '전체';
            } else {
                optionNm = $('option[value="'+ i +'"]').text();
            }
            html += '<span class="badge badge-info mr-2" style="font-size:small">';
            html += '<span class="'+ i +'">' + optionNm + '</span>';
            html += ' <span class="deleteOption" style="color:black;cursor:pointer">X</span></span>';
    
            var searchElement = text.split("♡");
            $.each(searchElement, function(j, element) {
                html += '<span class="mr-2" style="background-color:#CCE5FF">';
                html += '<span class="'+ i +'">' + element + '</span>';
                html += ' ' + '<span class="deleteWord" style="color:black;cursor:pointer;">X</span></span>';
            });
        }); 
    }
    $("#rebrowsingList").empty().html(html);

    // 검색어 삭제
    $(".deleteWord").on('click', function() {
        var classNm = $(this).siblings("span").attr("class");
        var word = $(this).siblings("span").text();

        removeSearchCondition(word, classNm);
        onConditionChange();
    });

    $(".deleteOption").on('click', function() {
        var classNm = $(this).siblings("span").attr("class");

        $("." + classNm).not(":first").each(function() {
            var word = $(this).text();
            removeSearchCondition(word, classNm)
        });

        onConditionChange();
    });
    
    $("#txtSearchValue").val('');
}

// 검색 조건 remove 정규식
function removeSearchCondition(word, classNm) {
    word = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    var removeWord = classNm + "=" + word;
    var regex = new RegExp('(^|♡)' + removeWord + '(?=♡|$)', 'g');
    var searchCondition = $("#searchCondition").val();

    var newStr = searchCondition.replace(regex, function(match, p1) {
        return p1 === '♡' ? '♡' : '';
    });

    newStr = newStr.replace(/♡{2,}/g, '♡');
    newStr = newStr.replace(/^♡/, '');
    newStr = newStr.replace(/♡$/, '');

    $("#searchCondition").val(newStr);
}
</script>
<form id="mainForm" name="mainForm" method="post" enctype="multipart/form-data">
    <!-- <div class="btnList">
    </div> -->
    <!-- <button type="button" class="btn btn-primary mr-2" id="btnExcelUploadStdDoc" name="btnExcelUploadStdDoc" style="display:none">업로드</button>
    <button type="button" class="btn btn-primary" id="btnExcelDownStdDoc" name="btnExcelDownStdDoc" style="display:none">다운로드</button> -->
    <div id="divIsoContent">
        <div id="divSearch">
            <div class="row d-flex align-items-center mt-2">
                <div class="col-lg-2 col-xl-2 search-inline">
                    <label>분류</label>
                    <select class="form-control mr-2" id="ddlCategory" name="ddlCategory" onchange="onCategoryChange()">
                        <option value="">전체</option>
                    </select>
                </div>
                <div class="col-lg-2 col-xl-2 search-inline">
                    <label class="labelMargin">구분</label>
                    <select class="form-control" id="ddlKind" name="ddlKind" onchange="onConditionChange()">
                        <option value="">전체</option>
                    </select>
                </div>
                <div class="col-lg col-xl">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <select class="form-control" id="ddlSearchKind" name="ddlSearchKind">
                                <option value="">전체</option>
                                <option value="charge_dept">주관부서</option>
                                <option value="charge_staff">담당자</option>
                                <option value="doc_cd">Doc.No.</option>
                                <option value="doc_nm">문서 제목</option>
                                <option value="doc_detail">주요 내용</option>
                                <option value="hash_txt">해시태그</option>
                            </select>
                        </div>
                        <input type="search" class="form-control" id="txtSearchValue" name="txtSearchValue" maxlength="50" />
                        <!-- <input type="text" class="form-control" id="hidPreventSubmit" name="hidPreventSubmit" style="display: none;" /> -->
                        <div class="input-group-append">
                            <button type="button" id="btnSearchDoc" name="btnSearchDoc" class="btn btn-info">
                                <span class="spinner-border spinner-border-sm" style="display: none;"></span>
                                <span class="fas fa-magnifying-glass"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-1 col-xl-1 text-right mr-3" style="padding:0 !important; padding-top:0.2rem !important">
                    <div class="custom-control custom-switch" style="padding:0 !important" title="결과 내 재검색">
                        <input type="checkbox" class="custom-control-input" id="rebrowsing" name="rebrowsing" value="Y">
                        <label class="custom-control-label" for="rebrowsing">결과 내 재검색</label>
                    </div>
                </div>
                <!-- <div class="col-lg-2 col-xl-2 text-right mr-3 searchBtn" style="padding:0 !important; padding-top:0.2rem !important">
                    <button type="button" class="btn btn-primary mr-2" id="btnExcelUploadStdDoc" name="btnExcelUploadStdDoc" style="display:none">업로드</button>
                    <button type="button" class="btn btn-primary" id="btnExcelDownStdDoc" name="btnExcelDownStdDoc" style="display:none">다운로드</button>
                </div> -->
            </div>
            <div id="rebrowsingList" class="py-2" style="height:40px"></div>
        </div>
        <div>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs nav-pills" id="tabList">
                <li class="nav-item" id="btnHome" onclick="onChangeSheet('', true)">
                    <a class="nav-link active" data-toggle="tab" href="#home">HTE</a>
                </li>
                <li class="nav-item" id="btnAll" onclick="onChangeSheet('')">
                    <a class="nav-link" data-toggle="tab" href="#list">전체</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div id="home" class="tab-pane active">
                    <div class="row align-items-stretch">
                        <div class="col-md p-3">
                            <table class="tblIsoDetail">
                                <tr>
                                    <th class="pr-5">•</th>
                                    <th>문서 구조 및 체계</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td class="font-lager"><span class="pr-3">-</span>표준 문서의 제정 및 유지관리 참고</td>
                                </tr>
                                <tr style="height:50px"></tr>
                            </table>
                            <img src="/gw/images/iso/iso_img.png" style="width:100%" id="isoDocImg">
                        </div>
                        <div class="col-md p-3">
                            <table class="tblIsoDetail">
                                <tr>
                                    <th class="pr-5">•</th>
                                    <th>회사 표준</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td class="font-lager"><span class="pr-3">-</span>HTE 조직 내에서 일관되게 적용하는 규칙, 절차, 지침 또는 방법을 의미</td>
                                </tr>
                                <tr style="height:50px"></tr>
                                <tr>
                                    <th class="pr-5">•</th>
                                    <th>회사 표준 목적</th>
                                </tr>
                                <tr></tr>
                                <tr>
                                    <td></td>
                                    <td class="font-lager">
                                        <span class="pr-3">-</span>업무 효율성<br/>
                                        <span class="pr-3">&nbsp;</span>일관된 절차를 통해 업무를 보다 빠르고 효율적으로 수행<br/>
                                        <span class="pr-3">-</span>품질 관리<br/>
                                        <span class="pr-3">&nbsp;</span>제품이나 서비스의 품질을 일정하게 유지하고, 고객의 기대에 부응<br/>
                                        <span class="pr-3">-</span>안전 및 규정 준수<br/>
                                        <span class="pr-3">&nbsp;</span>작업 환경에서 안전을 보장하고, 법적 규제나 산업 규범을 준수<br/>
                                        <span class="pr-3">-</span>일관성 유지<br/>
                                        <span class="pr-3">&nbsp;</span>직원 간, 부서 간의 업무 방식이 일관되게 유지되어 혼란을 줄임<br/>
                                        <span class="pr-3">-</span>지속적인 개선<br/>
                                        <span class="pr-3">&nbsp;</span>표준화된 절차를 통해 개선이 필요한 부분을 쉽게 파악하고 개선
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="list" class="tab-pane pt-4">
                    <div class="tableFixHead">
                        <table class="table" id="tblIsoList" style="table-layout: fixed;">
                            <thead class="thead-light">
                                <tr class="row">
                                    <th class="col-md-1 d-none d-md-block" id="category_nm"><span class="mx-2">분류</span><span class="sortIcon"></span></th>
                                    <th class="col-md-1 d-none d-md-block" id="category_kind"><span class="mx-2">구분</span><span class="sortIcon"></span></th>
                                    <th class="col-md-2 col-1dot5 d-none d-md-block" id="doc_cd"><span class="mx-2">Doc. No.</span><span class="sortIcon"></span></th>
                                    <th class="col-md col-7" id="doc_nm"><span class="mx-2">문서 제목</span><span class="sortIcon"></span></th>
                                    <th class="col-md-1 d-none d-md-block" id="charge_dept"><span class="mx-2">주관부서</span><span class="sortIcon"></span></th>
                                    <th class="col-md-1 d-none d-md-block" id="charge_staff"><span class="mx-2">담당자</span><span class="sortIcon"></span></th>
                                    <th class="col-md-3 col-2dot5 col-5">기능</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalUpload" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">표준목록 업로드</h4>
                    <button type="button" class="close btn-close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body mainContents">
                    <!-- <div class="row">
                    <div class="col-3 colHeader">양식 다운로드</div>
                    <div class="col-9">
                        <button type="button" class="btn btn-warning btn-sm py-0 ml-2" id="btnFormDownload" name="btnFormDownload"><i class="fa-solid fa-file-excel"></i> 양식 다운로드</button>
                    </div>
                </div> -->
                    <div class="row">
                        <div class="col-3 colHeader">엑셀파일</div>
                        <div class="col-9">
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="isoExcel" name="isoExcel" onchange="onAttachFileChange(this)" accept="application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
                                    <label class="custom-file-label" for="customFile"><i class="fa-solid fa-cloud-arrow-up"></i> 파일을 선택하세요</label>
                                </div>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" id="btnExcelDel" name="btnExcelDel" onclick="javascript:delAttachedFile(this);">&times;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="errorMsg" class="alert alert-danger mt-2 py-1" style="display: none;"></div>
                    <!-- <div id="returnValue" style="display: none;">
                    <table class="table">
                        <thead class="thead-light">
                            <tr class="row">
                                <th class="col-2">그룹명</th>
                                <th class="col-2">이름</th>
                                <th class="col-2">회사명</th>
                                <th class="col-2">휴대전화</th>
                                <th class="col-2">메일주소</th>
                                <th class="col-2">사유</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div> -->
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-around">
                            <button type="button" class="btn btn-primary" id="btnExcelUpload" name="btnExcelUpload">
                                업로드&nbsp;<span class="spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<input type="hidden" id="mode" name="mode" />
<input type="hidden" id="sno" name="sno" />
<input type="hidden" id="oid" name="oid" />
<input type="hidden" id="fno" name="fno" />
<input type="hidden" id="sortQuery" name="sortQuery" />
<input type="hidden" id="actType" name="actType" />
<input type="hidden" id="isManager" name="isManager" />
<input type="hidden" id="searchCondition" name="searchCondition" />
</form>
