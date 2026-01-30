<?php
require_once 'class_db.php';

class FunctionalFeature{

    var $db_conn;

    public function __construct(){
	$this->db_conn = new MySQLConn();
    }
    
    public function addFunctionalFeature($ff_type, $ff_id,$unit_type, $unit_id){
        $ff_type = $this->db_conn->escapestr($ff_type);
        $ff_id = filter_var($ff_id, FILTER_SANITIZE_NUMBER_INT);
        $unit_type = $this->db_conn->escapestr($unit_type);
        $unit_id = filter_var($unit_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "INSERT INTO functional_features(ff_type, ff_id, unit_type, unit_id) "
                . "VALUES ('$ff_type',$ff_id,'$unit_type',$unit_id)";
        return $this->db_conn->insert($query) ? : false;
    }
    
    public function editFuncionalFeature($id, $ff_type, $ff_id,$unit_type, $unit_id){
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $ff_type = $this->db_conn->escapestr($ff_type);
        $ff_id = filter_var($ff_id, FILTER_SANITIZE_NUMBER_INT);
        $unit_type = $this->db_conn->escapestr($unit_type);
        $unit_id = filter_var($unit_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "UPDATE functional_features "
                . "SET ff_type = '$ff_type', ff_id = $ff_id, unit_type = '$unit_type', unit_id = $unit_id "
                . "WHERE id = $id";
        return $this->db_conn->update($query) ? : false;
    }
    
    public function getFunctionalFeaturesByUnit($unit_type, $unit_id){
        $unit_type = $this->db_conn->escapestr($unit_type);
        $unit_id = filter_var($unit_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT * FROM functional_features WHERE unit_type = '$unit_type' AND unit_id = $unit_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    

    /*
    public function getFuntionalFeaturesArrayByUnit($unit_type, $unit_id){
        $ff_list = $this->getFunctionalFeaturesByUnit($unit_type, $unit_id);
        if ($ff_list){
            $ff = array();
            foreach ($res as $functional_feature){
                switch ($functional_feature['ff_type']){
                    case 'ateco_sectors':
                        $query = "SELECT ateco_sectors.id as ateco_sector_id,"
                            . "ateco_sectors.name as ateco_sector,"
                            . "ateco_risks.id as ateco_risk_id,"
                            . "ateco_risks.short_desc_ateco_risk as ateco_risk"
                            . "FROM ateco_risks "
                            . "JOIN ateco_sectors ON ateco_risks.id = ateco_sectors.ateco_risk_id "
                            . "WHERE ateco_sectors.id = {$functional_feature['ff_id']}";
                        $ateco = $this->db_conn->query($query);
                        $ff['ateco'] = $ateco[0];
                        break;
                    case 'custom_categories':
                        $query = "SELECT * FROM custom_categories WHERE lev_3 = {$functional_feature['ff_id']}";
                        $cc = $this->db_conn->query($query);
                        $query = "SELECT * FROM custom_categories WHERE lev_1 = {$cc[0]['lev_1']} AND lev_2 = 0 AND lev_3 = 0";
                        $cc_lev_1 = $this->db_conn->query($query);
                        $ff[$cc_lev1[0]['definition']] = $cc[0];
                        break;
                }
            }
            return $ff;
        } else {
            return false;
        }
    }
     *
     */

}