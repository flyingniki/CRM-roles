<?php

use \Bitrix\Crm\Service;

class crmRules
{

    private $rolesInfo;

    private function getRoles()
    {
        global $USER;
        global $APPLICATION;

        if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

        if (!CModule::IncludeModule('crm')) {
            ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
            return;
        }

        $CrmPerms = new CCrmPerms($USER->GetID());
        if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')) {
            ShowError(GetMessage('CRM_PERMISSION_DENIED'));
            return;
        }

        $restriction = \Bitrix\Crm\Restriction\RestrictionManager::getPermissionControlRestriction();
        $arResult['IS_PERMITTED'] = $restriction->hasPermission();
        if (!$arResult['IS_PERMITTED']) {
            $arResult['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
        }

        CJSCore::Init(array('access', 'window'));

        $arParams['PATH_TO_ROLE_EDIT'] = CrmCheckPath('PATH_TO_ROLE_EDIT', $arParams['PATH_TO_ROLE_EDIT'], $APPLICATION->GetCurPage());
        $arParams['PATH_TO_ENTITY_LIST'] = CrmCheckPath('PATH_TO_ENTITY_LIST', $arParams['PATH_TO_ENTITY_LIST'], $APPLICATION->GetCurPage());

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ACTION'] == 'save' && check_bitrix_sessid() && $arResult['IS_PERMITTED']) {
            $arPerms = isset($_POST['PERMS']) ? $_POST['PERMS'] : array();
            $CCrmRole = new CcrmRole();
            $CCrmRole->SetRelation($arPerms);

            CCrmSaleHelper::updateShopAccess();

            $cache = new \CPHPCache;
            $cache->CleanDir("/crm/list_crm_roles/");

            LocalRedirect($APPLICATION->GetCurPage());
        }

