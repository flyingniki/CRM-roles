<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("PHP to Excel");

include($_SERVER['DOCUMENT_ROOT'] . '/php2excel/simplexlsxgen-master/src/SimpleXLSXGen.php');
include($_SERVER['DOCUMENT_ROOT'] . '/crm/configs/perms/class.php');

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
array_unshift($result, ['<b>Группа/Отдел</b>', '<b>Роль</b>', '<b>Сущность</b>', '<b>Операция</b>', '<b>Правила</b>']);
// echo '<pre>';
// print_r($result);
// echo '</pre>';
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($result, 'CRM Perms');
$title = 'CRM Permissions ' . date("m.d.Y H.i.s");
$xlsx->downloadAs($title . '.xlsx');
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
