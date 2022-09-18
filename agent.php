<?php

use \Bitrix\Crm\Service;

require_once($_SERVER['DOCUMENT_ROOT'] . '/php2excel/simplexlsxgen-master/src/SimpleXLSXGen.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/crm/configs/perms/class.php');

CModule::IncludeModule("crm");
CModule::IncludeModule("workflow");
CModule::IncludeModule("bizproc");
$container = Service\Container::getInstance();

$obj = new crmRules();
$perms = $obj->crmPerms();

//разбиваем массив в строки
foreach ($perms as $groupName => $groupProps) {
    $groupName = explode(': ', $groupName);
    $groupName = implode(', ', $groupName);
    foreach ($groupProps as $entity => $entityPerms) {
        foreach ($entityPerms as $atr => $perm) {
            $resultEntityPerms[] = [$groupName, $entity, $atr, $perm];
        }
    }
}

foreach ($resultEntityPerms as $key => $value) {
    foreach ($value as $item) {
        $resultEntityPermsString[$key] = implode(', ', $value);
    }
}

foreach ($resultEntityPermsString as $item) {
    $result[] = explode(', ', $item);
}

foreach ($result as $key => $res) {
    if (count($res) == 4) {
        array_unshift($res, 'Группа/Пользователь');
        $result[$key] = $res;
    }
}

array_unshift($result, ['<b>Группа/Отдел/Пользователь</b>', '<b>Роль</b>', '<b>Сущность</b>', '<b>Операция</b>', '<b>Правила</b>']);
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($result, 'CRM Perms');
$title = 'CRM Permissions ' . date("m.d.Y H.i.s");
$xlsx->saveAs($_SERVER["DOCUMENT_ROOT"] . '/upload/crm_perms/' . $title . '.xls');
$Smart_Type_ID = 188;
$factory = $container->getFactory($Smart_Type_ID);
if (!$factory) {
    echo 'factory not found';
}

$data = [
    'TITLE' => $title,
    'UF_CRM_61_1663143567' => date("m.d.Y H.i.s"), //дата
	'UF_CRM_61_1663334230' => 'https://corp.estelab.ru/upload/crm_perms/' . $title . '.xls' //файл
];

$item = $factory->createItem($data); //можем добавить пустой, далее заполнить минимальные поля. Многие поля сами подтянутся.

$res = $item->save(); // обязательно сохраним
