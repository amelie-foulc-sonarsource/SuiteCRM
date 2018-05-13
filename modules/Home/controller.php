<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2017 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

include_once("include/InlineEditing/InlineEditing.php");
require_once "data/BeanDuplicateCheckRules.php";

class HomeController extends SugarController{


    public function action_getEditFieldHTML(){

        if($_REQUEST['field'] && $_REQUEST['id'] && $_REQUEST['current_module']){

            $html = getEditFieldHTML($_REQUEST['current_module'], $_REQUEST['field'], $_REQUEST['field'] , 'EditView', $_REQUEST['id']);
            echo $html;
        }

    }

    public function action_saveHTMLField(){

        if($_REQUEST['field'] && $_REQUEST['id'] && $_REQUEST['current_module']){

            echo saveField($_REQUEST['field'], $_REQUEST['id'], $_REQUEST['current_module'], $_REQUEST['value'], $_REQUEST['view']);

        }

    }

    public function action_getDisplayValue(){

        if($_REQUEST['field'] && $_REQUEST['id'] && $_REQUEST['current_module'] ){

            $bean = BeanFactory::getBean($_REQUEST['current_module'],$_REQUEST['id']);

            if(is_object($bean) && $bean->id != ""){
                echo getDisplayValue($bean, $_REQUEST['field'],"close");
            }else{
                echo "Could not find value.";
            }

        }

    }

    public function action_checkUniqueRules()
    {
        global $app_strings;
        $arules = json_decode(html_entity_decode($_REQUEST['rules']));
        if($_REQUEST['rules'] && $_REQUEST['current_module']){
            if($_REQUEST['id']){
                $bean = BeanFactory::getBean($_REQUEST['current_module'],$_REQUEST['id']);
            } else {
                $bean = new $_REQUEST['current_module'];
            }
            if (!$bean->has_duplicate_check){
                $return_json["iserror"] = true;
                $return_json["error"] = 
                array(
                    array(
                        "type" => "system",
                        "msg" => $app_strings["LBL_ERROR_UNIQUE_CHECK"],
                    ),
                );
            } else {
                $return_json["iserror"] = false;
                $return_json["error"] = array();
                foreach($arules as $rules ){
                    foreach($rules as $rule ){
                        $result = $bean->duplicate_check->checkForDuplicateCheckRule( $rule, $bean, $_REQUEST['current_module'] ); 
                        switch($result["return"]){
                            case "rulenotfound":
                                $return_json["iserror"] = true;
                                array_push( $return_json["error"], array(
                                                                       "type" => "system",
                                                                       "msg" => $app_strings["LBL_ERROR_UNIQUE_CHECK_NORULES"],
                                                                   ));
                                break;
                            case "missingfields":
                                $return_json["iserror"] = true;
                                array_push( $return_json["error"], array(
                                                                       "type" => "system",
                                                                       "msg" => $app_strings["LBL_ERROR_UNIQUE_CHECK_MISSING"],
                                                                   ));
                                break;
                            case "duplicated":
                                $return_json["iserror"] = true;
                                array_push( $return_json["error"], array(
                                                                       "type" => "field",
                                                                       "msg" => $result["msgError"],
                                                                       "field" => $rule->field,
                                                                   ));
                                break;
                        }
                    }
                }
            }
        } else {
            $return_json["iserror"] = true;
            $return_json["error"] = 
            array(
                "type" => "system",
                "msg" => $app_strings["LBL_ERROR_UNIQUE_CHECK_NORULES"],
            );
        }
        echo json_encode($return_json);
    }

