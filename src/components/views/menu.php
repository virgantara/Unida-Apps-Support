<?php
use yii\helpers\Html;
?>

<ul class="nav navbar-nav">
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            <li>
                <?= Html::a(Html::encode($item['label']), $item['url']) ?>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li><a href="#">No allowed applications found</a></li>
    <?php endif; ?>
</ul>
