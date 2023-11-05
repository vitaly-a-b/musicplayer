<?php


namespace core\user\controller;


use core\user\model\Model;

class SearchController extends BaseUser
{

    // таблицы в которых будет поиск
    private $searchTables = ['track'];

    // колонки таблицы по которым искать
    private $searchRows = ['name', 'alias'];

    // в каком порядке искать
    private $orderRows = [];
    private $fields = ['id'];
    private $returnQuery = false;
    private $noPagination = true;
    public $callBack = null;

    private $var = null;



    protected function inputData()
    {
        parent::inputData(); // TODO: Change the autogenerated stub

        // проверка токена при получении формы от пользователя
        //$this->checkToken();

        if (!empty($_GET['choice'])){

            $_GET['choice'] = $this->clearStr($_GET['choice']);

            switch ($_GET['choice']){

                case 'artist':
                    $this->searchTables = ['artist'];
                    break;

                default:
                    $this->searchTables = ['track', 'artist'];
                    $this->searchRows[] = 'text';
                    break;
            }
        }

        $result = $this->searchData();

        $where = [];
        $condition = [];
        $tracks = null;

        if ($result['data']){

            foreach ($this->searchTables as $item){

                $this->var = $item;

                $arr = array_filter($result['data'], function ($el){

                    if (isset($el['table_name']) && $el['table_name'] === $this->var){
                        return true;
                    }

                    return false;

                }, ARRAY_FILTER_USE_BOTH);

                if ($arr){

                    switch ($item){

                        case 'track':
                            $where['id'] = array_column($arr, 'id');
                            break;

                        case 'artist':
                            $where['parent_id'] = array_column($arr, 'id');
                            break;

                    }

                }

            }

            if (($count = count($where)) >1){

                $keys = array_keys($where);
                $keys[0] = '(' . $keys[0];
                $keys[$count-1] = ')' . $keys[$count-1];

                $where = array_combine($keys, array_values($where));

                for ($i=0; $i<$count-1; $i++){
                    $condition[] = 'OR';
                }

            }


            if (!empty($this->model->showColumns('track')['visible'])){
                $where['visible'] = 1;
                $condition[] = 'AND';
            }

            $pagination = $this->clearNum($_GET['page'] ?? 1) ?? 1;

            $tracks = $this->model->get('track', [
                'where' => $where,
                'condition' => $condition,
                'pagination' => $pagination,
                'join' => [
                    'artist' => [
                        'fields' => ['name as artist_name'],
                        'on' => ['parent_id' => 'id'],
                    ]
                ],
            ]);

        }

        $pages = $this->model->getPagination();

        $this->template = TEMPLATE .'index';

        return compact('tracks', 'pages');

    }




    public function searchData($search =''){

        // если поисковая строка не пришла, берем из $_GET['search']
        !$search && $search = $_GET['search'] ?? null;

        // если нечего или негде искать
        if(!$search || !$this->searchTables || !$this->searchRows){

            if($this->returnQuery){
                return false;
            }

            $this->redirect();
        }

        !$this->model && $this->model = Model::instance();

        $search = trim(preg_replace('/[^\w\-\s]/u', '', $search));

        // строка с поисковым запросом разбивается в массив по пробелу, т.е каждый элемент это отдельное слово
        $arr = preg_split('/\s+/', $search, 0, PREG_SPLIT_NO_EMPTY);

        //массив с поисковым запросом
        $searchArr = [];

        for(;;){

            if(!$arr)
                break;

            //поисковый запрос: 'aa bb cc', в итоге массив $searchArr[] = [aa bb cc, aa bb, aa]
            $searchArr[] = implode(' ', $arr);

            // выкидываем последний элемент
            array_pop($arr);

        }

        $order = [];
        $orderByGoodsTableName = false;

        foreach ($this->searchTables as $table){

            $res = $this->createWhereOrder($searchArr, $table);

            $where = $res['where'];
            !$order && $order = $res['order'];

            if($where){

                $fields = ['*', "('$table') AS table_name"];

                if($this->fields){
                    $fields = $this->fields[$table] ?? $this->fields;
                    $fields[] =  "('$table') AS table_name";

                }elseif (!empty($this->model->goodsTable) && $this->model->goodsTable === $table){
                    $orderByGoodsTableName = true;

                }

                $this->model->buildUnion($table, [
                   'fields' => $fields,
                   //'no_concat' => true,
                    'where' => $where
                ]);
            }
        }

        $dbOrder = '';

        if($order){

            if($orderByGoodsTableName){

                $dbOrder = "table_name = '{$this->model->goodsTable}' DESC";
            }

            $firstOrder = preg_replace('/[\(\)]/', '', $order[0]);

            $dbOrder .= ($dbOrder ? ', ' : '') . "IF($firstOrder, 1, 0) DESC, (" . implode('+', $order) . ") DESC";

            if(!empty($this->model->goodsTable) && in_array($this->model->goodsTable, $this->searchTables) &&
                !empty($this->model->showColumns($this->model->goodsTable)['price'])) {

                $dbOrder .= ', IF(price > 0, 1, 0) DESC, price';
            }

        }

        if(!$this->noPagination){

            $page = !empty($_GET['page']) ? $this->clearStr($_GET['page']) : 0;

            if(!$this->returnQuery && !$page){
                $page = 1;
            }
        }

        $data = $this->model->getUnion([
            'pagination' => $page ?? null,
            'order' => $dbOrder,
            'return_query' => $this->returnQuery
        ]);

        $pages = !$this->returnQuery ? $this->model->getPagination() : [];

        if($data){

            if($this->callBack && is_callable($this->callBack)){

                $callback = $this->callBack;
                $this->callBack = null;
                $callback($data);

            }
        }

        return compact('data', 'pages');

    }




    private function createWhereOrder($searchArr, $table){

        $where = '';
        $order = [];
        $columns = $this->model->showColumns($table);

        foreach ($this->searchRows as $row){

            if(!$where){

                if(!empty($columns['visible'])){
                    $where .= 'visible=1 AND (';

                }else{
                    $where .= '(';
                }
            }

            $where .= '(';

            foreach ($searchArr as $item){

                $orderItem = '';

                if(in_array($row, $this->orderRows)){

                    $orderItem = "($row LIKE '%$item%')";
                }

                if($orderItem && !in_array($orderItem, $order)){
                    $order[] = $orderItem;
                }

                if(isset($columns[$row])){
                    $where .= "$row LIKE '%$item%' OR ";
                }
            }

            $where = preg_replace('/\)?\s*or\s*\(?$/i', '', $where);

            $where .= ') OR ';

        }

        if($where){
            $where = preg_replace('/\s+or\s+$/i', '', $where) . ')';
        }


        return compact('where', 'order');


    }


    public function setSearchParameters(array $parameters):void{

        foreach ($parameters as $key => $item){

            if(property_exists($this, $key)){

                $this->$key = $item;
            }
        }

    }



}


