        // get role list
        $arResult['PATH_TO_ROLE_ADD'] = CComponentEngine::MakePathFromTemplate(
            $arParams['PATH_TO_ROLE_EDIT'],
            array(
                'role_id' => 0
            )
        );
        $arResult['ROLE'] = array();
        $obRes = CCrmRole::GetList();
        while ($arRole = $obRes->Fetch()) {
            $arRole['PATH_TO_EDIT'] = CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_ROLE_EDIT'],
                array(
                    'role_id' => $arRole['ID']
                )
            );
            $arRole['PATH_TO_DELETE'] = CHTTP::urlAddParams(
                CComponentEngine::MakePathFromTemplate(
                    $arParams['PATH_TO_ROLE_EDIT'],
                    array(
                        'role_id' => $arRole['ID']
                    )
                ),
                array('delete' => '1', 'sessid' => bitrix_sessid())
            );
            $arRole['NAME'] = htmlspecialcharsbx($arRole['NAME']);
            $arResult['ROLE'][$arRole['ID']] = $arRole;
        }

        // get role relation
        $arResult['RELATION'] = array();
        $arResult['RELATION_ENTITY'] = array();
        $obRes = CCrmRole::GetRelation();
        while ($arRelation = $obRes->Fetch()) {
            $arResult['RELATION'][$arRelation['RELATION']] = $arRelation;
            $arResult['RELATION_ENTITY'][$arRelation['RELATION']] = true;
        }

        $CAccess = new CAccess();
        $arNames = $CAccess->GetNames(array_keys($arResult['RELATION_ENTITY']));
        foreach ($arResult['RELATION'] as &$arRelation) {
            $arRelation['NAME'] = htmlspecialcharsbx($arNames[$arRelation['RELATION']]['name']);
            $providerName = $arNames[$arRelation['RELATION']]['provider'];
            if (!empty($providerName)) {
                $arRelation['NAME'] = /*'<b>' . htmlspecialcharsbx($providerName) . ':</b> ' . */ $arRelation['NAME'];
            }
        }
        unset($arRelation);

        foreach ($arResult['RELATION'] as $arRelation) :
            // echo '<b>Группа/Отдел:</b> ' . $arRelation['NAME'] . '; ';
            foreach ($arResult['ROLE'] as $arRole) :
                if ($arRole['ID'] == $arRelation['ROLE_ID']) :
                    $arRolesInfo[$arRelation['NAME']][$arRole['ID']] = $arRole['NAME'];
                // echo '<b>ID роли:</b> ' . $arRole['ID'] . '<br>';
                // echo '<b>Название роли:</b> ' . $arRole['NAME'] . '<br><br>';
                endif;
            endforeach;
        endforeach;
        $this->rolesInfo = $arRolesInfo;
    }

    public function getPerms($roleID)
    {
        // global $USER;
        global $APPLICATION;
        if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

        if (!CModule::IncludeModule('crm')) {
            ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
            return;
        }

        $restriction = \Bitrix\Crm\Restriction\RestrictionManager::getPermissionControlRestriction();
        $arResult['IS_PERMITTED'] = $restriction->hasPermission();
        if (!$arResult['IS_PERMITTED']) {
            $arResult['LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
        }

        $arParams['PATH_TO_ROLE_EDIT'] = CrmCheckPath('PATH_TO_ROLE_EDIT', $arParams['PATH_TO_ROLE_EDIT'], $APPLICATION->GetCurPage());
        $arParams['PATH_TO_ENTITY_LIST'] = CrmCheckPath('PATH_TO_ENTITY_LIST', $arParams['PATH_TO_ENTITY_LIST'], $APPLICATION->GetCurPage());
        $arParams['ROLE_ID'] = $roleID;
        $bVarsFromForm = false;

        if (!$bVarsFromForm) {
            if ($arParams['ROLE_ID'] > 0) {
                $obRes = CCrmRole::GetList(array(), array('ID' => $arParams['ROLE_ID']));
                $arResult['ROLE'] = $obRes->Fetch();
                if ($arResult['ROLE'] == false)
                    $arParams['ROLE_ID'] = 0;
            }

            if ($arParams['ROLE_ID'] <= 0) {
                $arResult['ROLE']['ID'] = 0;
                $arResult['ROLE']['NAME'] = '';
            }

            $arResult['ROLE_PERMS'] = array();
        }
        if ($arParams['ROLE_ID'] > 0 && !$bVarsFromForm)
            $arResult['~ROLE_PERMS'] = CCrmRole::GetRolePerms($arParams['ROLE_ID']);
        if (!$bVarsFromForm)
            $arResult['ROLE_PERMS'] = $arResult['~ROLE_PERMS'];

        $permissionSet = [
            BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_NONE),
            BX_CRM_PERM_SELF => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_SELF),
            BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_DEPARTMENT),
            BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_SUBDEPARTMENT),
            BX_CRM_PERM_OPEN => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_OPEN),
            BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_ALL)
        ];
        $operations = ['READ', 'ADD', 'WRITE', 'DELETE'];
        $operationsWithImport = $operations;
        $operationsWithImport[] = 'EXPORT';
        $operationsWithImport[] = 'IMPORT';
        $operationsWithAutomation = $operationsWithImport;
        $operationsWithAutomation[] = 'AUTOMATION';

        $arResult['ENTITY'] = [];

        $arResult['ENTITY']['CONTACT'] = GetMessage('CRM_ENTITY_TYPE_CONTACT');
        $factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Contact);
        foreach ($factory->getCategories() as $category) {
            if ($category->getIsDefault()) {
                continue;
            }
            $entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId()));
            $entityTitle = $category->getName();
            $arResult['ENTITY'][$entityName] =  htmlspecialcharsbx($entityTitle);
            $arResult['ROLE_PERM'][$entityName] = $permissionSet;
            $entityOperationsMap[$entityName] = $operationsWithImport;
        }

        $arResult['ENTITY']['COMPANY'] = GetMessage('CRM_ENTITY_TYPE_COMPANY');
        $factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Company);
        foreach ($factory->getCategories() as $category) {
            if ($category->getIsDefault()) {
                continue;
            }
            $entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId()));
            $entityTitle = $category->getName();
            $arResult['ENTITY'][$entityName] = htmlspecialcharsbx($entityTitle);
            $arResult['ROLE_PERM'][$entityName] = $permissionSet;
            $entityOperationsMap[$entityName] = $operations;
        }

        $arResult['ENTITY']['DEAL'] = GetMessage('CRM_ENTITY_TYPE_DEAL');

        $dealCategoryConfigs = Bitrix\Crm\Category\DealCategory::getPermissionRoleConfigurations();
        foreach ($dealCategoryConfigs as $typeName => $config) {
            $arResult['ENTITY'][$typeName] = isset($config['NAME']) ? htmlspecialcharsbx($config['NAME']) : $typeName;
        }

        $arResult['ENTITY'] = array_merge(
            $arResult['ENTITY'],
            [
                'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
                'QUOTE' => GetMessage('CRM_ENTITY_TYPE_QUOTE'),
            ]
        );

        $typesMap = Service\Container::getInstance()->getDynamicTypesMap()->load();

        if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled()) {
            $arResult['ENTITY'][\CCrmOwnerType::InvoiceName] = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Invoice);
        }

        $entityOperationsMap = [
            'LEAD' => $operationsWithAutomation,
            'QUOTE' => $operationsWithImport,
            'INVOICE' => $operationsWithImport,
            'CONTACT' => $operationsWithImport,
            'COMPANY' => $operationsWithImport,
            'ORDER' => $operationsWithAutomation,
            'WEBFORM' => ['READ', 'WRITE'],
            'BUTTON' => ['READ', 'WRITE'],
            'SALETARGET' => ['READ', 'WRITE'],
            'EXCLUSION' => ['READ', 'WRITE'],
        ];

        $arResult['ENTITY_FIELDS'] = array(
            'DEAL' => array('STAGE_ID' => CCrmStatus::GetStatusListEx('DEAL_STAGE')),
            'LEAD' => array('STATUS_ID' => CCrmStatus::GetStatusListEx('STATUS'))
        );

        if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled()) {
            $smartInvoiceFactory = Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
            if ($smartInvoiceFactory) {
                $isAutomationEnabled = $smartInvoiceFactory->isAutomationEnabled();
                foreach ($smartInvoiceFactory->getCategories() as $category) {
                    $entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType(\CCrmOwnerType::SmartInvoice, $category->getId()));
                    $entityTitle = \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice);
                    if ($smartInvoiceFactory->isCategoriesEnabled()) {
                        $entityTitle .= ' ' . $category->getName();
                    }
                    $arResult['ENTITY'][$entityName] = $entityTitle;
                    foreach ($smartInvoiceFactory->getStages($category->getId()) as $stage) {
                        $arResult['ENTITY_FIELDS'][$entityName][\Bitrix\Crm\Item::FIELD_NAME_STAGE_ID][htmlspecialcharsbx($stage->getStatusId())] = htmlspecialcharsbx($stage->getName());
                    }
                    $arResult['ROLE_PERM'][$entityName] = $permissionSet;
                    $entityOperationsMap[$entityName] = $isAutomationEnabled ? $operationsWithAutomation : $operationsWithImport;
                }
            }
        }

        $arResult['ENTITY'] = array_merge(
            $arResult['ENTITY'],
            [
                'ORDER' => GetMessage('CRM_ENTITY_TYPE_ORDER'),
                'WEBFORM' => GetMessage('CRM_ENTITY_TYPE_WEBFORM'),
                'BUTTON' => GetMessage('CRM_ENTITY_TYPE_BUTTON'),
                'SALETARGET' => GetMessage('CRM_ENTITY_TYPE_SALETARGET'),
                'EXCLUSION' => GetMessage('CRM_ENTITY_TYPE_EXCLUSION'),
            ]
        );

        $arResult['ROLE_PERM']['LEAD'] = $arResult['ROLE_PERM']['DEAL'] =
            $arResult['ROLE_PERM']['QUOTE'] = $arResult['ROLE_PERM']['INVOICE'] =
            $arResult['ROLE_PERM']['COMPANY'] = $arResult['ROLE_PERM']['CONTACT'] =
            $arResult['ROLE_PERM']['ORDER'] = $permissionSet;
        $arResult['ROLE_PERM']['WEBFORM'] = $arResult['ROLE_PERM']['BUTTON'] =
            $arResult['ROLE_PERM']['EXCLUSION'] = array(
                BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_NONE),
                BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_ALL)
            );
        $arResult['ROLE_PERM']['SALETARGET'] = array(
            BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_NONE),
            BX_CRM_PERM_SELF => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_SELF),
            BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_DEPARTMENT),
            BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_SUBDEPARTMENT),
            BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_' . BX_CRM_PERM_ALL)
        );
        $arResult['ROLE_PERM']['AUTOMATION'] = array(
            BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_AUTOMATION_NONE'),
            BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_AUTOMATION_ALL')
        );

        foreach ($dealCategoryConfigs as $typeName => $config) {
            if (isset($config['FIELDS']) && is_array($config['FIELDS'])) {
                $fields = $config['FIELDS'];
                foreach ($fields as $fieldType => $stages) {
                    foreach ($stages as $stageId => $stageName) {
                        unset($fields[$fieldType][$stageId]);
                        $fields[$fieldType][htmlspecialcharsbx($stageId)] = htmlspecialcharsbx($stageName);
                    }
                }
                $arResult['ENTITY_FIELDS'][$typeName] = $fields;
            }

            $arResult['ROLE_PERM'][$typeName] = $permissionSet;
        }

        foreach ($typesMap->getTypes() as $type) {
            $isAutomationEnabled = $typesMap->isAutomationEnabled($type->getEntityTypeId());
            $stagesFieldName = htmlspecialcharsbx($typesMap->getStagesFieldName($type->getEntityTypeId()));
            foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category) {
                $entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($type->getEntityTypeId(), $category->getId()));
                $entityTitle = $type->getTitle();
                if ($type->getIsCategoriesEnabled()) {
                    $entityTitle .= ' ' . $category->getName();
                }
                $arResult['ENTITY'][$entityName] = htmlspecialcharsbx($entityTitle);
                if ($type->getIsStagesEnabled()) {
                    foreach ($typesMap->getStages($type->getEntityTypeId(), $category->getId()) as $stage) {
                        $arResult['ENTITY_FIELDS'][$entityName][$stagesFieldName][htmlspecialcharsbx($stage->getStatusId())] = htmlspecialcharsbx($stage->getName());
                    }
                }
                $arResult['ROLE_PERM'][$entityName] = $permissionSet;
                $entityOperationsMap[$entityName] = $isAutomationEnabled ? $operationsWithAutomation : $operationsWithImport;
            }
        }

        unset($arResult['ROLE_PERM']['INVOICE'][BX_CRM_PERM_OPEN]);
        unset($arResult['ROLE_PERM']['ORDER'][BX_CRM_PERM_OPEN]);

        $arResult['PATH_TO_ROLE_DELETE'] =  CHTTP::urlAddParams(
            CComponentEngine::MakePathFromTemplate(
                $arParams['PATH_TO_ROLE_EDIT'],
                array(
                    'role_id' => $arResult['ROLE']['ID']
                )
            ),
            array('delete' => '1', 'sessid' => bitrix_sessid())
        );

        foreach ($operationsWithAutomation as $operation) {
            foreach ($arResult['ENTITY'] as $entityType => $entityName) {
                if (!isset($entityOperationsMap[$entityType]) || in_array($operation, $entityOperationsMap[$entityType])) {
                    $arResult['ENTITY_PERMS'][$entityType][] = $operation;
                }

                if (isset($arResult['ENTITY_FIELDS'][$entityType])) {
                    foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue) {
                        foreach ($arFieldValue as $fieldValueID => $fieldValue) {
                            if (!isset($arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID]) || $arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID] == '-')
                                $arResult['ROLE_PERMS'][$entityType][$operation][$fieldID][$fieldValueID] = $arResult['ROLE_PERMS'][$entityType][$operation]['-'];
                        }
                    }
                }
            }
        }

        $APPLICATION->SetTitle(GetMessage('CRM_PERMS_ROLE_EDIT'));
        $APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);
        $APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ROLE_EDIT'), $arResult['PATH_TO_ROLE_EDIT']);

        $rolePerms = array(
            '' => 'Нет доступа',
            'A' => 'Свои',
            'D' => 'Свои + своего отдела',
            'F' => 'Свои + своего отдела + подотделов',
            'O' => 'Все открытые',
            'X' => 'Все',
        );

        foreach ($arResult['ENTITY'] as $entityType => $entityName) :
            // echo '<b>Сущность:</b> ' . $entityType . ' => ' . $entityName . '<br>';

            if (in_array('READ', $arResult['ENTITY_PERMS'][$entityType])) :
                foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName) :
                    if (array_key_exists($rolePermAtr, $rolePerms)) :
                        $rolePermName = $rolePerms[$rolePermAtr];
                    endif;
                    // echo ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['READ']['-'] ? '<b>Атрибут для READ:</b> ' . $rolePermName . '<br>' : '');
                    if ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['READ']['-']) :
                        // echo '<b>Атрибут для READ:</b> ' . $rolePermName . '<br>';
                        $entityPerms[$entityName]['READ'] = $rolePermName;
                    // else :
                    // echo '';
                    endif;
                endforeach;
            endif;

            if (in_array('ADD', $arResult['ENTITY_PERMS'][$entityType])) :
                foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName) :
                    if (array_key_exists($rolePermAtr, $rolePerms)) :
                        $rolePermName = $rolePerms[$rolePermAtr];
                    endif;
                    // echo ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['ADD']['-'] ? '<b>Атрибут для  ADD:</b> ' . $rolePermName . '<br>' : '');
                    if ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['ADD']['-']) :
                        // echo '<b>Атрибут для ADD:</b> ' . $rolePermName . '<br>';
                        $entityPerms[$entityName]['ADD'] = $rolePermName;
                    // else :
                    // echo '';
                    endif;
                endforeach;
            endif;

            if (in_array('WRITE', $arResult['ENTITY_PERMS'][$entityType])) :
                foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName) :
                    if (array_key_exists($rolePermAtr, $rolePerms)) :
                        $rolePermName = $rolePerms[$rolePermAtr];
                    endif;
                    // echo ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['WRITE']['-'] ? '<b>Атрибут для  WRITE:</b> ' . $rolePermName . '<br>' : '');
                    if ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['WRITE']['-']) :
                        // echo '<b>Атрибут для WRITE:</b> ' . $rolePermName . '<br>';
                        $entityPerms[$entityName]['WRITE'] = $rolePermName;
                    // else :
                    // echo '';
                    endif;
                endforeach;
            endif;

            if (in_array('DELETE', $arResult['ENTITY_PERMS'][$entityType])) :
                foreach ($arResult['ROLE_PERM'][$entityType] as $rolePermAtr => $rolePermName) :
                    if (array_key_exists($rolePermAtr, $rolePerms)) :
                        $rolePermName = $rolePerms[$rolePermAtr];
                    endif;
                    // echo ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['DELETE']['-'] ? '<b>Атрибут для  DELETE:</b> ' . $rolePermName . '<br><br>' : '');
                    if ($rolePermAtr == $arResult['ROLE_PERMS'][$entityType]['DELETE']['-']) :
                        // echo '<b>Атрибут для DELETE:</b> ' . $rolePermName . '<br><br>';
                        $entityPerms[$entityName]['DELETE'] = $rolePermName;
                    // else :
                    // echo '';
                    endif;
                endforeach;
            endif;
        endforeach;

        return $entityPerms;
    }

    public function crmPerms()
    {
        $this->getRoles();
        $rolesInfo = $this->rolesInfo;
        // берем отсюда данные для Excel
        foreach ($rolesInfo as $roleGroup => $roleInfo) :
            foreach ($roleInfo as $roleID => $roleName) :
                // echo $roleGroup . ' <b>ID роли:</b> ' . $roleID . ' <b>Название роли:</b> ' . $roleName . '<br>';
                $perms[$roleGroup] = $this->getPerms($roleID);
            endforeach;
        endforeach;
        // echo '<pre>';
        // print_r($perms);
        // echo '</pre>';
        return $perms;
    }
}

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

include($_SERVER['DOCUMENT_ROOT'] . '/php2excel/simplexlsxgen-master/src/SimpleXLSXGen.php');
CModule::IncludeModule("crm");
CModule::IncludeModule("workflow");
CModule::IncludeModule("bizproc");
$container = Service\Container::getInstance();

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
