<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Comment_Limiter_Deactivator' ) ) {

    class Comment_Limiter_Deactivator
    {
        private static $_instance;

        public function __construct() {

            $this->setup();
        }

        public function setup() {

            $this->deactivate();
        }

        /**
         * Short Description. (use period)
         *
         * Long Description.
         *
         * @since    1.0.0
         */
        public function deactivate() {

            register_deactivation_hook( __FILE__, array( $this, 'deactivate_comment_limiter' ) );
        }

        public function deactivate_comment_limiter() {
            
            unregister_setting(
                'comment_limiter_group',
                'comment_limiter_settings'
            );
        }

        public static function factory() {
            
            if ( ! self::$_instance ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
    }

}