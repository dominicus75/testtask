<?php declare(strict_types=1);

setlocale(LC_ALL, 'hu_HU.UTF-8');

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$employee = new \Application\Model\Department(new \Dominicus75\Config\Config('mysql'), 'departments', ['d003']);
/*$employee->setProperties([
    "emp_no" => 110022,
    "birth_date" => "1956-09-12",
    "first_name" => "Margareta",
    "last_name" => "Markovitch",
    "gender" => "M",
    "hire_date" => "2015-12-31"
]);
$employee->insert();*/

echo '<pre>';
var_dump($employee);
echo '</pre>';