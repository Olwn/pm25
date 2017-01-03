<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ResetForm extends Model
{
    public $name;
    public $password;
    public $password2;
    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'password', 'password2'], 'required'],
            [['password2'], 'compare','compareAttribute'=>'password'],
        ];
    }
}
