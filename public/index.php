<?php declare(strict_types=1);

setlocale(LC_ALL, 'hu_HU.UTF-8');

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$employee = new \Application\Model\Employee(new \Dominicus75\Config\Config('mysql'), 'employees');
$employee->create(
    [
    "birth_date" => "1956-09-12",
    "first_name" => "Géza",
    "last_name" => "Golyó",
    "gender" => "M",
    "hire_date" => "2015-12-31"
    ],
    'd002',
    300000,
    'Staff'
);
#$employee->insert();

echo '<pre>';
#var_dump($employee->salary());
var_dump($employee);
echo '</pre>';