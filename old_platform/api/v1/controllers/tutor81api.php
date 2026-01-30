<?php
class tutor81api {
    
    private static $api_key = '41d0517a20a63b4b4d62768172958b2d4cf701af55f92f54a716a'; //inserisci qui la chiave per il tuo host
    private static $url_remote_api = 'http://amm.tutor81.com/api/v1/';
    
    public function __construct() {
    }
    
    private static function CallAPI($method, $url = '', $data = array())
    {
        $url = self::$url_remote_api . $url;
        $data['apiKey'] = self::$api_key;
        $curl = curl_init();

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Origin: http://' . $_SERVER['SERVER_NAME']));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_VERBOSE, true);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    /**
     * 
     * @param type $method ('GET' or 'POST')
     * @return type
     */
    public static function testT81API($method){
        return self::CallAPI($method,'test');
    }

    public static function createT81Demo($surname,$email){
        return self::CallAPI('POST','demo/new',array('surname' => $surname,'email' => $email));
    }

    public static function getCourseTypeBySubcategories($subcategory_id){
        return self::CallAPI('GET', "coursetype/bySubcategory/$subcategory_id");
    }

    public static function getCourseTypeDetail($course_type_id){
        return self::CallAPI('GET', "coursetype/$course_type_id");
    }

    public static function getClassroomByCourseType($course_type_id){
        return self::CallAPI('GET',"classroom/byCourseType/$course_type_id");   
    }

    public static function getElearningByCourseType($course_type_id){
        return self::CallAPI('GET',"elearning/byCourseType/$course_type_id");   
    }

    public static function reserveClassroom($classroom_scheduled_id, $booked_places, $customer_name, $customer_email, $customer_phone){
        return self::CallAPI('POST', 'classroom/reserve', array('classroom_scheduled_id' => $classroom_scheduled_id,
                                                          'booked_places' => $booked_places,
                                                          'customer_name' => $customer_name,
                                                          'customer_email' => $customer_email,
                                                          'customer_phone' => $customer_phone,));
    }
    
    /**
     * 
     * @param json $company = '{
     *                  "vat_code"      : string partita iva,
     *                  "business_name" : string ragione sociale,
     *                  "address"       : string indirizzo,
     *                  "postal_code"   : string CAP,
     *                  "city"          : string città,
     *                  "province"      : string provincia,
     *                  "telephone"     : string telefono,
     *                  "email"         : string email      
     *              }'
     * @param json $order = '[
     *                  "id" : int id dell'ordine,
     *                  "completed" : boolean in base allo stato dell'ordine (true if completed or processing),
     *                  items : {
     *                          "learning_project_id" : int id del learning project,
     *                          "qta"                 : int quantità acquistata,
     *                          "price"               : float prezzo unitario senza iva,
     *                          },
     *                          { ... }
     *                  }
     *              ]'
     * @return text
     */
    public static function ecommercePurchase($company, $orders){
        return self::CallApi('POST', 'elearning/purchase', array( 'company' => $company, 'order' => $order));
    }
}