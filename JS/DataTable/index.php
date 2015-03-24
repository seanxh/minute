<?php
/**
 * Created by PhpStorm.
 * User: sean
 * Date: 14-9-26
 * Time: 下午11:17
 */

$draw = $_POST['draw'];
$array = array(
    'recordsTotal'=>10,
    'draw'=>$draw,
    "recordsFiltered"=>10,
    'data'=>array(
        array(
            'title'=>"Tiger Nixon",
            'test'=>"System Architect",
            'amount'=>1,
        ),
        array(
            'title'=>"Garrett Winters",
            'test'=>"Accountant",
            'amount'=>2,
        ),
    ),
);

echo json_encode($array);