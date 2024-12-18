<?php
use yii\helpers\Html;
?>

<ul class="<?= Html::encode($ulClass) ?>">
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            <li class="<?= Html::encode($liClass) ?>">
                <?= Html::a(Html::encode($item['label']), $item['url']) ?>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li class="<?= Html::encode($liClass) ?>"><a href="#">No allowed applications found</a></li>
    <?php endif; ?>
</ul>