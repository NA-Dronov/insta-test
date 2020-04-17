<div class="row mt-3">
    <div class="col-md-9">
        <table class="table">
            <thead>
                <tr>
                    <th>Идентификатор валюты</th>
                    <th>Числовой код валюты</th>
                    <th>Буквенный код валюты</th>
                    <th>Имя валюты</th>
                    <th>Курс</th>
                    <th>Дата публикации</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['data'] as $currency) : ?>
                    <tr>
                        <td><?= $currency->valuteID ?></td>
                        <td><?= $currency->numCode ?></td>
                        <td><?= $currency->charCode ?></td>
                        <td><?= $currency->name ?></td>
                        <td><?= $currency->value ?></td>
                        <td><?= $currency->date ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <?php if (!empty($data['params']) && $data['params']['total'] > 1) : ?>
            <ul>
                <?php for ($i = 1; $i < $data['params']['total'] + 1; $i++) : ?>
                    <li><a href="<?= URL_ROOT . 'home?' . 'page=' . $i ?>" class="<?php if ($data['params']['page'] == $i) : ?>active<?php endif ?>">[<?= $i ?>]</a></li>
                <?php endfor; ?>
            </ul>
        <?php endif ?>
    </div>
    <div class="col-md-3 p-2">
        <form action="<?= URL_ROOT . 'home' ?>" method="get">
            <?php if (!empty($data['params']['page'])) : ?>
                <input type="hidden" name="page" value="<?= $data['params']['page'] ?>">
            <?php endif ?>
            <div class="form-group">
                <input type="text" name="date" id="date_picker" value="<?= $data['params']['from'] ?? '' ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-block btn-primary">Отправить</button>
        </form>
    </div>
</div>