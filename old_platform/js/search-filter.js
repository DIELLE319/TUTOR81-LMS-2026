/**
 * file: search-filter.js
 * author: Roberto Zaniol
 * 
 * 
 * 	FORM FOR FILTER SEARCH:
 * 		<div id="search_company" class="nav nav-list" style="display: none">
 *  		<form id="search_company_form" class="filterform">
 *      		<input type="text" id="search_company_name" name="search_company_name" class="filterinput" required="required" placeholder="nome azienda" autofocus="autofocus" maxlength="255" title="Inserisci il nome dell'azienda"/>
 *				<img alt="cancella testo" src="img/clean.png" class="icon_clear" onclick="clearSearchCompany()" title="cancella inserimento"/>
 *			</form>
 *		</div>
 * 
 *  ON DOM READY CALL TO FILTER SEARCH
 * 		$(function () {
 *   		listFilter($("#search_company_name"), $("#companies"),"a","li");
 * 		});
 * 
 * 	EXAMPLE LIST TO FILTER:
 * 		<ul id="companies" class="nav nav-list">
 *      	<li ><a href="#">ALCOA</a></li>
 *      	<li ><a href="#">AMAZON</a></li>
 *      	<li ><a href="#">APPLE</a></li>
 *      	<li ><a href="#">BLACKROCK</a></li>
 *      	<li ><a href="#">BROADCOM</a></li>
 *      	<li ><a href="#">CISCO</a></li>
 *      	<li ><a href="#">CITRIX</a></li>
 *      	<li ><a href="#">......</a></li>
 *		</ul>
 * 
 * 	PLUS:
 * 		searchFilter(filter_box) display an hidden box (div, span or other tag) that contain
 * 		form whit input text for filter search:
 * 
 * 		clearSearchFilter(filter_form, list, tag) reset input text for filter search and show tag in list
 */


	// custom css expression for a case-insensitive contains()
	jQuery.expr[':'].Contains = function(a,i,m){
    	return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
  	};

  	function listFilter(header, list, tag, parent_tag) { 
        $(header).change( function () {
        	var filter = $(this).val();
        	if(filter) {
          		$(list).find(tag+":not(:Contains(" + filter + "))").parents(parent_tag).slideUp();
          		$(list).find(tag+":Contains(" + filter + ")").parents(parent_tag).slideDown();
        	} else {
          		$(list).find(parent_tag).slideDown();
        	}
        	return false;
      	}).keyup( function () {
        	$(this).change();
    	});
  	}

	
	function changeInput(header,value){
		$(header).val(value).change();
	}

	function searchFilter(filter_box){
        if($(filter_box).css("display") == "none"){
            $(filter_box).slideDown();
        }else{
            $(filter_box).slideUp();
        }        
    }

    function clearSearchFilter(filter_form, list, tag){
		$(filter_form)[0].reset();
		$(list+" "+tag).slideDown();
    }