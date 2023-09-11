<?php
/**
 * Created by PhpStorm.
 * User: developer
 * Date: 11.02.2019
 * Time: 16:45
 */

namespace core\admin\model;

use core\base\controller\BaseMethods;
use core\base\controller\Singleton;
use core\base\model\BaseModel;
use core\base\settings\Settings;

class Model extends BaseModel
{

    use Singleton;
    use BaseMethods;

    public $userData = [];



    public function updateMenuPosition($table, $row, $where, $end_pos, $update_rows = false, $oldData = []){

        $db_where = [];

        if(!empty($where) && !empty($where[$this->showColumns($table)['id_row']])){

            $db_where['!' . $this->showColumns($table)['id_row']] = $where[$this->showColumns($table)['id_row']];

        }

        if($update_rows && isset($update_rows['where'])){

            if($where){

                $old_data = $oldData ?: $this->get($table, [
                    'fields' => [$update_rows['where'], $row],
                    'where' => $where
                ])[0];

                $start_pos = $this->clearNum($old_data[$row]);

                if(is_numeric($old_data[$update_rows['where']]) && is_numeric($_POST[$update_rows['where']])){

                    $_POST[$update_rows['where']] = $this->clearNum($_POST[$update_rows['where']]);

                    $old_data[$update_rows['where']] = $this->clearNum($old_data[$update_rows['where']]);

                }

                /*Если перенесли в другую родительскую категорию*/
                if($old_data[$update_rows['where']] !== $_POST[$update_rows['where']]) {

                    $pos = $this->clearNum($this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
                        'no_concat' => true
                    ])[0]['count']);

                    if (!empty($pos)) {

                        $this->edit($table, [
                            'fields' => [$row => "$row - 1"],
                            'where' => [
                                $update_rows['where'] => $old_data[$update_rows['where']],
                                '>' . $row => $start_pos
                            ],
                            'no_ecran' => $row
                        ]);

                    }

                    $start_pos = $this->get($table, [
                        'fields' => ['COUNT(*) as count'],
                        'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
                        'no_concat' => true
                    ])[0]['count'] + ($db_where ? 1 : 0);

                }

            }else{

                $start_pos = $this->get($table, [
                    'fields' => ['COUNT(*) as count'],
                    'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
                    'no_concat' => true
                ])[0]['count'] + 1;

            }

            $where_equal = (array_key_exists($update_rows['where'], $_POST)) ? $_POST[$update_rows['where']] : $old_data[$update_rows['where']];

