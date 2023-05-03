<?php

require_once 'engine/paginator.class.php';

engine::TEMPLATE()->setTagValue('pagename', 'Список спецификаций');

echo '<form action="/index.php?component=factory&page=specifications" method="GET">';
echo '<div class="card-body">';
echo '<div class="form-group">';
echo '<h4>Найти спецификацию</h4>';
echo '<input type="hidden" name="component" value="factory">';
echo '<input type="hidden" name="page" value="specifications">';
echo '<input type="search" class="form-control" name="spec" placeholder="Поиск по спецификации">';
echo '</div>';
echo '<button class="btn btn-primary" name="submit">Найти</button> ';
echo '<button class="btn btn-danger" name="reset">Сбросить фильтр</button> ';
echo '</div>';
echo '</form>';


// поиск по спецификациям

 $query = 'SELECT @[prefix]specification.*,
            @[prefix]products.name AS productName,
            @[prefix]modifications.name AS modification,
            @[prefix]products.article AS productArticle 
            FROM @[prefix]specification  ';

$limit      = ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 15;
$num       = ( isset( $_GET['num'] ) ) ? $_GET['num'] : 1;
$links      = ( isset( $_GET['links'] ) ) ? $_GET['links'] : 4;

if(isset($_GET['submit'])){
        if (preg_match("/[а-яёА-ЯЁ]+()/u", $_GET['spec'])) {
            mb_regex_encoding('utf-8');
            $name = trim($_GET['spec']);

            // пагинация
            $query      .= 'JOIN @[prefix]products ON @[prefix]specification.productId = @[prefix]products.id 
                           JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId 
                           WHERE @[prefix]products.name LIKE "%' . $name . '%"
                           OR @[prefix]products.article LIKE "%' . $name . '%"
                           ORDER BY @[prefix]specification.id DESC';
            $rs= engine::DB()->getAll( $query);
            $total= count($rs);
            $offset = $num * $total;

            $Paginator  = new Paginator();
            $newQuery = $Paginator->getQuery($query, $total);
            $results    = $Paginator->getData( $limit, $num, $offset );

            // Выводим записи
            // они выводятся ниже в пагинации

            echo '<div class="card card-success">';
            echo '<div class="card-header"><h3 class="card-title">Спецификации</h3></div>';
            echo '<div class="card-body">';
            echo '<table class="table">';
            echo '<thead>';
            echo '<th>Наименование</th>';
            echo '<th>Артикул</th>';
            echo '<th>Модификация</th>';
            echo '<th>Ревизия</th>';
            echo '<th>Запрещено к производству</th>';
            echo '<thead>';
            echo '<tbody>';
            if($results->data) {
                for ($i = 0; $i < count($results->data); $i++) {
                    echo '<tr>';
                    echo '<td><a href="/index.php?component=factory&page=specification&id=' . $results->data[$i]['id'] . '">' . $results->data[$i]['productName'] . '</a></td>';
                    echo '<td>' . $results->data[$i]['productArticle'] . '</td>';
                    echo '<td>' . $results->data[$i]['modification'] . '</td>';
                    echo '<td>' . $results->data[$i]['revision'] . '</td>';
                    echo '<td>' . ($results->data[$i]['canBeProduced'] ? 'нет' : 'запрещено') . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';

            echo '<div class="container">';

            $last       = ceil( $total / $limit );

            $start      = ( ( $num - $links ) > 0 ) ? $num - $links : 1;
            $end        = ( ( $num + $links ) < $last ) ? $num + $links : $last;


            $html = '  <nav aria-label="Page navigation example">';

            $html       .= '<ul class="pagination">';

            $class      = ( $num == 1 ) ? "disabled" : "";
            $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&spec='.$name.'&submit=&limit=' . $limit . '&num=' . ( $num - 1 ) . '">&laquo;</a></li>';

            if ( $start > 1 ) {
                $html   .= '<li class="page-item"><a class="page-link" href="/index.php?component=factory&page=specifications&spec='.$name.'&submit=&limit=' . $limit . '&num=1">1</a></li>';
                $html   .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            }

            for ( $i = $start ; $i <= $end; $i++ ) {
                $class  = ( $num == $i ) ? " active " : "";
                $html   .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&spec='.$name.'&submit=&limit=' . $limit . '&num=' . $i . '">' . $i . '</a></li>';
            }

            if ( $end < $last ) {
                $html   .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                $html   .= '<li class="page-item"><a class="page-link" href="/index.php?component=factory&page=specifications&spec='.$name.'&submit=&limit=' . $limit . '&num=' . $last . '">' . $last . '</a></li>';
            }

            $class      = ( $num == $last ) ? "disabled" : "";
            $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&spec='.$name.'&submit=&limit=' . $limit . '&num=' . ( $num + 1 ) . '">&raquo;</a></li>';

            $html       .= '</ul>';
            $html       .= '</nav>';


            echo $html;

            echo '</div>';


            echo '<div class="container">';
            echo '<p class="pagination"></p>';
            echo '</div>';

        } else {
            // пагинация
            $query      .= ' JOIN @[prefix]products ON @[prefix]specification.productId = @[prefix]products.id 
                           JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId 
                           ORDER BY @[prefix]specification.id DESC';

            $total  = engine::DB()->getOne('SELECT COUNT(id) FROM @[prefix]specification');
            $offset = $num * $limit;


            if (empty($_GET['spec'])) {
                echo '<div class="alert alert-warning">
            Задан пустой поисковый запрос
        </div>';
            }
          //  mb_regex_encoding('utf-8');
            mb_regex_encoding('utf-8');
            if (!empty($_GET['spec']) && (mb_ereg_match("/[а-яёА-ЯЁ]+()/u", $_GET['spec']) != $total)) {

                echo '<div class="alert alert-warning">
            По вашему вопросу спецификации не найдены
        </div>';

            }
        }
            }

 else {
    // пагинация
    $query      .= ' JOIN @[prefix]products ON @[prefix]specification.productId = @[prefix]products.id 
                   JOIN @[prefix]modifications ON @[prefix]modifications.id = @[prefix]specification.modificationId 
                   ORDER BY @[prefix]specification.id DESC';
     $rs= engine::DB()->getAll($query);
     $total= count($rs);
     $offset = $limit;

     $Paginator  = new Paginator();
     $newQuery = $Paginator->getQuery($query, $total);
     $results    = $Paginator->getData( $limit, $num, $offset );

     echo '<div class="card card-success">';
     echo '<div class="card-header"><h3 class="card-title">Спецификации</h3></div>';
     echo '<div class="card-body">';
     echo '<table class="table">';
     echo '<thead>';
     echo '<th>Наименование</th>';
     echo '<th>Артикул</th>';
     echo '<th>Модификация</th>';
     echo '<th>Ревизия</th>';
     echo '<th>Запрещено к производству</th>';
     echo '<thead>';
     echo '<tbody>';
     if($results->data) {
         for ($i = 0; $i < count($results->data); $i++) {
             echo '<tr>';
             echo '<td><a href="/index.php?component=factory&page=specification&id=' . $results->data[$i]['id'] . '">' . $results->data[$i]['productName'] . '</a></td>';
             echo '<td>' . $results->data[$i]['productArticle'] . '</td>';
             echo '<td>' . $results->data[$i]['modification'] . '</td>';
             echo '<td>' . $results->data[$i]['revision'] . '</td>';
             echo '<td>' . ($results->data[$i]['canBeProduced'] ? 'нет' : 'запрещено') . '</td>';
             echo '</tr>';
         }
     }

     echo '</tbody>';
     echo '</table>';
     echo '</div>';
     echo '</div>';

     echo '<div class="container">';

     $last       = ceil( $total / $limit );

     $start      = ( ( $num - $links ) > 0 ) ? $num - $links : 1;
     $end        = ( ( $num + $links ) < $last ) ? $num + $links : $last;


     $html = '  <nav aria-label="Page navigation example">';

     $html       .= '<ul class="pagination">';

     $class      = ( $num == 1 ) ? "disabled" : "";
     $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&limit=' . $limit . '&num=' . ( $num - 1 ) . '">&laquo;</a></li>';

     if ( $start > 1 ) {
         $html   .= '<li class="page-item"><a class="page-link" href="/index.php?component=factory&page=specifications&limit=' . $limit . '&num=1">1</a></li>';
         $html   .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
     }

     for ( $i = $start ; $i <= $end; $i++ ) {
         $class  = ( $num == $i ) ? " active " : "";
         $html   .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&limit=' . $limit . '&num=' . $i . '">' . $i . '</a></li>';
     }

     if ( $end < $last ) {
         $html   .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
         $html   .= '<li class="page-item"><a class="page-link" href="/index.php?component=factory&page=specifications&limit=' . $limit . '&num=' . $last . '">' . $last . '</a></li>';
     }

     $class      = ( $num == $last ) ? "disabled" : "";
     $html       .= '<li class="page-item ' . $class . '"><a class="page-link" href="/index.php?component=factory&page=specifications&limit=' . $limit . '&num=' . ( $num + 1 ) . '">&raquo;</a></li>';

     $html       .= '</ul>';
     $html       .= '</nav>';


     echo $html;

     echo '</div>';
    // Выводим записи

     echo '<div class="container">';
     echo '<p class="pagination"></p>';
     echo '</div>';
}

//engine::debug_printArray($specifications);

?>

<script>
    var table = $("tr");
    var countRows = table.length - 1;
    var totalRows = <?php print($total) ?>

        $("p.pagination").html("Показано " + countRows + " записей из " + totalRows);

</script>

