<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/department.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
$dep_obj = new Departments();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$res = 0;

// ADD PRODUCT UNIT
if ($op_type == "add_product_unit") {
    if ($_REQUEST['short_desc'] != "" && $_REQUEST['company_id'] > 0) {
        $res = $dep_obj->addProductUnit($_REQUEST['short_desc'], $_REQUEST['long_desc'], $_REQUEST['company_id']);
    } else {
        $res = false;
    }



// EDIT PRODUCT UNIT
}
elseif ($op_type == "edit_product_unit") {
    $res = $dep_obj->setProductUnit($_REQUEST['id_pu'], $_REQUEST['short_desc_pu'], $_REQUEST['long_desc_pu']);



// DELETE PRODUCT UNIT
}
elseif ($op_type == "delete_product_unit") {
    $res = $dep_obj->deleteProductUnit($_REQUEST['id_pu']);



// ADD DEPARTMENT TYPE
}
elseif ($op_type == "add_department_type") {
    if ($_REQUEST['short_desc'] != "" && $_REQUEST['company_id'] > 0) {
        $res = $dep_obj->addDepartmentType($_REQUEST['short_desc'], $_REQUEST['long_desc'], $_REQUEST['company_id']);
    } else {
        //$res = false;
        $res = 0;
    }



// EDIT DEPARTMENT TYPE FOR ALL PRODUCT UNIT
}
elseif ($op_type == "edit_department_type") {
    $dep_type = $dep_obj->getDepartmentsTypeByShortDescription($_REQUEST['short_desc_dep_type'], $_REQUEST['company_id']);
    if ($dep_type) {
        $res = $dep_type['_id_dep_type'];
        $departments = $dep_obj->getDepartmentsByDepartmentType($_REQUEST['id_dep_type']);
        foreach ($departments as $dep) {
            $res += $dep_obj->setDepartment($dep['id_dep'], $dep_type['id_dep_type']);
        }
    } else
        $res = $dep_obj->setDepartmentType($_REQUEST['id_dep_type'], $_REQUEST['short_desc_dep_type'], $_REQUEST['long_desc_dep_type']);



// EDIT LONG DESCRIPTION DEPARTMENT TYPE FOR ALL PRODUCT UNIT
}
elseif ($op_type == "edit_long_description_department_type") {
    $res = $dep_obj->setDepartmentType($_REQUEST['id_dep_type'], $_REQUEST['short_desc_dep_type'], $_REQUEST['long_desc_dep_type']);



// DELETE DEPARTMENT TYPE
}
elseif ($op_type == "delete_department_type") {
    $res = $dep_obj->deleteDepartmentType($_REQUEST['id_dep_type']);



// ADD DEPARTMENT
}
elseif ($op_type == "add_department") {
    $res = $dep_obj->addDepartmentInProductUnit($_REQUEST['dep_type_id'], $_REQUEST['pu_id']);



// EDIT DEPARTMENT RISKS
}
elseif ($op_type == "edit_dep_risks") {
    $res = $dep_obj->setDepartmentRisks($_REQUEST['id_dep'], $_REQUEST['risks'], $_REQUEST['other_risk']);



// EDIT DEPARTMENT FOR ONE PRODUCT UNIT
}
elseif ($op_type == "edit_department") {
    $dep_type = $dep_obj->getDepartmentsTypeByShortDescription($_REQUEST['short_desc_dep_type'], $_REQUEST['company_id']);
    if ($dep_type)
        $dep_type_id = $dep_type['id_dep_type'];
    else
        $dep_type_id = $dep_obj->addDepartmentType($_REQUEST['short_desc_dep_type'], $_REQUEST['long_desc_dep_type'], $_REQUEST['company_id']);

    $res = $dep_obj->setDepartment($_REQUEST['id_dep'], $dep_type_id);



// DELETE DEPARTMENT
}
elseif ($op_type == "delete_department") {
    $res = $dep_obj->deleteDepartment($_REQUEST['id_dep']);



// ADD EMPLOYEE
}
elseif ($op_type == "add_employee") {
    $res = $dep_obj->addEmployeeInDepartment($_REQUEST['user_id'], $_REQUEST['dep_id'], $_REQUEST['hire_date']);




// DELETE EMPLOYEE
}
elseif ($op_type == "delete_employee") {
    $res = $dep_obj->deleteEmployeeInDepartment($_REQUEST['id_dep_empl']);




// DELETE HISTORY EMPLOYEE DEPARTMENTS
}
elseif ($op_type == "delete_history_employee_departments") {
    $res = $dep_obj->deleteHistoryEmployeeDepartment($_REQUEST['user_id']);



// DISMISS EMPLOYEE
}
elseif ($op_type == "dismiss_employee") {
    $res = $dep_obj->dismissEmployeeByIdDepEmplId($_REQUEST['id_dep_empl'], $_REQUEST['dismissal_date']);



// ADD PRODUCT UNIT CUSTOM CATEGORY
}
elseif ($op_type == "add_product_unit_custom_category") {
    $res = $dep_obj->addProductUnitCustomCategory($_REQUEST['pu_id'], $_REQUEST['ccat_id']);



// EDIT PRODUCT UNIT CUSTOM CATEGORY
}
elseif ($op_type == "edit_product_unit_custom_category") {
    $res = $dep_obj->editProductUnitCustomCategory($_REQUEST['id_pu_ccat'], $_REQUEST['ccat_id']);



// ADD PRODUCT UNIT ATECO
}
elseif ($op_type == "add_product_unit_ateco") {
    $res = $dep_obj->addAtecoSectorInProductUnit($_REQUEST['pu_id'], $_REQUEST ['ateco_id']);



// EDIT PRODUCT UNIT ATECO
}
elseif ($op_type == "edit_product_unit_ateco") {
    $res = $dep_obj->editAtecoSectorInProductUnit($_REQUEST['id_pu_ateco'], $_REQUEST['ateco_id']);



// ADD PRODUCT UNIT ATECO RISK
}
elseif ($op_type == "add_product_unit_ateco_risk") {
    $res = $dep_obj->addAtecoRiskInProductUnit($_REQUEST['pu_id'], $_REQUEST ['ateco_risk_id']);



// EDIT PRODUCT UNIT ATECO RISK
}
elseif ($op_type == "edit_product_unit_ateco_risk") {
    $res = $dep_obj->editAtecoRiskInProductUnit($_REQUEST['id_pu_ateco_risk'], $_REQUEST['ateco_risk_id']);



// LOAD SAFETY PRODUCT UNIT
}
elseif ($op_type == "department_employees") {
    $list_employees = $dep_obj->getEmployeesByDepartments($_REQUEST['dep_id']);

    $res = '<ul class="nav nav-list">';

    foreach ($list_employees as $employee) {
        $res .= '<li class="link_empl" id="link_empl_' . $employee['user_id'] . '" data-employee-id="' . $employee['user_id'] . '">';
        $res .= '<a href="javascript:void(0)">' . ucwords(strtolower($employee['surname'] . ' ' . $employee['name'])) . '</a>';
        $res .= '</li>';
    }



    // LOAD SAFETY PRODUCT UNIT
}
elseif ($op_type == "load_safety_evaluation_product_unit") {
    require_once BASE_LIBRARY_PATH . 'class_custom_category.php';
    $ccat_obj = new CustomCategory();

    $custom_categories = $dep_obj->getProductUnitCustomCategories($_REQUEST['pu_id']);

    $list_fire_risk = $ccat_obj->getCustomCategoriesByLev1(2);
    $pu_fire_risk = $dep_obj->getProductUnitSpecificCustomCategories($_REQUEST['pu_id'], 2);

    $list_first_aid_risk = $ccat_obj->getCustomCategoriesByLev1(3);
    $pu_first_aid_risk = $dep_obj->getProductUnitSpecificCustomCategories($_REQUEST['pu_id'], 3);

    $list_50dip_risk = $ccat_obj->getCustomCategoriesByLev1(4);
    $pu_50dip_risk = $dep_obj->getProductUnitSpecificCustomCategories($_REQUEST['pu_id'], 4);

    $list_ateco_sectors = $dep_obj->getAtecoList();
    $pu_ateco_sector = $dep_obj->getProductUnitAteco($_REQUEST['pu_id']);


    $res = <<< EOT
 	<h4 style="text-align: center;">Impostazioni Generali</h4>
 	<div class="form-horizontal">
 		<div class="control-group">
    	<label class="control-label" for="fire_risk">Livello di Rischio INCENDIO</label>
    	<div class="controls">
      	<select name="fire_risk" data-fire_risk="{$pu_fire_risk['id_pu_ccat']}" onchange="onChangeFireRisk({$_REQUEST['pu_id']})">
EOT;
    for ($lev_3 = 1; $lev_3 < count($list_fire_risk); $lev_3++) {
        $selected = $list_fire_risk[$lev_3]['id'] == $pu_fire_risk['ccat_id'] ? "selected" : "";
        $res .= "<option $selected value='{$list_fire_risk[$lev_3]['id']}'>{$list_fire_risk[$lev_3]['definition']}</option>";
    }
    $res .= <<< EOT
 				</select>
 				<input type="hidden" name="new_fire_risk" value="{$selected}">
    	</div>
		</div>
 	
 			
 		<div class="control-group">
    	<label class="control-label" for="first_aid_risk">Livello di Rischio PRIMO SOCCORSO</label>
    	<div class="controls">
      	<select name="first_aid_risk" data-first_aid_risk="{$pu_first_aid_risk['id_pu_ccat']}" onchange="onChangeFirstAidRisk({$_REQUEST['pu_id']})">
EOT;
    for ($lev_3 = 1; $lev_3 < count($list_first_aid_risk); $lev_3++) {
        $selected = $list_first_aid_risk[$lev_3]['id'] == $pu_first_aid_risk['ccat_id'] ? "selected" : "";
        $res .= "<option $selected value='{$list_first_aid_risk[$lev_3]['id']}'>{$list_first_aid_risk[$lev_3]['definition']}</option>";
    }

    $res .= <<< EOT
 				</select>
    	</div>
		</div>
 			
 		<div class="control-group">
 			<label class="control-label" style="margin-top: 10px;">Numero dipendenti</label>
 			<div class="controls">
 				<fieldset id="50dip_risk" data-50dip_risk="{$pu_50dip_risk['id_pu_ccat']}">
EOT;

    for ($lev_3 = 1; $lev_3 < count($list_50dip_risk); $lev_3++) {
        $checked = $list_50dip_risk[$lev_3]['id'] == $pu_50dip_risk['ccat_id'] ? "checked" : "";

        $res .= '<input type="radio" name="pu_50dip" value="' . $list_50dip_risk[$lev_3]['id'] . '" ' . $checked . ' onclick="onChange50dipRisk(' . $_REQUEST['pu_id'] . ',' . $list_50dip_risk[$lev_3]['id'] . ')">';
        $res .= '<label>' . $list_50dip_risk[$lev_3]['definition'] . '</label>';
    }

    $res .= <<< EOT
 				</fieldset>
 			</div>
		</div>
 	
 			
 		<div class="control-group">
    	<label class="control-label" for="ateco_id">Settore Ateco 2007</label>
    	<div class="controls">
      	<select name="ateco_id" data-ateco_id="{$pu_ateco_sector['id_pu_ateco']}" onchange="onChangeAtecoSector({$_REQUEST['pu_id']})">
EOT;
    foreach ($list_ateco_sectors as $ateco_sector) {
        $selected = $ateco_sector['id'] == $pu_ateco_sector['ateco_id'] ? "selected" : "";
        $res .= "<option $selected value='{$ateco_sector['id']}'>{$ateco_sector['name']}</option>";
    }

    $res .= <<< EOT
 				</select>
    	</div>
		</div> 			
 	</div> 	
EOT;



// LOAD PU DEPARTMENTS
}
elseif ($op_type == "load_pu_departments") {
    $departments = $dep_obj->getDepartmentsByProductUnit($_REQUEST['pu_id']);
    $preselect = isset($_REQUEST['preselect']) ? $_REQUEST['preselect'] : 0;

    $res = <<< EOT
	<label class="control-label">Reparti</label>
	<div class="controls">
		<select class="input-medium" name="dep_id">
EOT;

    foreach ($departments as $dep) {
        $selected = $dep['id_dep'] == $preselect ? 'selected' : '';

        $res .= <<< EOT
		<option value="{$dep['id_dep']}" {$selected}>{$dep['short_desc_dep_type']}</option>
EOT;
    }

    $res .= <<< EOT
		</select>
		<a class="btn btn-default" href="#addDepartmentModal" data-toggle="modal"><i class="icon-plus"></i></a>
	</div>
EOT;


// GET PU
}
elseif ($op_type == "get_pu") {

    $product_units = $dep_obj->getProductUnits($_REQUEST['pu_id']);

    $res = $product_units ? json_encode($product_units) : 0;

// GET PU DEPARTMENTS
}
elseif ($op_type == "get_pu_departments") {
    if ($_REQUEST['pu_id'] === "all") {
        $departments = $dep_obj->getDepartmentsByCompany($_REQUEST['company_id']);
    } else {
        $departments = $dep_obj->getDepartmentsByProductUnit($_REQUEST['pu_id']);
    }
    $res = $departments ? json_encode($departments) : 0;
}

echo $res;