<?php
//
use Bitrix\Main\Diag\Debug;

class CEstelabFunctions
{

    protected static $fileExt = '.php';

    protected static $IS_CHECK_GROUP_CREATE = false;
    protected static $IS_CHECK_GROUP_UPDATE = false;
    protected static $IS_MY_TASKS = false;

    private static function include_current_method_file($func_name)
    {

        $filename = 'lib/' . $func_name . static::$fileExt;

        include_once $filename;
    }

    /* Функция дебага  */
    public static function vardump($arr, $var_dump = false)
    {
        echo "<pre style='background: #222;color: #54ff00;padding: 20px; z-index: 10000'>";
        if ($var_dump) var_dump($arr);
        else print_r($arr);
        echo "</pre>";
    }


    public static function WorkFlowBbToHtml($str)
    {
        $str = str_replace("\n", "<br>", $str);
        $str = preg_replace("/\[b\](.+)\[\/b\]/", "<strong>\\1</strong>", $str);

        $str = preg_replace("/\[i\](.+)\[\/i\]/", "\\1", $str);
        $str = preg_replace("/\[u\](.+)\[\/u\]/", "<u>\\1</u>", $str);
        $str = preg_replace("/\[s\](.+)\[\/s\]/", "<s>\\1</s>", $str);

        $str = preg_replace('/\[url=(.+)](.+)\[\/url]/', '<a href="$1">$2</a>', $str);

        return $str;
    }


    public static function WorkFlowHtmlToBb($str)
    {
        $str = str_replace("\"", "'", $str);
        $str = str_replace(array("<p>", "</p>"), "", $str);
        $str = str_replace("<br>", "\n", $str);
        $str = str_replace("<a href='", "[url=", $str);
        $str = str_replace("' target='_blank'>", "]", $str);
        $str = str_replace("</a>", "[/url]", $str);
        $str = str_replace("<", "[", $str);
        $str = str_replace(">", "]", $str);
        return $str;
    }





    public static function AttestationWFLinkMake($id_course, $id_test, $name_test)
    {
        $link = "https://corp.estelab.ru/services/learning/course.php?COURSE_ID=" . $id_course . "&TEST_ID=" . $id_test . "&PAGE=1";
        $full_link = "[url=$link]" . $name_test . "[/url]";

        return $full_link;
    }

    /*	public static function marketing_utm() 
	{

		// Нужен для запуска БП на элементе маркетинговой метки и запроса по метке отчета

		$today = date("j"); 

		   if($today==1)
		   {

		CModule::IncludeModule("workflow");
		CModule::IncludeModule("bizproc");
		CModule::IncludeModule("intranet"); 
		CModule::IncludeModule("iblock");
			$arSelect = array('ID', 'NAME',"PROPERTY_1095","PROPERTY_1110");//'ID', 'NAME' 
			$arFilter = array("CHECK_PERMISSIONS" => "N",
				'IBLOCK_ID' => 149,
				"PROPERTY_1095" => 984, 
				"PROPERTY_1110" => 1005);


			$res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
			while($ob = $res->GetNextElement())
			 {
				$arFields = $ob->GetFields();

		CBPDocument::StartWorkflow( 
		361, 
		array("lists","Bitrix\Lists\BizprocDocumentLists", $arFields["ID"]),
		array()); 
		}
		   return "CEstelabFunctions::marketing_utm();";
		   }

		else 
		   return "CEstelabFunctions::marketing_utm();";
		   
	} */

    public static function GetDeclNum($value = 1, $status = array('', 'а', 'ов'))
    {
        $array = array(2, 0, 1, 1, 1, 2);
        return $status[($value % 100 > 4 && $value % 100 < 20) ? 2 : $array[($value % 10 < 5) ? $value % 10 : 5]];
    }

    public static function GetToEmailFromLeadCurl($leadId)
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://corp.estelab.ru/mtest.php?id=' . $leadId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }


    public static function GetToEmailFromLead($leadId)
    {

        \Bitrix\Main\Loader::includeModule('mail');
        \Bitrix\Main\Loader::includeModule('crm');

        $filter = array(
            '=CRM_ACTIVITY_OWNER_ID' => $leadId,
            '=CRM_ACTIVITY_OWNER_TYPE_ID' => '1',
            '=MAILBOX_ID' => '55',
        );

        $itemsQuery = \Bitrix\Mail\MailMessageTable::query()
            ->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField(
                'MESSAGE_ACCESS',
                \Bitrix\Mail\Internals\MessageAccessTable::class,
                [
                    '=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
                    '=this.ID' => 'ref.MESSAGE_ID',
                ]
            ))