            $db_where[$update_rows['where']] = $where_equal;

        }else{

            if($where){

                $start_pos = $this->clearNum((!empty($oldData[$row]) ? $oldData[$row] : $this->clearNum($this->get($table, [
                    'fields' => [$row],
                    'where' => $where,
                ])[0][$row])));

            }else{

                $start_pos = $this->get($table, [
                    'fields' => ['COUNT(*) as count'],
                    'no_concat' => true
                ])[0]['count'] + 1;

            }
        }

        $fields = [];

        if($start_pos < $end_pos){

            $fields[$row] = "$row - 1";

            $db_where['<=' . $row] = $end_pos;

            $db_where['>' . $row] = $start_pos;

        }elseif($start_pos > $end_pos){

            $fields[$row] = "$row + 1";

            $db_where['>=' . $row] = $end_pos;

            $db_where['<' . $row] = $start_pos;

        }elseif (!$oldData && $where){

            $fields[$row] = "$row + 1";

            $db_where['>=' . $row] = $end_pos;

        }

        if($fields){

            return $this->edit($table, [
                'fields' => $fields,
                'where'  => $db_where,
                'no_ecran' => $row
            ]);

        }

    }




    public function adminSearch($data, $currentTable = false, $page = 1, $qty = QTY){

        $result = [];

        $qty_links = QTY_LINKS;

        $dbTables = $this->showTables();

        if(is_array($page)){

            $pages = $page;

            $page = $pages['page'];

            $qty = $pages['qty'];

            $qty_links = !empty($pages['qty_links']) ? $pages['qty_links'] : $qty_links;

        }

        $data = addslashes($data);

        $arr = preg_split('/,?\s+/u', $data, 0, PREG_SPLIT_NO_EMPTY);

        $searchArr = [];

        $order = [];

        for(;;){

            if(!$arr) break;

            $searchArr[] = implode(' ', $arr);
            unset($arr[count($arr) - 1]);

        }

        $correctCurrentTable = false;

        $temp_tables = Settings::get('projectTables');

        foreach ($temp_tables as $key => $item){

            if(is_numeric($key)){

                $temp_tables[$item] = true;

                unset($temp_tables[$key]);

            }
        }

        foreach($temp_tables as $name => $item){

            if(!in_array($name, $dbTables)) continue;

            $table = $name;

            $seachRows = [];

            $columns = $this->showColumns($table);

            $orderRows = ['name'];

            $fields = [];

            $fields[] = $columns['id_row'] . ' as id';

            if(isset($columns['name'])) $fields['name'] = 'name';

            $fieldName = '';

            foreach($columns as $col => $value){

                if((!isset($fields['name']) || !$fields['name']) && strpos($col, 'name') !== false){

                    if(!$fieldName) $fieldName = 'CASE ';
                    $fieldName .= "WHEN `$col` <> '' THEN `$col` ";

                }

                if(isset($value['Type']) &&
                    (stripos($value['Type'], 'char') !== false ||
                        stripos($value['Type'], 'text') !== false ||
                        stripos($value['Type'], 'float'))){

                    $seachRows[] = $value['Field'];

                }

            }

            if(!empty($fieldName)) $fields['name'] = $fieldName . ' END as name';
            elseif (!$fields['name']) $fields['name'] = $columns['id_row'] . ' as name';

            $fields[] = "('$table') AS table_name";

            $res = $this->createWhereOrder($seachRows, $searchArr, $orderRows, $table);

            $where = $res['where'];

            !$order && $order = $res['order'];

            if($table === $currentTable) {

                $correctCurrentTable = $table;

            }

            if($where){

                $this->buildUnion($table, [
                    'fields' => $fields,
                    'no_concat' => true,
                    'where' => $where,
                ]);

            }

        }

        if($order){

            $order = ($correctCurrentTable ? "table_name = '" . $correctCurrentTable . "' DESC, " : '') . "(" . implode('+', $order) . ")";

            $order_direction = 'DESC';

        }

        $result = $this->getUnion([
            'pagination' => [
                'page' => $page,
                'qty' => $qty,
                'qty_links' => $qty_links
            ],
            'order' => $order,
            'order_direction' => $order_direction
        ]);

        if($result){

            if(is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('routes')['admin']['alias'] . '/plugins')){
                $plugins = scandir($_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('routes')['admin']['alias'] . '/plugins');
                unset($plugins[0], $plugins[1]);
            }

            if(!empty($plugins)){
                foreach ($plugins as $i => $plugin){
                    if(!is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('routes')['admin']['alias'] . '/plugins/'.$plugin)){
                        unset($plugins[$i]);
                    }
                }
            }

            foreach ($result as $index => $item) {
                if(!$item){
                    unset($result[$index]);
                    continue;
                }

                $path = '/';

                if(!empty($plugins)){
                    foreach ($plugins as $plugin){
                        if(strpos($index, $plugin.'_') === 0){
                            $path .= $plugin . '/';
                            break;
                        }
                    }
                }

                $result[$index]['name'] .= ' (' . (isset($temp_tables[$item['table_name']]['name']) ? $temp_tables[$item['table_name']]['name'] : $item['table_name']) . ')';

                $result[$index]['alias'] = PATH.Settings::get('routes')['admin']['alias'] . $path . 'edit/' . $item['table_name'] . '/'.$item['id'];
            }

        }

        return $result ?: [];

    }




    protected function createWhereOrder($seachRows, $searchArr, $orderRows, $table){

        $where = '';

        $order = [];

        if($seachRows){

            $columns = $this->showColumns($table);

            foreach ($seachRows as $row) {

                if(!$where){

                    $where .= '(';

                }

                $where .= '(';

                foreach ($searchArr as $item){

                    $text = '';

                    if(in_array($row, $orderRows))
                        $text = "(`$row` LIKE '%$item%')";

                    if($text && !in_array($text, $order))
                        $order[] = "(`$row` LIKE '%$item%')";

                    if(isset($columns[$row])){

                        $where .= "`$row` LIKE '%$item%' OR ";

                    }

                }

                $where = preg_replace('/\)?\s*or\s*\(?$/i', '', $where);

                $where .= ') OR ';

            }

            if($where) {

                $where = mb_substr($where, 0, -4) . ')';

            }

        }

        return compact('where', 'order');

    }





    public function checkMetaDataTable(){

        if(!in_array('metadata', $this->showTables())){

            $query = "create table metadata
                    (
                        id             int auto_increment primary key,
                        title          varchar(255) null,
                        description    varchar(255) null,
                        keywords       varchar(255) null,
                        name           varchar(255) null,
                        table_name     varchar(255) null,
                        content        text         null,
                        img            varchar(255) null,
                        gallery_img    text         null,
                        short_content  text         null
                    );";

            return $this->query($query, 'u');

        }

        return true;

    }

}