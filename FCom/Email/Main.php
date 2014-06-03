<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Email_Main extends BClass
{
    public function bootstrap()
    {
        BEmail::i()->addHandler('FCom_Email', 'FCom_Email_Main::handler');

        $c = $this->BConfig->get('modules/FCom_Email');
        if (!empty($c['smtp_host'])) {
            ini_set('SMTP', $c['smtp_host']);
        }
        if (!empty($c['sendmail_from'])) {
            ini_set('sendmail_from', $c['sendmail_from']);
        }

        if (!empty($c['default_handler'])) {
            BEmail::i()->setDefaultHandler($c['default_handler']);
        }

        $this->FCom_Admin_Model_Role->createPermission([
            'subscriptions' => 'Email Subscriptions',
        ]);
    }

    public function onEmailSendBefore($args)
    {
        $email = $args['email_data']['to'];
        $pref = $this->FCom_Email_Model_Pref->load($email, 'email');
        return $pref && $pref->unsub_all ? false : true;
    }

    public function handler($data)
    {
        $msg = $this->FCom_Email_Model_Message->create([
            'recipient' => $data['to'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'data' => $this->BUtil->arrayMask($data, 'headers,params,files,orig_data'),
            'status' => 'sending',
        ])->save();

        $this->BDebug->startErrorLogger();
        $result = BEmail::i()->defaultHandler($data);
        $errors = $this->BDebug->stopErrorLogger();

        if ($result) {
            $msg->set([
                'status' => 'success',
            ])->save();
            return true;
        } else if ($errors) {
            $msg->set([
                'status' => 'error',
                'error_message' => $errors[0]['message'],
                'num_attempts' => $msg->num_attempts + 1,
            ])->save();
            return false;
        }
    }
}