            ->addSelect('SUBJECT')
            ->addSelect('FIELD_FROM')
            ->addSelect('FIELD_TO')
            //->addSelect(new \Bitrix\Main\Entity\ExpressionField('CRM_ACTIVITY_OWNER_TYPE_ID', 'MAX(%s)', 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_TYPE_ID'))
            //->addSelect(new \Bitrix\Main\Entity\ExpressionField('CRM_ACTIVITY_OWNER_ID', 'MAX(%s)', 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_ID'))
            ->addSelect(new \Bitrix\Main\Entity\ExpressionField('CRM_ACTIVITY_OWNER_TYPE_ID', '%s', 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_TYPE_ID'))
            ->addSelect(new \Bitrix\Main\Entity\ExpressionField('CRM_ACTIVITY_OWNER_ID', '%s', 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_ID'))
            ->setFilter($filter);

        \Bitrix\Main\Application::getConnection()->startTracker(false);
        $rs = $itemsQuery->exec();
        \Bitrix\Main\Application::getConnection()->stopTracker();
        $sql = $rs->getTrackerQuery()->getSql();


        $items = $rs->fetchAll(\Bitrix\Main\Text\Converter::getHtmlConverter());

        //$connection = \Bitrix\Main\Application::getConnection();
        //$recordset = $connection->query($sql);

        //$items = $recordset->fetchAll(\Bitrix\Main\Text\Converter::getHtmlConverter());

        //\Bitrix\Main\Diag\Debug::writeToFile(array('leadId' => $leadId, 'items' => $items, 'sql' => $sql),"","mail_debug.txt");

        if (!empty($items)) {

            return self::ParseEmailToAddress($items[0]['FIELD_TO']);

            // $rfc_email_string = $items[0]['FIELD_TO'];
            // $rfc_email_string = strtolower(htmlspecialcharsBack($rfc_email_string));

            // $arr = explode(">", $rfc_email_string, 2);

            // if(count($arr) > 1) {

            // 	$rfc_email_string = $arr[0].'>';
            // 	preg_match('/(?:<)(.+)(?:>)$/iu', $rfc_email_string, $matches);

            // 	if ($matches[1] != '') return $matches[1];
            // }

            // return $rfc_email_string;

        }

        return '';
    }

    public static function ParseEmailToAddress($fieldValue)
    {

        $rfc_email_string = $fieldValue;
        $rfc_email_string = strtolower(htmlspecialcharsBack($rfc_email_string));

        $arr = explode(">", $rfc_email_string, 2);

        if (count($arr) > 1) {

            $rfc_email_string = $arr[0] . '>';
            preg_match('/(?:<)(.+)(?:>)$/iu', $rfc_email_string, $matches);

            if ($matches[1] != '') return $matches[1];
        }

        return $rfc_email_string;
    }

    /*
		public static function OnMessageAccessTableAddHandler(\Bitrix\Main\Event $event) {
	
			\Bitrix\Main\Loader::includeModule('crm');
			\Bitrix\Main\Loader::includeModule('mail');
			\Bitrix\Main\Loader::includeModule('bizproc');
	
			$eventFields = $event->getParameter('fields');
			// \Bitrix\Main\Diag\Debug::writeToFile($eventFields, "", "mailat_debug.txt");
	
			if($eventFields['ENTITY_TYPE'] != 'CRM_ACTIVITY') {
				return;
			}
	
			// Вычисление адреса "Кому"
	
			$emailToAddress = '';
	
			$itemsQuery = \Bitrix\Mail\MailMessageTable::query()
				->addSelect('SUBJECT')
				->addSelect('FIELD_FROM')
				->addSelect('FIELD_TO')
				->setFilter(array(
					'ID' => $eventFields['MESSAGE_ID'],
				));
			
			$rs = $itemsQuery->exec();
			$items = $rs->fetchAll(\Bitrix\Main\Text\Converter::getHtmlConverter());
	
			if(!empty($items)) {
				$emailToAddress = self::ParseEmailToAddress($items[0]['FIELD_TO']);
			}
			
			// Вычисление ID лидов по ID дела
	
			$actID = $eventFields['ENTITY_ID'];
			$leadIDs = [];
	
			$actBinds = CCrmActivity::GetBindings($actID);
	
			foreach($actBinds as $bind)	{
				
				if($bind['OWNER_TYPE_ID'] != CCrmOwnerType::Lead) continue;
	
				$leadIDs[] = $bind['OWNER_ID'];
	
			}
	
			// Запуск БП Маршрутизации на лидах
	
			foreach($leadIDs as $leadId)	{
				
				$workflowTemplateId = '1098'; 
				$arErrors = array();
				
				$wfId = CBPDocument::StartWorkflow( 
					$workflowTemplateId, 
					array('crm', 'CCrmDocumentLead', 'LEAD_'.$leadId), 
					array('email_to_address' => $emailToAddress), 
					$arErrors 
				); 
	
			}
	
		}
	*/

    // Схлопывает два контакта
    public static function MergeContacts($seedId, $targetId)
    {

        // Код из /bitrix/components/bitrix/crm.dedupe.list/ajax.php

        $seedEntityID = (int)$seedId;
        $targEntityID = (int)$targetId;

        if ($seedEntityID <= 0 || $targEntityID <= 0) {
            return false;
        }

        \Bitrix\Main\Loader::IncludeModule('crm');

        $entityTypeID = CCrmOwnerType::Contact;
        $typeID = \Bitrix\Crm\Integrity\DuplicateIndexType::UNDEFINED;
        $matches = array();
        $criterion = \Bitrix\Crm\Integrity\DuplicateManager::createCriterion($typeID, $matches);
        $currentUserID = 0;
        $enablePermissionCheck = false;

        $merger = \Bitrix\Crm\Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);

        try {
            $merger->merge($seedEntityID, $targEntityID, $criterion);
        } catch (\Bitrix\Crm\Merger\EntityMergerException $e) {
            //print_r($e);
            return false;
        } catch (Exception $e) {
            //print_r($e->getMessage());
            return false;
        }

        return true;
    }

    public static function GetFullName($id)
    {
        $fullName = '';

        $rsUser = CUser::GetByID($id);
        $arUser = $rsUser->Fetch();

        $fullName = $arUser['LAST_NAME'] . ' ' . $arUser['NAME'] . ' (' . $id . ')';

        return $fullName;
    }

    public static function OnBeforeCrmLeadUpdateHandler(&$arFields)
    {
        \Bitrix\Main\Loader::includeModule('crm');

        $beforeResponsibleName = static::GetFullName($arFields['MODIFY_BY_ID']);
        $afterResposibleName = static::GetFullName($arFields['ASSIGNED_BY_ID']);

        if (\Bitrix\Main\Loader::includeModule('im')) {
            if ($arFields['MODIFY_BY_ID'] != $arFields['ASSIGNED_BY_ID'] && $arFields['ASSIGNED_BY_ID'] > 0 && $arFields['MODIFY_BY_ID'] != 0) {
                \CIMChat::AddMessage(array(
                    'FROM_USER_ID' => 587,  //id автора
                    'TO_CHAT_ID' => 73476,  //id чата
                    'MESSAGE' => 'Ответственный за лид #' . $arFields['ID'] . ' был(а) ' . $beforeResponsibleName . ' стал(a) ' . $afterResposibleName
                ));
            }
        }
        //Debug::writeToFile($arFields, "FIELDS", "est_CRM.txt");
    }

    // Событие перед обновлением задачи
    public static function OnBeforeTaskUpdateAddHandler($id, &$arTask, &$copy)
    {
        global $USER;
        $id_group = $copy['GROUP_ID'];

        if (is_null($arTask['RESPONSIBLE_ID'])) {
            self::$IS_MY_TASKS = true;
        } else {
            self::$IS_MY_TASKS = false;
        }


        // Если не указана группа
        if (isset($copy['GROUP_ID']) && $id_group === 0 && $arTask['RESPONSIBLE_ID'] != $USER->GetID()) {

            // if (isset($arTask['AUDITORS']) && !in_array($arTask['RESPONSIBLE_ID'], $arTask['AUDITORS']))
            // {
            // 	$arTask['AUDITORS'][] = $arTask['RESPONSIBLE_ID'];
            // }

            $arTask['RESPONSIBLE_ID'] = $USER->GetID();
            $arTask['IS_CHECK_GROUP'] = true;
            // throw new \Bitrix\Tasks\ActionFailedException("Укажите группу к которой относиться задача");
        }
    }

    // Событие после обновления задачи
    public static function OnTaskUpdateHandler($id_task, &$data)
    {
        global $USER;

        if (
            $data['IS_CHECK_GROUP'] &&
            !self::$IS_CHECK_GROUP_CREATE &&
            !self::$IS_CHECK_GROUP_UPDATE &&
            !self::$IS_MY_TASKS
        ) {

            unset($data['IS_CHECK_GROUP']);
            self::$IS_CHECK_GROUP_UPDATE = true;
            $comment = 'Так как [B]в задаче не установлена группа[/B], задача автоматически делегирована на поставщика. [B]Укажите группу[/B], к которой относится задача и затем делегируйте на ответственного.';
            self::checkGroupId($id_task, $comment);
            self::$IS_CHECK_GROUP_UPDATE = false;
        }
    }

    // Событие перед созданием задачи
    public static function OnBeforeTaskAddHandler(&$arTask)
    {
        global $USER;
        $id_group = $arTask['GROUP_ID'];

        // Если не указана группа
        if (isset($arTask['GROUP_ID']) && $id_group == 0 && $arTask['RESPONSIBLE_ID'] != $USER->GetID()) {
            if (isset($arTask['RESPONSIBLE_ID'])) {
                if (!in_array($arTask['RESPONSIBLE_ID'], $arTask['AUDITORS'])) {
                    $arTask['AUDITORS'][] = $arTask['RESPONSIBLE_ID'];
                }
            }
            $arTask['RESPONSIBLE_ID'] = $USER->GetID();
            self::$IS_CHECK_GROUP_CREATE = true;
            // throw new \Bitrix\Tasks\ActionFailedException("Укажите группу к которой относиться задача");
        }
    }

    public static function onCrmDynamicItemUpdateHandler()
    {
        // $numargs = func_num_args();
        // AddMessage2Log($numargs);
    }

    // Событие после добавления задачи в задачнике на портале для установки OKR признаков
    public static function OnTaskAddHandler($id_task, &$data)
    {
        if (self::$IS_CHECK_GROUP_CREATE) {
            $comment = 'Так как [B]в задаче не установлена группа[/B], задача автоматически делегирована на поставщика. [B]Укажите группу[/B], к которой относится задача и затем делегируйте на ответственного.';
            self::checkGroupId($id_task, $comment);
            self::$IS_CHECK_GROUP_CREATE = false;
        }

        CModule::IncludeModule("tasks");
        $rsTask = CTasks::GetByID($id_task);
        if ($arTask = $rsTask->GetNext()) {
            if (is_array($arTask['UF_CRM_TASK']) || is_object($arTask['UF_CRM_TASK'])) {
                foreach ($arTask['UF_CRM_TASK'] as $value) {
                    if (strstr($value, 'Ta1_')) {
                        $title = $arTask['TITLE'];
                    }
                }
            }
        }
        if ($title) {
            if (substr($title, 0, 4) == "CRM:")
                $title = 'OKR =' . substr($title, 4);
            else
                $title = 'OKR =' . $title;

            $arFields = array(
                'TITLE' => $title
            );
            $obTask = new CTasks;
            $success = $obTask->Update($id_task, $arFields);

            $res = CTaskTags::GetList(
                array(),
                array("TASK_ID" => $id_task)
            );

            while ($arTag = $res->GetNext()) {
                $arTags[] = $arTag["NAME"];
            }

            $USER_ID = 1;
            $arTags[] = "OKR";
            CTasks::AddTags($id_task, 1, $arTags);
        }
    }


    // проверка на наличие группы в задаче
    public static function checkGroupId($id_task, $comment)
    {
        if (!$id_task || !CModule::IncludeModule("tasks") || !$comment)
            return;

        CModule::IncludeModule("tasks");
        $id_bot = 31137; //Бот Дозорный
        $rsTask = CTasks::GetByID($id_task);

        if ($arTask = $rsTask->GetNext()) {
            $id_group = $arTask['GROUP_ID'];

            // Если не указана группа
            if ($id_group == 0) {

                $oTaskItem = CTaskItem::getInstance($id_task, $id_bot);
                $fields = array(
                    'AUTHOR_ID' => $id_bot, // Идентификатор пользователя, от имени которого создается комментарий.
                    'USE_SMILES' => 'N',  // (Y|N) - парсить или нет комментарии на наличие смайлов.
                    'POST_MESSAGE' => $comment, // Текст сообщения.
                    'FILES' => array(), // Массив файлов.
                    'AUX' => 'N', // (Y|N) - обновить модуль статистики и отправить сообщение о новом сообщении получателям.
                );

                \CTaskCommentItem::add($oTaskItem, $fields);
            }
        }
    }


    public static function OnAfterEntityMergeHandler(\Bitrix\Main\Event $event)
    {

        /*
		 array(
                'entityTypeID' => $this->entityTypeID,
                'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeID),
                'seedEntityID'  => $seedID,
                'targetEntityID' => $targID,
                'userID' => $this->getUserID()
			)
		*/

        $connectorId = 'estelabERP'; // не менять!
        $appId = 1; // не менять!

        $entityTypeID = $event->getParameter('entityTypeID');

        if ($entityTypeID !== CCrmOwnerType::Contact) {
            return;
        }

        $seedID = $event->getParameter('seedEntityID');
        $targID = $event->getParameter('targetEntityID');

        $session = \Bitrix\Rest\Event\Session::get();
        $userId = 0;

        $authData = null;

        $dbRes = \Bitrix\Rest\AppTable::getById($appId);
        $application = $dbRes->fetch();

        $authData = array(
            \Bitrix\Rest\Event\Session::PARAM_SESSION => $session,
            \Bitrix\Rest\OAuth\Auth::PARAM_LOCAL_USER => $userId,
            "application_token" => \CRestUtil::getApplicationToken($application),
        );

        $fields = array(
            'CONNECTOR_ID' => $connectorId,
            'APP_ID' => $appId,
            'EVENT_NAME' => 'ONESTELABCRMCONTACTMERGE',
            'EVENT_DATA' => array('FIELDS' => array('DELETED_ID' => $seedID, 'ORIGINAL_ID' => $targID)),
            'EVENT_ADDITIONAL' => $authData,
        );

        \Bitrix\Rest\EventOfflineTable::callEvent($fields);
    }

    // Корректно удаляет запущенный БП
    public static function RemoveWorkflowCorrectly($workflowId)
    {

        $workflowId = trim($workflowId);
        if (strlen($workflowId) <= 0)
            throw new Exception("workflowId");

        CModule::IncludeModule('bizproc');
        global $DB;

        // удаляем из b_bp_task	
        CBPAllTaskService::DeleteAllWorkflowTasks($workflowId);

        // удаляем из b_bp_workflow_state	
        CBPAllStateService::DeleteWorkflow($workflowId);

        // удаляем из b_bp_workflow_instance	
        $DB->Query("DELETE FROM b_bp_workflow_instance WHERE ID = '" . $DB->ForSql($workflowId) . "'");
    }




    // АГЕНТЫ (из папки lib/)

    // Каждые 30 минут проверяет не обработанные лиды по дате запланированных дел по нему
    public static function lead_tasks()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // CRM выгрузка прав доступа
    public static function crm_perms()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверяет элементы списка "Служебный спаисок командировка" и запускает на них бизнесс-процесс
    public static function business_trip_check_element()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Создает элемент в списке Заказ фруктов и запускает на нем БП. 
    public static function fruits_ordering()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // запускается каждые 10 дней и проверяет кому надо сделать ТО
    public static function technical_service()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Создает новую сделку по ремонту через 10 месяцев после последней сделки ТО
    public static function fix_support()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка срока годности документов сотрудников
    public static function employee_docs()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка отзывов каждые 7 дней
    public static function company_feedback()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Постановка задачи покупки подарка на день рождение за 10 дней
    public static function birthday()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Ежедневная проверка % брака
    public static function spoilage_k()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }





    // Запись в реестр Анализ базы клиентов бренда Красивое лицо всегда
    public static function dynamic_base()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Агент по инветаризации производства
    public static function inventarization_producing()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // 1 числа месяца запускать БП по начислению премии руководителем
    public static function non_standart_motivation()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }


