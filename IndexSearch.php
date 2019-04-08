<?php

//class searchItemResultOwn extends searchBaseClass{
//
//    protected $type = "own";
//
//    public static function getInstance($c = NULL) {
//        return parent::getInstance(__CLASS__);
//    }
//
//    public function getTotal(){
//        $connection = ConnectionPool::getInstance()->getConnection();
//
//        try{
//            $result = $connection->queryResult("SELECT FOUND_ROWS() as 'count';");
//        } catch (databaseException $e) {
//            throw new publicAdminException(__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage());
//        }
//
//        $result = $result->fetch();
//        if(empty($result)) return false;
//
//        if(!isset($result['count'])) return false;
//
//        return $result['count'];
//    }
//
//    public function result(){
//        $umiH = umiHierarchy::getInstance();
//        $baseTypeId = $this->getBaseTypeId();
//        $fields = $this->getSearchFields();
//
//        $q = $this->q;
//
//        $replaceArg = create_function('$a', 'return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($a, \"-\", \"\"), \" \", \"\"), \"III\", 3), \"IV\", 4), \"/\", \"\"), \",\", \".\"), \"*\", \"\"), \"#\", \"\")";');
//
//        $offset = $this->getOffset();
//        $limit = $this->per_page;
//
//        $sql = "
//            SELECT
//                DISTINCT SQL_CALC_FOUND_ROWS h.id as id,
//                h.rel as pid
//	        FROM
//	            cms3_hierarchy h,
//	            cms3_objects o
//	        LEFT JOIN
//                cms3_object_content o_asteriks_varchar ON o_asteriks_varchar.obj_id=o.id
//                AND o_asteriks_varchar.varchar_val IS NOT NULL
//            LEFT JOIN
//                cms3_object_content o_asteriks_text ON o_asteriks_text.obj_id=o.id
//                AND o_asteriks_text.text_val IS NOT NULL
//	        WHERE
//	            h.type_id IN ($baseTypeId) AND h.domain_id = '1'
//	            AND h.lang_id = '1'
//	            AND h.is_deleted = '0'
//	            AND h.is_active = '1'
//	            AND (
//	                ".$replaceArg('o_asteriks_varchar.varchar_val')." LIKE '%$q%'
//	                OR ".$replaceArg('o_asteriks_text.text_val')." LIKE '%$q%'
//	                OR ".$replaceArg('o.name')." LIKE '%$q%'
//	              )
//	            AND h.obj_id = o.id
//	        ORDER BY h.ord ASC
//	        LIMIT $offset, ".($offset+$limit);
//
//        $connection = ConnectionPool::getInstance()->getConnection();
//        try {
//            $result = $connection->queryResult($sql);
//        } catch (databaseException $e) {
//            throw new publicAdminException(__METHOD__ . ': MySQL exception has occurred:' . $e->getCode() . ' ' . $e->getMessage());
//        }
//        /**
//         * @var $result mysqliQueryResult
//         */
//
//        $this->total = $this->getTotal();
//
//        $result->setFetchType(IQueryResult::FETCH_ASSOC);
//
//        $ar = array();
//        foreach($result as $row){
//
//            $page = $umiH->getElement($row['id']);
//
//            if($page->getValue("price") <= 0){
//                $this->total--;
//                continue;
//            }
//
//            $t = array(
//                "id"=>$row['id'],
//                "providerType"=>key($this->getGuidTypeId($this->type)),
//                "link"=>$page->link,
//                "count"=>$page->getValue("common_quantity"),
//                "price"=>$page->getValue("price"),
//                "name"=>$page->getName(),
//                "photo"=>false
//            );
//
//
//            $photo = $page->getValue("photo");
//            if($photo instanceof umiImageFile) $t['photo'] = $photo->getFilePath(true);
//
//            foreach($fields as $f){
//                $t['searchFields'][$f['name']] = array("name"=>$f['title'], "value"=>$page->getValue($f['name']));
//            }
//
//            $t = $this->renderItemSearchData($t);
//            $ar[] = $t;
//        }
//
//        return array("nodes:item"=>$ar);
//    }
//}

