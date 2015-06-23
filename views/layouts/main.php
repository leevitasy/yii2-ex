<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>

<?php $this->beginBody() ?>
    <div class="wrap">
        <?php
            NavBar::begin([
                'brandLabel' => 'Ex Media Search',
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            $itemsAcion = [];
            $itemsAcion[] = ['label' => 'Главная', 'url' => ['/ex/home']];
            $itemsAcion[] = ['label' => 'Фильмы', 'url' => ['/ex/films']];
            $itemsAcion[] = ['label' => 'Сериалы', 'url' => ['/ex/serials']];
            $itemsAcion[] = ['label' => 'Mузыка', 'url' => ['/ex/music']];
            // доступно только для аминистратора            
            if(Yii::$app->user->can('admin')){
               $itemsAcion[] = ['label' => 'Генератор', 'url' => ['/ex/generator']];            
            }
            $itemsAcion[] = Yii::$app->user->isGuest ?
                        ['label' => 'Авторизация', 
				    'url' => ['/ex/login']] :
                        ['label' => 'Выход (' . Yii::$app->user->identity->username . ')',
                        	    'url' => ['/ex/logout'],
                                   'linkOptions' => ['data-method' => 'post']];
            
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => $itemsAcion,
            ]);
            NavBar::end();
        ?>

        <div class="container">
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <?= $content ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="pull-left">&copy; Ex Media Search <?= date('Y') ?></p>
            <p class="pull-right">&nbsp;</p>
        </div>
    </footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
