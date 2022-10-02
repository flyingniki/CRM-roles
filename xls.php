<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("PHP to Excel");

include($_SERVER['DOCUMENT_ROOT'] . '/php2excel/simplexlsxgen-master/src/SimpleXLSXGen.php');
include($_SERVER['DOCUMENT_ROOT'] . '/crm/configs/perms/class.php');

$obj = new crmRules();
$perms = $obj->crmPerms();

//разбиваем массив в строки
foreach ($perms as $groupRoleGroupName => $arRolesNames) {
    foreach ($arRolesNames as $roleName => $arEntities) {
        foreach ($arEntities as $entityName => $arStages) {
            if (count($arStages) == 4) {
                foreach ($arStages as $operation => $perm) {
                    $result[] = [$groupRoleGroupName, $roleName, $entityName, '', $operation, $perm];
                }
            } else {
                $arStages = array_slice($arStages, 4);
                foreach ($arStages as $stageName => $stageRules) {
                    foreach ($stageRules as $operation => $perm) {
                        $result[] = [$groupRoleGroupName, $roleName, $entityName, $stageName, $operation, $perm];
                    }
                }
            }
        }
    }
}
array_unshift($result, ['<b>Группа/Отдел</b>', '<b>Роль</b>', '<b>Сущность</b>', '<b>Стадия</b>', '<b>Операция</b>', '<b>Правила</b>']);
// echo '<pre>';
// print_r($result);
// echo '</pre>';
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($result, 'CRM Perms');
$title = 'CRM Permissions ' . date("m.d.Y H.i.s");
$xlsx->downloadAs($title . '.xlsx');
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
