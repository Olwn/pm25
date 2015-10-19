<?php

namespace app\modules\restful\controllers;
use Yii;

class UserTakeinController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\UserTakein';
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actions()
    {
        return parent::actions();

    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        return $this->encryptDataBeforeAction();
        // your custom code here
        //return true; // or false to not run the action
    }

    private function encryptDataBeforeAction()
    {
        $request = Yii::$app->getRequest();
        if ($request->getMethod() != "POST")
        {
            return true;
        }
        $bodyParams = $request->getBodyParams();
        if (isset($bodyParams['value']))
        {
            $value = $bodyParams['value'];
            $bodyParams['value'] = $this->encrypt($value);
            Yii::$app->getRequest()->setBodyParams($bodyParams);
        }
        return true;
    }

    private function encrypt($plaintext)
    {
        # --- ENCRYPTION ---

        # the key should be random binary, use scrypt, bcrypt or PBKDF2 to
        # convert a string into a key
        # key is specified using hexadecimal
        $key = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
        
        # show key size use either 16, 24 or 32 byte keys for AES-128, 192
        # and 256 respectively
        $key_size =  strlen($key);
        echo "Key size: " . $key_size . "\n";
        

        # create a random IV to use with CBC encoding
        $iv_size = \mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        
        # creates a cipher text compatible with AES (Rijndael block size = 128)
        # to keep the text confidential 
        # only suitable for encoded input that never ends with value 00h
        # (because of default zero padding)
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
                                     $plaintext, MCRYPT_MODE_CBC, $iv);

        # prepend the IV for it to be available for decryption
        $ciphertext = $iv . $ciphertext;
        
        # encode the resulting cipher text so it can be represented by a string
        $ciphertext_base64 = base64_encode($ciphertext);

        echo  $ciphertext_base64 . "\n";
        return $ciphertext_base64;
    }
}