    public function action_getValidationRules(){
        global $app_strings, $mod_strings;

        if($_REQUEST['field'] && $_REQUEST['id'] && $_REQUEST['current_module'] ){

            $bean = BeanFactory::getBean($_REQUEST['current_module'],$_REQUEST['id']);

            if(is_object($bean) && $bean->id != ""){

                $fielddef = $bean->field_defs[$_REQUEST['field']];

                if (!isset($fielddef['required']) || !$fielddef['required']) {
                    $fielddef['required'] = false;
                }

                if ($fielddef['name'] == "email1" || (isset($fielddef['email2']) && $fielddef['email2'])) {
                    $fielddef['type'] = "email";
                    $fielddef['vname'] = "LBL_EMAIL_ADDRESSES";
                }

                if (isset($app_strings[$fielddef['vname']])) {
                    $fielddef['label'] = $app_strings[$fielddef['vname']];
                }else{
                    if (isset($mod_strings[$fielddef['vname']])) {$fielddef['label'] = $mod_strings[$fielddef['vname']];} else {
                        if(!isset($fielddef['label'])) {
                            $fielddef['label'] = null;
                        }
                    }
                }
                switch ($fielddef['type']){
                    case 'timeslot':
                        $validate_array = 
                        array( "rules" => 
                            array( 
                                array(
                                    'validation' => 'default',
                                    'type' => $fielddef['type'], 
                                    'required' => $fielddef['required'],
                                    'label' => $fielddef['label']
                                ),
                                array( 
                                    'validation' => 'composefield', 
                                    'type' => $fielddef['type'], 
                                    'required' => $fielddef['required'],
                                    'label' => $fielddef['label'],
                                    'field' => "val_".$fielddef['name']
                                ),
                                array( 
                                    'validation' => 'addtocomposefield', 
                                    'type' => $fielddef['type'], 
                                    'required' => true,
                                    'label' => $app_strings["LBL_HOURS"],
                                    'field' => $fielddef['name']."_hours",
                                    'parent' => "val_".$fielddef['name']
                                ),
                                array( 
                                    'validation' => 'addtocomposefield', 
                                    'type' => $fielddef['type'], 
                                    'required' => true,
                                    'label' => $app_strings["LBL_MINUTES"],
                                    'field' => $fielddef['name']."_minutes",
                                    'parent' => "val_".$fielddef['name']
                                ),
                            )
                        );
                        break;
                    default:
                        $validate_array =
                        array( "rules" =>
                            array(
                                array(
                                    'validation' => 'default',
                                    'type' => $fielddef['type'], 
                                    'required' => $fielddef['required'],
                                    'label' => $fielddef['label']
                                ),
                            )
                        );
                        break;
                }
                if (isset($fielddef['validation']['type'])){
                    switch ($fielddef['validation']['type']){
                        case 'callback':
                            $newRule = 
                            array(
                                'validation' => $fielddef['validation']['type'],
                                'type' => $fielddef['type'],
                                'required' => $fielddef['required'],
                                'function' => $fielddef['validation']['callback'],
                                'label' => $fielddef['label']
                            ); 
                            array_push( $validate_array['rules'], $newRule );
                            break;
                        default:
                            break;
                    }
                }
                if ($bean->has_duplicate_check) {
                    $rules = $bean->duplicate_check->isFieldOfDuplicateCheckRule($_REQUEST['field']);
                    foreach( $rules as $rule ){
                        $tmpRule = $rule->getRuleInformation();
                        $newRule = 
                        array(
                            'validation' => 'duplicate_check',
                            'name' => $tmpRule['nameRule'],
                            'fields' => $tmpRule['fields'],
                            'errorMessages' => $tmpRule['errorMessages'],
                            'label' => $fielddef['label']
                        ); 
                        array_push( $validate_array['rules'], $newRule );
                    }
                }
                echo json_encode($validate_array);
            }

        }

    }
    
    public function action_getRelateFieldJS(){
        
        global $beanFiles, $beanList;
        
        $fieldlist = array();
        $view = "EditView";

        if (!isset($focus) || !($focus instanceof SugarBean)){
            require_once($beanFiles[$beanList[$_REQUEST['current_module']]]);
            $focus = new $beanList[$_REQUEST['current_module']];
        }

        // create the dropdowns for the parent type fields
        $vardefFields[$_REQUEST['field']] = $focus->field_defs[$_REQUEST['field']];

        require_once("include/TemplateHandler/TemplateHandler.php");
        $template_handler = new TemplateHandler();
        $quicksearch_js = $template_handler->createQuickSearchCode($vardefFields, $vardefFields, $view);
        $quicksearch_js = str_replace($_REQUEST['field'], $_REQUEST['field'] . '_display', $quicksearch_js);

        if($_REQUEST['field'] != "parent_name") {
            $quicksearch_js = str_replace($vardefFields[$_REQUEST['field']]['id_name'], $_REQUEST['field'], $quicksearch_js);
        }

        echo $quicksearch_js;

    }

}
