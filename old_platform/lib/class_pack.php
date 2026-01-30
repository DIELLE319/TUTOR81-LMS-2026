<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 28-set-2015
 * File: lib/class_pack.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'class_db.php';

class T81Pack {
    
    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }
    
    public function getPackTypes(){
        return $this->db_conn->query("SELECT * FROM pack_types WHERE deleted = 0");
    }
    
    /**
     * Restituisce la lista dei pacchetti acquistati non scaduti e non vuoti
     * 
     * @param integer $tutor_id
     * @return array
     */
    public function getCurrentPackPurchased($tutor_id){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT * 
                  FROM pack_purchases 
                    JOIN pack_types ON pack_purchases.pack_type_id = pack_types.id_pack_type
                  WHERE pack_purchases.tutor_id = '$tutor_id'
                    AND pack_purchases.content_available > 0
                    AND pack_purchases.expiration_date >= CURDATE()
                  ORDER BY pack_purchases.purchase_date";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : FALSE;
    }
    
    /**
     * acquista un pacchetto di corsi
     * 
     * @param intger $pack_type_id
     * @param integer $tutor_id
     * @param integer $qty_purchased
     * @return boolean /integer
     */
    public function purchasePack($pack_type_id, $tutor_id, $qty_purchased){
        $pack_type_id = filter_var($pack_type_id, FILTER_SANITIZE_NUMBER_INT);
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $qty_purchased = filter_var($qty_purchased, FILTER_SANITIZE_NUMBER_INT);
        $pack_type = $this->db_conn->query("SELECT * FROM pack_types WHERE id_pack_type = '$pack_type_id'");
        if ($pack_type[0]) {
            $content_available = $qty_purchased*$pack_type[0]['content_amount'];
            $query = "INSERT INTO pack_purchases (pack_type_id, tutor_id, qty_purchased, content_available, expiration_date)
                      VALUES ('$pack_type_id', '$tutor_id', '$qty_purchased', '$content_available', DATE_ADD(CURDATE(), INTERVAL 1 YEAR))";
            $res = $this->db_conn->insert($query);
            return $res ? : false;
        } else {
            return false;
        }
    }
    
    /**
     * calcola i corsi acquistabili in base al learning_project_id e ai pacchetti acquistati
     * 
     * @param integer $tutor_id
     * @param integer $learning_project_id
     * @return integer
     */
    public function calcElearningPacked($tutor_id, $learning_project_id){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $course = $this->getElearningPriceDuration($learning_project_id);
        if (!$course || $course['price'] == 0) return 0;
        $query = "SELECT pack_types.content_type, 
                    SUM(pack_purchases.content_available) as available
                  FROM pack_purchases 
                    JOIN pack_types ON pack_purchases.pack_type_id = pack_types.id_pack_type
                  WHERE pack_purchases.tutor_id = '$tutor_id'
                    AND pack_purchases.content_available > 0
                    AND pack_purchases.expiration_date >= CURDATE()
                  GROUP BY pack_types.content_type";
        $available = $this->db_conn->query($query);
        $elearnig_packed = 0;
        if (!isset($available[0])) return 0;
        foreach ($available as $purchase) {
            if ($purchase['content_type'] === 'COURSES') $elearnig_packed += $purchase['available'];
            elseif ($purchase['content_type'] === 'HOURS') $elearnig_packed += $purchase['available']/((integer) $course['duration']);
            elseif ($purchase['content_type'] === 'MONEY') $elearnig_packed += floor($purchase['available']/$course['price']);
        }
        return $elearnig_packed;        
    }
    
    public function getElearningPriceDuration($learning_project_id) {
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT IFNULL(course_price_range_sequences.price, 0) as price,
                    courses.total_elearning as duration
                  FROM unities 
                    JOIN courses ON unities.course_id = courses.id
                    LEFT JOIN course_price_range_sequences ON unities.course_id = course_price_range_sequences.course_id
                    LEFT JOIN ranges ON course_price_range_sequences.range_id = ranges.id 
                    LEFT JOIN price_range_sequences ON ranges.price_range_sequence_id = price_range_sequences.id
                  WHERE unities.learning_project_id = '$learning_project_id' 
                  ORDER BY lower_limit";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    public function setContentAvialablePackPurchased($id_pack_purchase, $content_available){
        $id_pack_purchase = filter_var($id_pack_purchase, FILTER_SANITIZE_NUMBER_INT);
        $content_available = filter_var($content_available, FILTER_SANITIZE_NUMBER_INT);
        $content_available = $content_available > 0 ? $content_available : 0;
        $query = "UPDATE pack_purchases
                  SET content_available = $content_available 
                  WHERE id_pack_purchase = $id_pack_purchase";
        return $this->db_conn->update($query) ? : false;
    }
}