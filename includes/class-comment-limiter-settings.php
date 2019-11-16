<?php
/**
 * If accessed directly, then exit
 */
defined( 'ABSPATH' ) || exit;


/**
 * If class do not exists, then create it
 */
if ( ! class_exists( 'Comment_Limiter_Settings' ) ) {

    /**
     * Class that holds Comment Limiter settings
     */
    class Comment_Limiter_Settings
    {
        /**
         * Property instance
         *
         * @since 1.0
         * @var object
         */
        private static $_instance;

        /**
         * Handles get_option values
         *
         * @since 1.0
         * @var array
         */
        private $_comment_limiter_options;

        /**
         * Handles global configuration class
         *
         * @since 1.3
         * @var array
         */
        private $_config;

        /**
         * handles default values
         *
         * @since 1.0
         * @var array
         */
        public $defaults = array();

        /**
         * Constructor
         *
         * @since 1.0
         */
        public function __construct() {
            // ...
        }

        /**
         * Setup action and filter hooks
         *
         * @since 1.0
         * @return void
         */
        public function setup() {

            add_action( 'admin_init',            array( $this, 'comment_limiter_page_init' ) );
            add_action( 'admin_menu',            array( $this, 'comment_limiter_add_submenu_page' ) );
            add_filter( 'preprocess_comment',    array( $this, 'comment_limiter_checker' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );

            $this->_comment_limiter_options = get_option( 'comment_limiter_settings' );
            $this->_config = Comment_Limiter_Config::factory()->get();
        }

        /**
         * Enqueue style
         *
         * @since 1.0
         * @return void
         */
        public function admin_enqueue_styles() {
            if ( ! empty( $_GET['page'] ) && 'comment-limiter' == $_GET['page'] ) {
                wp_enqueue_style( 'cl-settings-css', plugins_url( '/assets/css/settings.css', dirname( __FILE__ ) ), array(), CL_VERSION, 'all' );
            }
        }

        /**
         * Add submenu page in the dashboard
         *
         * @since 1.0
         * @return string
         */
        public function comment_limiter_add_submenu_page() {

            add_submenu_page(
                'edit-comments.php',
                __( 'Comment Limiter', 'comment-limiter' ),
                __( 'Comment Limiter', 'comment-limiter' ),
                'manage_options',
                'comment-limiter',
                array( $this, 'comment_limiter_admin_page' )
            );
        }

        /**
         * Setup HTML form
         *
         * @since 1.0
         * @return void
         */
        public function comment_limiter_admin_page() {

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            if ( isset( $_GET['settings-updated'] ) ) {
                // add settings saved message with the class of "updated"
                // add_settings_error( 'cl_messages', 'cl_message', __( 'Comment Limiter settings saved correctly.', 'comment-limiter' ), 'updated' );
            }
            ?>
            <div class="wrap" id="comment-limiter">
                <h2><?php esc_html_e( 'Comment Limiter', 'comment-limiter' ); ?></h2>
                <!-- <p>This is a text that appears on the options page.</p> -->
                    <?php settings_errors( 'comment-limiter-messages' ); ?>

                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'comment_limiter_group' );
                    do_settings_sections( 'comment-limiter-admin' );
                    submit_button( esc_html__( 'Save Comments Changes', 'comment-limiter' ), 'primary', '' );
                    ?>
                </form>
            </div>
            <?php
        }

        /**
         * Register settings
         *
         * @since 1.0
         * @return void
         */
        public function comment_limiter_page_init() {

            register_setting(
                'comment_limiter_group',
                'comment_limiter_settings',
                array( $this, 'comment_limiter_sanitize' )
            );

            add_settings_section(
                'comment_limiter_section',
                __( '', 'comment-limiter' ),
                array( $this, 'comment_limiter_section_info' ),
                'comment-limiter-admin'
            );

            add_settings_field(
                'maximum_characters',
                __( 'Maximum Characters Number', 'comment-limiter' ),
                array( $this, 'maximum_characters_callback' ),
                'comment-limiter-admin',
                'comment_limiter_section',
                array(
                    'label_for' => 'maximum_characters',
                    'class'     => 'maximum_characters',
                )
            );

            add_settings_field(
                'maximum_message',
                __( 'Maximum Message Error', 'comment-limiter' ),
                array( $this, 'maximum_message_callback' ),
                'comment-limiter-admin',
                'comment_limiter_section',
                array(
                    'label_for' => 'maximum_message',
                    'class'     => 'maximum_message',
                )
            );

            add_settings_field(
                'minimum_characters',
                __( 'Minimum Characters Number', 'comment-limiter' ),
                array( $this, 'minimum_characters_callback' ),
                'comment-limiter-admin',
                'comment_limiter_section',
                array(
                    'label_for' => 'minimum_characters',
                    'class'     => 'minimum_characters',
                )
            );

            add_settings_field(
                'minimum_message',
                __( 'MInimum Message Error', 'comment-limiter' ),
                array( $this, 'minimum_message_callback' ),
                'comment-limiter-admin',
                'comment_limiter_section',
                array(
                    'label_for' => 'minimum_message',
                    'class'     => 'minimum_message',
                )
            );

            add_settings_field(
                'enable_admin_feature',
                __( 'Apply Settings to Admins', 'comment-limiter' ),
                array( $this, 'comment_limiter_dropdown' ),
                'comment-limiter-admin',
                'comment_limiter_section' ,
                array(
                    'label_for' => 'enable_admin_feature',
                    'class'     => 'enable_admin_feature',
                )
            );

        }

        /**
         * Sanitize and validate fields
         *
         * @since 1.0
         * @param  array
         * @return array
         */
        public function comment_limiter_sanitize( $input ) {

            $output = $this->_comment_limiter_options;

            if ( $input['maximum_characters'] <= $input['minimum_characters'] ) {
                add_settings_error( 'comment-limiter-messages', 'invalid-values', esc_html__( 'Invalid lengths. Please insert logical values.', 'comment-limiter' ) );

                return $output;
            }

            if ( empty( $input['maximum_message'] ) ) {
                add_settings_error( 'comment-limiter-messages', 'empty-values', esc_html__( 'Maximum message error', 'comment-limiter' ) );

                return $output;
            } else {
                $output['maximum_message'] = sanitize_text_field( esc_html__( $input['maximum_message'], 'comment-limiter' ) );
            }

            if ( empty( $input['minimum_message'] ) ) {
                add_settings_error( 'comment-limiter-messages', 'empty-values', esc_html__( 'Minimum message error', 'comment-limiter' ) );

                return $output;
            } else {
                $output['minimum_message'] = sanitize_text_field( esc_html__( $input['minimum_message'], 'comment-limiter' ) );
            }

            if ( isset( $input['maximum_characters'] ) ) {
                $output['maximum_characters'] = sanitize_text_field( absint( $input['maximum_characters'] ) );
            }

            if ( isset( $input['minimum_characters'] ) ) {
                $output['minimum_characters'] = sanitize_text_field( absint( $input['minimum_characters'] ) );
            }

            if ( isset( $input['enable_admin_feature'] ) ) {
                $output['enable_admin_feature'] = sanitize_text_field( $input['enable_admin_feature'] );
            }
            add_settings_error( 'comment-limiter-messages', 'success-message', esc_html__( 'Comment Limiter settings saved correctly.', 'comment-limiter' ), 'updated' );

            return $output;
        }

        /**
         * Sectiton description
         *
         * @since 1.0
         * @return void
         */
        public function comment_limiter_section_info() {
            // ...
        }

        /**
         * Check comment length
         *
         * @since 1.0
         * @param  array
         * @return array
         */
        public function comment_limiter_checker( $commentdata ) {

            if ( current_user_can( 'manage_options' ) && $this->_comment_limiter_options['enable_admin_feature'] === 'no' ) {
                return $commentdata;
            }

            // If comment is long, then throw an error message
            if ( strlen( $commentdata['comment_content'] ) > $this->_comment_limiter_options['maximum_characters'] ) {
                wp_die(
                    sprintf( esc_html__( $this->_comment_limiter_options['maximum_message'], 'comment-limiter' ), ( strlen( $commentdata['comment_content'] ) - $this->_comment_limiter_options['maximum_characters'] ) ),
                    __( 'Comment Limiter Error', 'comment-limiter' ),
                    array(
                        'back_link' => true,
                    )
                );
            }

            // If comment is short, then throw an error message
            if ( strlen( $commentdata['comment_content'] ) < $this->_comment_limiter_options['minimum_characters'] ) {

                wp_die(
                    sprintf( esc_html__( $this->_comment_limiter_options['minimum_message'], 'comment-limiter' ), ( $this->_comment_limiter_options['minimum_characters'] - strlen($commentdata['comment_content']) ) ),
                    __( 'Comment Limiter Error', 'comment-limiter' ),
                    array(
                        'back_link' => true,
                    )
                );
            }

            return $commentdata;
        }

        /**
         * Setup maximum characters field
         *
         * @since 1.0
         * @return
         */
        public function maximum_characters_callback() {

            ?>
            <input type="number" name="comment_limiter_settings[maximum_characters]" id="maximum_characters" class="regular-text" value="<?php esc_html_e( $this->_config['maximum_characters'], 'comment-limiter' ); ?>" />
            <span class="description"><?php esc_html_e( 'Accepts only numbers', 'comment-limiter' ); ?></span>
            <?php
        }

        /**
         * Setup maximum message error
         *
         * @since 1.3
         * @return
         */
        public function maximum_message_callback() {

            ?>
            <textarea class="maximum_message" name="comment_limiter_settings[maximum_message]" id="maximum_message"><?php esc_html_e( $this->_config['maximum_message'], 'comment-limiter' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'This is the error message that will be displayed to the user when the comment length is more than the maximum value indicated above.', 'comment-limiter' ); ?></p>
            <?php
        }

        /**
         * Setup minimum characters field
         *
         * @since 1.0
         * @return
         */
        public function minimum_characters_callback() {

            ?>
            <input type="number" name="comment_limiter_settings[minimum_characters]" id="minimum_characters" class="regular-text" value="<?php esc_html_e( $this->_config['minimum_characters'], 'comment-limiter' ); ?>" />
            <span class="description"><?php esc_html_e( 'Accepts only numbers', 'comment-limiter' ); ?></span>
            <?php
        }

        /**
         * Setup minimum message error
         *
         * @since 1.3
         * @return
         */
        public function minimum_message_callback() {

            ?>
            <textarea class="minimum_message" name="comment_limiter_settings[minimum_message]" id="minimum_message"><?php esc_html_e( $this->_config['minimum_message'], 'comment-limiter' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'This is the error message that will be displayed to the user when the comment length is less than the minimum value indicated above.', 'comment-limiter' ); ?></p>
            <?php
        }

        /**
         * Setup dropdown field
         *
         * @since 1.0
         * @return
         */
        public function comment_limiter_dropdown() {

            ?>
            <select name='comment_limiter_settings[enable_admin_feature]' id="enable_admin_feature">
                <option value="no" <?php echo isset( $this->_config['enable_admin_feature'] ) ? ( selected( $this->_config['enable_admin_feature'], 'no' ) ) : ''; ?>><?php esc_html_e( 'No', 'comment-limiter' ); ?></option>
                <option value="yes" <?php echo isset( $this->_config['enable_admin_feature'] ) ? ( selected( $this->_config[ 'enable_admin_feature' ], 'yes' ) ) : ''; ?>><?php esc_html_e( 'Yes', 'comment-limiter' ); ?></option>
            </select>
            <p class="description"><?php esc_html_e( 'This will allows users with administrator capabilities to publish comments despite the configuration of Comment Limiter plugin.', 'comment-limiter' ); ?></p>
            <?php
        }

        /**
         * Instance class object
         *
         * @since 1.0
         * @return object
         */
        public static function factory() {

            if ( ! self::$_instance ) {
                self::$_instance = new self();
                self::$_instance->setup();
            }

            return self::$_instance;
        }
    }

}
