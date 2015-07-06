<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\GeneratorForm;
use app\models\ContactForm;
use yii\web\Response;
use yii\widgets\ActiveForm;

class ExController extends Controller
{
    public $defaultAction = 'home';
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout','generator'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['generator'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(\Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        \Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(\Yii::$app->request->post()) && $model->contact(\Yii::$app->params['adminEmail'])) {
            \Yii::$app->session->setFlash('contactFormSubmitted');
            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionHome()
    {
        return $this->render('home');
    }

    private function performAjaxValidation($model){
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return ActiveForm::validate($model);
    }

    private function outResult($model){
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $model->getResult();
    }

    private function outError($model){
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $model->getAllError();
    }

    public function actionGenerator()
    {
        $model = new GeneratorForm();
        $model->scenario = $this->action->id;
        $post = \Yii::$app->request->post();
        $get = \Yii::$app->request->get();
        $formName = $model->formName();
        $post[$formName]['ajax'] = (
            (isset($post['ajax']))
               ? $post['ajax']
               : ''
        );
        $post[$formName]['json'] = (
            (isset($get['json']))
               ? $get['json']
               : ''
        );
        $model->load($post);
        unset($get,$post['ajax']);
        if(\Yii::$app->request->isAjax && !empty($model->ajax)){
            return $this->performAjaxValidation($model);
        }
        $status = $model->generate();
        if(\Yii::$app->request->isAjax){
          if($status === true){
             return $this->outResult($model);
          }else{
             return $this->outError($model);
          }
        }
        return $this->render('generator', [
            'model' => $model,
        ]);
    }

}