if(!function_exists('replaceArg')){
    function replaceArg($a){
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($a, \"-\", \"\"), \" \", \"\"), \"III\", 3), \"IV\", 4), \"/\", \"\"), \",\", \".\"), \"*\", \"\"), \"#\", \"\")";
    }
}

class IndexSearch{

    CONST FIELD_GROUP_TO_SEARCH = 'harakteristiki';

    public static $groupDiscount = [
        42075=>0.95,
        42076=>0.90,
        42077=>0.85,
        42078=>0.80
    ];

    public static function getIndexId(){
        $categories = new selector('pages');
        $categories->types('object-type')->name('catalog', 'category');
        $categories->where('index_choose')->equals(true);
        $indexCatalogId = $categories->result()[0]->id;

        return $indexCatalogId;
    }

    public static function reIndexAll(){
        $indexGenerator = new FilterIndexGenerator(umiHierarchyTypesCollection::getInstance()->getTypeByName('catalog', 'object')->getId(), 'pages');
        $indexGenerator->setHierarchyCondition(self::getIndexId(), 100);
        $indexGenerator->setLimit(100);
        for ($i = 0; !$indexGenerator->isDone(); $i++) {
            echo $i.PHP_EOL;
            $indexGenerator->run();
        }

        $tableName = $indexGenerator->getTableName();

        $connection = ConnectionPool::getInstance()->getConnection();
        $connection->queryResult('ALTER TABLE '.$tableName.' ADD `name` VARCHAR(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $connection->queryResult('ALTER TABLE '.$tableName.' ADD `link` VARCHAR(2048) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $connection->queryResult('UPDATE '.$tableName.' as t1 SET t1.name = (SELECT name FROM cms3_objects WHERE id = t1.obj_id)');

        $result = $connection->queryResult('SELECT * FROM '.$tableName);
        $result->setFetchType(IQueryResult::FETCH_ASSOC);

        foreach($result as $key=>$item){
            $link = umiHierarchy::getInstance()->getPathById($item['id']);
            $connection->queryResult("UPDATE ".$tableName." SET link = '".$link."' WHERE id = ".$item['id']);
            echo "Обновляем ссылки в индексе ".$key.PHP_EOL;
        }

        return true;

    }

    public static function getSearchString(){
        $search_string = getRequest('q');
        $search_string = urldecode($search_string);
        $search_string = htmlspecialchars($search_string);
        $search_string = str_replace(". ", " ", $search_string);
        $search_string = trim($search_string, " \t\r\n%");
        $search_string = str_replace(array('"', "'"), "", $search_string);
        #$search_string = l_mysql_real_escape_string($search_string);

        $search_string = str_replace(array("-", " ", "/", "*", "#"), "", $search_string);
        $search_string = str_replace(array("III", "IV", ","), array("3", "4", "."), $search_string);
        return $search_string;
    }

    public static function getDiscountToCurrentUser(){

        $user = permissionsCollection::getInstance()->getUserId();
        if(!$user = umiObjectsCollection::getInstance()->getObject($user)){
            return 1;
        }

        $userGroups = array_flip($user->groups);
        $groupWithDiscount = array_intersect_key(self::$groupDiscount, $userGroups);

        if(!$groupWithDiscount){
            return 1;
        }

        $discount = min($groupWithDiscount);
        return $discount;
    }

    public static function doSearch(){
        if(!self::getSearchString()){
            return false;
        }

        $indexGenerator = new FilterIndexGenerator(umiHierarchyTypesCollection::getInstance()->getTypeByName('catalog', 'object')->getId(), 'pages');
        $indexGenerator->setHierarchyCondition(self::getIndexId(), 0);
        $tableName = $indexGenerator->getTableName();

        $umiOTC = umiObjectTypesCollection::getInstance();
        $typeId = $umiOTC->getTypeIdByHierarchyTypeName('catalog', 'object');
        $type = $umiOTC->getType($typeId);
        $group = $type->getFieldsGroupByName(self::FIELD_GROUP_TO_SEARCH);
        $fields = $group->getFields();
        $fieldsNames = [];

        $discountUser = self::getDiscountToCurrentUser();


        foreach($fields as $field){
            /**
             * @var $field umiField
             */
            if(!$field->getIsVisible() || !$field->isImportant()){
                continue;
            }
            $fieldsNames[] = $field->getName();
        }

        $sql = "
            SELECT 
                DISTINCT SQL_CALC_FOUND_ROWS *, price*".$discountUser." as 'price'
            FROM $tableName
            WHERE ( 
        ";

        foreach($fieldsNames as $fieldName){
            $sql .= replaceArg($fieldName)." LIKE '%".self::getSearchString()."%' OR ";
        }
        $sql = substr($sql, 0, -3);
        $sql .= ')';
        $sql .= " AND price > 0 ORDER BY price ASC LIMIT 0,25";

        $connection = ConnectionPool::getInstance()->getConnection();
        $result = $connection->queryResult($sql);
        $result->setFetchType(IQueryResult::FETCH_ASSOC);


        $ar = array();
        foreach($result as $row){
            $ar[] = $row;
        }

        return $ar;



    }

    public static function updateElementFromIndex(umiHierarchyElement $element){
        $object = $element->getObject();
        $objectTypeId = $element->getObjectTypeId();

        if(!$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId)){
            return false;
        }

        if(!$fields = $objectType->getAllFields()){
            return false;
        }

        $indexGenerator = new FilterIndexGenerator(umiHierarchyTypesCollection::getInstance()->getTypeByName('catalog', 'object')->getId(), 'pages');
        $indexGenerator->setHierarchyCondition(self::getIndexId(), 100);
        $tableName = $indexGenerator->getTableName();

        $sql = "UPDATE $tableName SET ";

        $data = [];
        foreach($fields as $field){
            if(!$field->getIsInFilter()){
                continue;
            }

            if(!$field->getIsVisible()){
                continue;
            }

            $value = $element->{$field->getName()};
            $data[] = "`".$field->getName()."`='".$value."'";
        }
        $data[] = "`link`='".$element->link."'";

        $sql = $sql.implode(', ', $data).' WHERE obj_id = '.$object->id;

        $connection = ConnectionPool::getInstance()->getConnection();
        $result = $connection->queryResult($sql);

        return true;
    }



}