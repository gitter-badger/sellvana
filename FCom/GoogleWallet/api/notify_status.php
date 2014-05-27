<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Copyright 2013 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 *
 * @author Nasir Khan <nasir@google.com>
 * @version 1.0
 */

require_once 'util.php';

class NotifyStatus {

  public static function post($input) {
    WalletUtil::assert_input($input, ['jwt']);
    $full_jwt = JWT::decode($input['jwt'], null, FALSE);
    $full_response = $full_jwt['response'];
    $now = (int)date('U');

    $data = [
      'iat' => $now,
      'exp' => $now + 3600,
      'typ' => 'google/wallet/online/transactionstatus/v2/request',
      'aud' => 'Google',
      'iss' => MERCHANT_ID,
      'request' => [
        'merchantName' => MERCHANT_NAME,
        'googleTransactionId' => $full_response['googleTransactionId'],
        'status' => 'SUCCESS'
      ],
    ];
    WalletUtil::encode_send_jwt($data);
  }
}
