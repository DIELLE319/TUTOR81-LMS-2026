<?php
/**
 * Plugin Name: Tutor81 Plugin
 * Plugin URI: https://www.tutor81.com
 * Description: A plugin to extend Woocommerce with Tutor81 API.
 * Author:  RZWeb
 * Author URI: 
 * Version: 1.0
 */
if ( ! class_exists( 'WC_tutor81' ) ) {
    class WC_tutor81 {
        
        private $apiKey = '41d0517a20a63b4b4d62768172958b2d4cf701af55f92f54a716a';
        private $url_remote_api = 'https://amm.tutor81.com/api/v1/';
        /**
        * Construct the plugin.
        */
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }
        /**
        * Initialize the plugin.
        */
        public function init() {
            // Checks if WooCommerce is installed.
            if ( class_exists( 'WC_Integration' ) ) {
                // Include our integration class.
                include_once 'includes/class-wc-integration-tutor81-integration.php';
                // Register the integration.
                add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
            
                // Set the plugin slug
                define( 'TUTOR81_SLUG', 'wc-settings' );
                // Setting action for plugin
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_tutor81_action_links' );
            }

        }
        /**
        * Add a new integration to WooCommerce.
        */
        public function add_integration( $integrations ) {
            $integrations[] = 'WC_Tutor81_Integration';
            return $integrations;
        }
        
        private function CallAPI($method, $url = '', $data = array()) {
            $url = (WC_Admin_Settings::get_option('tutor81_api_uri') ? : $this->url_remote_api) . $url;
            $data['apiKey'] = WC_Admin_Settings::get_option('tutor81_api_key') ? : $this->apiKey;
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

            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Origin: https://' . $_SERVER['SERVER_NAME']));
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
        private function testT81API($method){
            return $this->CallAPI($method,'test');
        }
        
        public function ecommercePurchase($order_id){
            //create an order instance
            $order_obj = wc_get_order($order_id);
            if ($order_obj->has_status( 'failed' )) {
                return false;
            } else {
                $order_data = $order_obj->get_data();
                // $company array
                $company['vat_code']        = get_post_meta($order_id,'billing_iva', true);
                $company['business_name']   = $order_data['billing']['company'];
                $company['address']         = $order_data['billing']['address_1'];
                $company['postal_code']     = $order_data['billing']['postcode'];
                $company['city']            = $order_data['billing']['city'];
                $company['province']        = $order_data['billing']['state'];
                $company['telephone']       = $order_data['billing']['phone'];
                $company['email']           = $order_data['billing']['email'];
                // $orders array
                $order = array();
                $order['id'] = $order_id;
                $order['completed'] = $order_obj->has_status( 'processing' ) || $order_obj->has_status( 'completed' );
                $order['items'] = array();
                foreach ($order_obj->get_items() as $item_key => $item ){
                    $item_data      = $item->get_data();
                    $item_product        = $item->get_product();
                    $item_learning_project_id = $item_product->get_sku();
                    $item_quantity  = $item_data['quantity'];
                    $item_price     = $item_data['total'];
                    array_push($order['items'], array(
                        'learning_project_id'   => $item_learning_project_id,
                        'qta'                   => $item_quantity,
                        'price'                 => $item_price/$item_quantity
                    ));

                }
                
                return $this->CallApi('POST', 'elearning/purchase', 
                        array( 'company' => json_encode($company), 
                            'order' => json_encode($order)));
            }
        }
    }
    
    $WC_tutor81 = new WC_tutor81( __FILE__ );
    
    function WC_tutor81_action_links( $links ) {
        $links[] = '<a href="'. menu_page_url( TUTOR18_SLUG, false ) .'&tab=integration">Settings</a>';
        return $links;
    }
    
    add_action('woocommerce_thankyou', array($WC_tutor81, 'ecommercePurchase'));
    
}