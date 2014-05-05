<?php

class FCom_LibRecaptcha_Main extends BClass
{
    static public function html( $error = null )
    {
        require_once __DIR__ . '/recaptchalib.php';
        $publicKey = BConfig::i()->get( 'modules/FCom_LibRecaptcha/public_key' );
        return recaptcha_get_html( $publicKey, $error );
    }

    static public function check()
    {
        $r = BRequest::i();
        if ( $r->post( 'recaptcha_response_field' ) ) {
            require_once __DIR__ . '/recaptchalib.php';
            $privateKey = BConfig::i()->get( 'modules/FCom_LibRecaptcha/private_key' );
            $resp = recaptcha_check_answer( $privateKey, $r->ip(), $r->post( 'recaptcha_challenge_field' ), $r->post( 'recaptcha_response_field' ) );
            if ( $resp->is_valid ) {
                return true;
            } else {
                return $resp->error;
            }
        }
        return false;
    }
}