    // 1 числа месяца запускать БП по отчетам руководителей
    public static function month_report()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // 	Установка статуса контакта 
    public static function status_customer()
    {

        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Курс валют ежедневно
    public static function currancy_daily()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Отчет по задачам сотрудинков
    public static function doings_daily()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка входящей почты (костыль после обновления битрикса)
    public static function check_email()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Запуск 5-го числа каждого месяца планирование на 15 месяцев
    public static function plan_15()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Помощник продаж по созданию повторных сделок
    public static function sale_helper()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Еженедельный расчет с внешним сотрудником
    public static function ex_employer_fee()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Анализ ценообразования 
    public static function price_list_analize()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Отчет преподавателя 
    public static function teacher_month_report()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Контроль лимитов обслуживающих компаний
    public static function limit_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Контроль маркетинговой активности
    public static function marketing_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Анализ вх и исх звонков менеджеров направлений
    public static function calls_analize()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Аттестация сотрудников 

    public static function attestation()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Контроль просроченных собраний и планерок

    public static function meeting_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка просроченных отложенных задач

    public static function deferred_overdue_task()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Контроль зависания агента проверки почты

    public static function email_agent_watchdog()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Провека контактов (Мастера ПМ) без компании на стадию конвейера продаж

    public static function conveer_stage_contact_without_company()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }
    // Провека у Мастеров ПМ стадиии и отв у контактов с компанией 

