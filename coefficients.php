<?php
//TODO проверка на существование коэф. для группы, проверка прав, проверка на существование редактируемой записи


$cats = engine::FACTORY()->COEFFS()->getCategoriesFromProducts();
$cats[] = '%_DEFAULT_%';
$cats[] = '%_NOTFACTORY_%';
$coeffs = engine::FACTORY()->COEFFS()->getAllCategoriesWithCoeffs();
$types = engine::FACTORY()->COEFFS()->getTypes();

mod_breadcrumbs::$disableDefault = true;
module::getModuleClass('breadcrumbs')->addElement('/index.php?component=factory&page=coefficients', 'Управление коэффициентами');;


engine::TEMPLATE()->setTagValue('pagename', 'Управление коэффициентами <i>alpha</i>');
##throw new Exception(123);
?>

<div class="card card-success">
<div class="card-header">
    <div class="card-tools"><a href="#" id="cf_new" class="btn btn-success">Новая группа коэффициентов</a></div>
</div>

<table class="table fixtable">
    <thead>
    <th>Категория</th>
    <?

    foreach($types as $k=>$type){
        echo '<th>'.$type['name'].'</th>';
    }

    ?>
    <th></th>
    </thead>
    <tbody>
        <tr id="controltr" style="display: none;">
            <?php

                echo '<td>';
                echo '<select class="form-control" id="cf_cat">';
                foreach($cats as $cat){
                    echo '<option>';
                    echo $cat;
                    echo '</option>';
                }
                echo '</select><p id="cf_cat_text"></p></td>';
                foreach($types as $k => $type){
                    echo '<td data-type="'.$k.'" style="text-align: center;"><input type="number" value="" step="0.01" id="cfc_'.$k.'" class="form-control" style="width: 75px;"></td>';
                }
                echo '<td><button class="btn btn-success" id="cf_save">Сохранить</button> <button class="btn btn-default" id="cf_close">Отмена</button></td>';

            ?>
        </tr>

        <?php

        foreach($coeffs as $k=>$v){
            echo '<tr data-id="'.$v['id'].'">';
            echo '<td class="cf_cat" data-name="'.$v['pathName'].'">'.($v['pathName'] == '%_DEFAULT_%' ? '<b>По умолчанию</b>' : ($v['pathName'] == '%_NOTFACTORY_%' ? '<b>НЕ ПРОИЗВОДСТВО</b>' : $v['pathName']) ).'</td>';
            //engine::debug_printArray($v['coeffs']);
            foreach($types as $k => $type){
                echo '<td class="cfc_'.$k.'">'.@$v['coeffs'][$k].'</td>';
            }
            echo '<td><button class="btn btn-success cf_edit">Изменить</button></td>';
            echo '</tr>';
        }

        ?>
    </tbody>
</table>


</div>


<script>
    var types = jQuery.parseJSON('<?=json_encode($types);?>');
    var actionType = false;
    var id = -1;

    $(document).ready(function (e){
        $('#controltr').hide();

        $('#cf_new').on('click', function (e){
            actionType = 'new';
            console.log('new');
            for (const [key, value] of Object.entries(types)) {
                $('#cfc_' + key).val(value.default);
            }
            $('#controltr').show();
        });


        $('.cf_edit').on('click', function (e){
            e.preventDefault();
            actionType = 'edit';
            let tr = $(e.target).closest('tr');


            for (const [key, value] of Object.entries(types)) {
                $('#cfc_' + key).val($(tr).children('.cfc_' + key).html());
            }
            $('#cf_cat').val($(tr).children('.cf_cat').data('name'))
            id = $(tr).data('id');
            $('#controltr').show();

            console.log('edit');

        });


        $('#cf_save').on('click', function (e){
            e.preventDefault();
            var objToSend = {
                action: '',
                coeffs: {},
                pathName: '',
                id: -1
            };

            objToSend.pathName = $('#cf_cat').val();

            if(actionType === 'new'){
                $('#cf_save').prop('disabled', true);
                objToSend.action = 'new';
                for (const [key, value] of Object.entries(types)) {
                    objToSend.coeffs[key] = $('#cfc_' + key).val();
                }

                $.ajax({
                    dataType: "json",
                    method: 'POST',
                    url: '/ajax.php?component=factory&do=coeff',
                    data: objToSend,
                    success: function (response) {
                        if (response.status === true) {
                            window.location.reload();
                            $('#controltr').hide();

                        } else {
                            alert(response.message);
                            $('#createModButton').attr('disabled', false);
                        }
                        $('#cf_save').prop('disabled', false);

                    }
                });
            }

            if(actionType === 'edit') {
                objToSend.action = 'edit';
                objToSend.id = id;
                $('#cf_save').prop('disabled', true);
                for (const [key, value] of Object.entries(types)) {
                    objToSend.coeffs[key] = $('#cfc_' + key).val();
                }


                $.ajax({
                    dataType: "json",
                    method: 'POST',
                    url: '/ajax.php?component=factory&do=coeff',
                    data: objToSend,
                    success: function (response) {
                        if (response.status === true) {
                            window.location.reload();
                            $('#controltr').hide();

                        } else {
                            alert(response.message);
                            $('#createModButton').attr('disabled', false);
                        }
                        $('#cf_save').prop('disabled', false);

                    }
                });
            }

            console.log(objToSend);
            //actionType = false;
            console.log('save');

        });

        $('#cf_close').on('click', function (e){
            actionType = false;
            console.log('close');
            $('#controltr').hide();

        });

    });
</script>