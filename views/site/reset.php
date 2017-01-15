<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

$this->title = 'Reset Key';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-reset">
    <h1><?= Html::encode($this->title) ?></h1>
    <php

    ?>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['action' => ['update'],'id' => 'reset-form']); ?>
                <?= $form->field($model, 'name')->hiddenInput(['value' => $_GET['name']])?>
                <?= $form->field($model, 'password')->passwordInput(['maxlength' => 20])  ?>
                <?= $form->field($model, 'password2')->passwordInput(['maxlength' => 20])  ?>
                <div class="form-group">
                    <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