    public static function conveer_stage_contact_with_company()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка заполненности уровня мастера ПМ у контактов отдела продаж

    public static function customer_checker()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Проверка ответственных за направление

    public static function business_responsibles()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }
    // Посылать отчет каждый понедельник косметологам по сданным статьям

    public static function weekly_artical_alarm()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }



    // еженедельный отчет по полезным действиям маркетинга

    public static function goals_marketing()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }
    // контроль подготовки компании к праздникам в РФ

    public static function holidays_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }


    // контроль сертификатов на продукцию
    public static function certification_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // запуск актуализации целей
    public static function targets_checking()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // проверяет актуальность прайс-листа поставщиков
    public static function suppliers_price_list_actualization()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // проверяет актуальность договоров  
    public static function contract_checker()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // проверяет ежедневно кол-во инвестиций
    public static function marketing_investment()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // проверяет ежедневно задания уволенных сотрудников и переводит их на руководителей
    public static function take_tasks_from_fired_employess()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // проверяет просроченные пароли
    public static function password_actulization()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // отправляет сообщения по отправки закрывающих документов
    public static function get_closed_doc()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // обрабатывает обучение сотрудника (ежемесячный отчет, диплом и дипломная работа)
    public static function employee_education_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }


    // проверка и удаление тел и эл почты в каждой карточке контакт и компании на портале
    public static function check_crm_entity_double_contact_info()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }



    // Проверять каждые 10 минут что бы задачи стояли на контроле постанощица
    public static function check_task_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Каждые 3 часа проверяет актуальность списка Реестр курсов обучения 
    public static function check_courses_list()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Запускать каждые 30 дней проверку прайс листа
    public static function pl_control()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Контрол ТО оборудования control_to_devices.php
    public static function control_to_devices()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Запрашивает прогресс по OKR
    public static function okr_progress()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // запускать проверку аттестации на Путь сотруднике смарт процессе 
    public static function attestation_control_v2()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    //запускает создание случайных пар сотрудников для встречи за кофе
    public static function coffee()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    //запускает создание элементов OKR выравнивание по понедельникам
    public static function okr_purposes()
    {
        static::include_current_method_file(__FUNCTION__);
        return __METHOD__ . '();';
    }

    // Удалить кавычки
    public static function quotes($str)
    {
        $str = preg_replace("/[^a-zA-ZА-Яа-я0-9.,:_\-\/;=?!\s]/u", "", $str);
        //$remove = array ("&#039;", "&quot;");
        //$str = str_replace($remove, '', $str);
        //$str = htmlspecialchars($str, ENT_QUOTES);
        //$str = strip_tags($str);
        //$remove = array ("&#039;", "&quot;");
        //$str = str_replace($remove, '', $str);
        return $str;
    }



    // определить федеральный округ
    public static function fed_okrug($address_entity)
    {
        $array_regions = [];

        $array_regions[344993]['regions'] = array('Белгородская область', 'Брянская область', 'Владимирская область', 'Воронежская область', 'Ивановская область', 'Калужская область', 'Костромская область', 'Курская область', 'Липецкая область', 'Московская область', 'Орловская область', 'Рязанская область', 'Смоленская область', 'Тамбовская область', 'Тверская область', 'Тульская область', 'Ярославская область', 'Москва');
        $array_regions[344993]['fed_okrug'] = 'Центральный';

        $array_regions[344994]['regions'] = array('Республика Карелия', 'Республика Коми', 'Архангельская область', 'Вологодская область', 'Калининградская область', 'Ленинградская область', 'Мурманская область', 'Новгородская область', 'Псковская область', 'Санкт-Петербург', 'Санкт Петербург', 'Ненецкий автономный округ');
        $array_regions[344994]['fed_okrug'] = 'Северо-Западный';

        $array_regions[344995]['regions'] = array('Республика Адыгея', 'Республика Калмыкия', 'Республика Крым', 'Краснодарский край', 'Астраханская область', 'Волгоградская область', 'Ростовская область', 'Севастополь', 'Крым', 'Севастополь');
        $array_regions[344995]['fed_okrug'] = 'Южный';

        $array_regions[344996]['regions'] = array('Республика Дагестан', 'Республика Ингушетия', 'Кабардино-Балкарская Республика', 'Карачаево-Черкесская Республика', 'Республика Северная Осетия', 'Чеченская Республика', 'Ставропольский край');
        $array_regions[344996]['fed_okrug'] = 'Северо-Кавказский';


        $array_regions[344997]['regions'] = array('Республика Башкортостан', 'Республика Марий Эл', 'Республика Мордовия', 'Республика Татарстан', 'Удмуртская Республика', 'Чувашская Республика', 'Пермский край', 'Кировская область', 'Нижегородская область', 'Оренбургская область', 'Пензенская область', 'Самарская область', 'Саратовская область', 'Ульяновская область');
        $array_regions[344997]['fed_okrug'] = 'Приволжский';

        $array_regions[344998]['regions'] = array('Курганская область', 'Свердловская область', 'Тюменская область', 'Челябинская область', 'Ханты-Мансийский автономный округ', 'Ямало-Ненецкий автономный округ');
        $array_regions[344998]['fed_okrug'] = 'Уральский';

        $array_regions[344999]['regions'] = array('Республика Алтай', 'Республика Бурятия', 'Республика Тыва', 'Республика Хакасия', 'Алтайский край', 'Забайкальский край', 'Красноярский край', 'Иркутская область', 'Кемеровская область', 'Новосибирская область', 'Омская область', 'Томская область');
        $array_regions[344999]['fed_okrug'] = 'Сибирский';

        $array_regions[345000]['regions'] = array('Республика Саха', 'Камчатский край', 'Приморский край', 'Хабаровский край', 'Амурская область', 'Магаданская область', 'Сахалинская область', 'Еврейская автономная область', 'Чукотский автономный округ');
        $array_regions[345000]['fed_okrug'] = 'Дальневосточный';

        $array_regions[345001]['fed_okrug'] = 'Другая страна';



        $full_address = mb_strtolower($address_entity);
        if ($full_address) {
            foreach ($array_regions as $key => $value) {
                if ($key == 345001) continue;

                foreach ($value['regions'] as $number => $region_name) {
                    if (stristr($full_address, mb_strtolower($region_name))) {
                        $id_fed_okrug = $key;
                        $fed_okrug_name = $value['fed_okrug'];
                        break;
                        break;
                    }
                }
            }

            if ($id_fed_okrug)     $message = $id_fed_okrug;
            elseif (!$id_fed_okrug && stristr($full_address, 'россия'))
                $message = 0;
            else
                $message = 345001;
            return $message;
        } else
            return false;
    }


    // Классификация телефона и эл почты у сущности CRM
    public static function control_contact_data($ENTITY_ID, $ELEMENT_ID)
    {

        //$ENTITY_ID, $ELEMENT_ID данные передаваемые в функцию
        CModule::IncludeModule('crm');

        // Получение телефонов и эл почты текущего контакта 

        $phonesToCheck = array();
        $emailsToCheck = array();

        $res = CCrmFieldMulti::GetListEx(
            null,
            array(
                'CHECK_PERMISSIONS' => 'N',
                'ENTITY_ID' => $ENTITY_ID,
                'ELEMENT_ID' => $ELEMENT_ID,
                'TYPE_ID' => array('PHONE', 'EMAIL')
            )
        );

        while ($row = $res->Fetch()) {

            $isDuplicatedField = false;

            switch ($row['TYPE_ID']) {

                case 'PHONE':

                    $tel_clean = preg_replace('|[^0-9]*|', '', $row['VALUE']);

                    if (!in_array($tel_clean, $phonesToCheck)) {
                        array_push($phonesToCheck, $tel_clean);
                    } else {
                        $isDuplicatedField = true;
                    }

                    if (!$isDuplicatedField && strlen($tel_clean) >= 10) {

                        if (strlen($tel_clean) == 10 && substr($tel_clean, 0, 1) == '9') {
                            $tel_clean = '7' . $tel_clean;
                        }

                        if (strlen($tel_clean) == 11 && substr($tel_clean, 0, 2) == '89') {
                            $tel_clean = '7' . substr($tel_clean, 1);
                        }

                        $tel_clean = '+' . $tel_clean;

                        $needToUpdate = false;
                        $updateData = array('PHONE' => array());

                        // обновить если отличается
                        if ($tel_clean !== $row['VALUE']) {

                            $row['VALUE'] = $tel_clean;
                            $needToUpdate = true;
                        }

                        $firstTwoSymbols = substr($tel_clean, 1, 2);
                        $isRussianMobile = $firstTwoSymbols == '79';
                        $isKazakhMobile = $firstTwoSymbols == '77';

                        // Проставить тип MOBILE, если русский или казахский номер
                        if (($isRussianMobile || $isKazakhMobile) && $row['VALUE_TYPE'] != 'MOBILE') {

                            $row['VALUE_TYPE'] = 'MOBILE';
                            $needToUpdate = true;
                        }

                        // Проставить тип WORK, если русский но не мобильный
                        if (substr($tel_clean, 1, 1) == '7' && !$isRussianMobile && !$isKazakhMobile && $row['VALUE_TYPE'] == 'MOBILE') {

                            $row['VALUE_TYPE'] = 'WORK';
                            $needToUpdate = true;
                        }

                        if ($needToUpdate) {
                            $updateData['PHONE'][$row['ID']] = $row;
                            $multi = new CCrmFieldMulti();
                            $multi->SetFields('CONTACT', $row['ELEMENT_ID'], $updateData);
                        }
                    } else {
                        // $isDuplicatedField = true;    
                    }


                    break;

                case 'EMAIL':

                    $email_clean = mb_strtolower($row['VALUE']);

                    if (!in_array($email_clean, $emailsToCheck)) {
                        array_push($emailsToCheck, $email_clean);
                    } else {
                        $isDuplicatedField = true;
                    }

                    if (!$isDuplicatedField) {

                        $needToUpdate = false;
                        $updateData = array('EMAIL' => array());

                        // обновить если отличается
                        if (strcmp($email_clean, $row['VALUE']) !== 0) {

                            $row['VALUE'] = $email_clean;
                            $needToUpdate = true;
                        }

                        if ($needToUpdate) {
                            $updateData['EMAIL'][$row['ID']] = $row;
                            $multi = new CCrmFieldMulti();
                            $multi->SetFields('CONTACT', $row['ELEMENT_ID'], $updateData);
                        }
                    }



                    break;
            }

            // Удаляем дубликаты контактной информации

            if ($isDuplicatedField) {
                $multi = new CCrmFieldMulti();
                $multi->Delete($row['ID']);
            }
        }

        return;
    }

    /* Функция возвращает рандомного сотрудника */
    public static function randomEmployee($idUser, $arFullDepartments)
    {
        $idDeratment = self::idDepartment($idUser);

        if (array_key_exists($idUser, $arFullDepartments)) {
            $key = array_search('green', $arFullDepartments['ID']);
            AddMessage2Log($arFullDepartments[$idDeratment]);
        }
    }

    // Получим ID Отдела сотрудника
    private function idDepartment($idUser)
    {
        $rsUsers = CUser::GetList(array(), array(), array("ID" => $idUser), array("SELECT" => array("UF_DEPARTMENT")));

        while ($arUser = $rsUsers->Fetch()) {
            $idDeratment = $arUser['UF_DEPARTMENT'][0];
        }
        return $idDeratment;
    }
}
