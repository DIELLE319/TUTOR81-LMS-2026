<?php
/**
 * Integration Tutor81.
 *
 * @package  Woocommerce Plugin Tutor81
 * @category Integration
 * @author   RZWeb
 */
if ( ! class_exists( 'WC_Tutor81_Integration' ) ) {
    class WC_Tutor81_Integration extends WC_Integration {
        /**
        * Init and hook in the integration.
        */
        public function __construct() {
            global $woocommerce;
            $this->id                   = 'tutor81-integration';
            $this->method_title         = __( 'Tutor81 Integration', 'woocommerce-tutor81-integration');
            $this->method_description   = __( 'Tutor81 Integration to extend WooCommerce.', 'woocommerce-tutor81-integration');
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
            // Define user set variables.
            $this->tutor81_api_key          = $this->get_option( 'tutor81_api_key' );
            $this->tutor81_api_uri          = $this->get_option( 'tutor81_api_uri' );
            // Actions.
            add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
        }
        /**
        * Initialize integration settings form fields.
        */
        public function init_form_fields() {
            $this->form_fields = array(
                'tutor81_api_uri' => array(
                    'title'         => __( 'Tutor81 API URL', 'woocommerce-tutor81-integration'),
                    'type'          => 'text',
                    'description'   => __( 'Inserisci l\'url della piattaforma Tutor81'),
                    'desc_tip'      => true,
                    'default'       => '',
                    'css'           => 'width:250px;',
                ),
                'tutor81_api_key' => array(
                    'title'         => __( 'Tutor81 API Key', 'woocommerce-tutor81-integration'),
                    'type'          => 'text',
                    'description'   => __( 'Inserisci le chiavi crittografate fornite da Tutor81'),
                    'desc_tip'      => true,
                    'default'       => '',
                    'css'           => 'width:400px;',
                ),
            );
        }
        
        
	/**
	 * Santize our settings
	 * @see process_admin_options()
	 */
	public function sanitize_settings( $settings ) {
		// We're just going to make the api key all lower case characters
		if ( isset( $settings ) &&
		     isset( $settings['tutor81_api_key'] ) ) {
			$settings['tutor81_api_key'] = strtolower( $settings['tutor81_api_key'] );
		}
		return $settings;
	}

	/**
	 * Validate the API key
	 * @see validate_settings_fields()
	 */
	public function validate_api_key_field( $key ) {
		// get the posted value
		$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];

		// check if the API key is longer 53 characters
		if ( isset( $value ) &&
			 53 != strlen( $value ) ) {
			$this->errors[] = $key;
		}
		return $value;
	}


	/**
	 * Display errors by overriding the display_errors() method
	 * @see display_errors()
	 */
	public function display_errors( ) {

		// loop through each error and display it
		foreach ( $this->errors as $key => $value ) {
			?>
			<div class="error">
				<p><?php _e( 'Il valore del campo ' . $value . ' non è corretto.', 'woocommerce-tutor81-integration' ); ?></p>
			</div>
			<?php
		}
	}


    }
}