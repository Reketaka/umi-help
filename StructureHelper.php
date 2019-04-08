<?php

class StructureHelper{

    public static function createElement($data){
        $hierarchyTypes = umiHierarchyTypesCollection::getInstance();
        $hierarchyType = $hierarchyTypes->getTypeByName($data['module'], $data['method']);
        $hierarchyTypeId = $hierarchyType->getId();

        $heirarchy = umiHierarchy::getInstance();
        /**
         * @var $heirarchy umiHierarchy
         */

        if(isset($data['checkExist']) && ($data['checkExist'] instanceof \Closure) && ($element = $data['checkExist']())){
            echo "Страница уже была создана".PHP_EOL;
            return $element;
        }

        if (!$newElementId = $heirarchy->addElement($data['rootPageId'], $hierarchyTypeId, $data['title'], $data['title'], $data['typeId'])) {
            return false;
        }

        $permissions = permissionsCollection::getInstance();
        $permissions->setDefaultPermissions($newElementId);

        if (!$newElement = $heirarchy->getElement($newElementId)) {
            return false;
        }

        $newElement->setValue('h1', $title);
        $newElement->setValue("publish_time", time());
        $newElement->setIsActive(true);
        $newElement->setIsVisible(true);

        $newElement->commit();

        echo "Страница создана $newElement".PHP_EOL;
        return $newElement;
    }

    /**
     * Удаляет всех пользователей
     * @throws selectorException
     */
    public function deleteUsers(){
        $users = new selector('objects');
        $users->types('object-type')->name('users', 'user');
        $users->where('login')->notequals(['admin', 'Гость']);

        $users = $users->result();

        foreach($users as $user){
            $user->delete();
        }

        var_Dump(count($users));
    }

    /**
     * Удаляет все страницы с базовым типом данных ['module'=>'catalog', 'method'=>'object']
     * @param $data
     * @throws selectorException
     */
    public static function deletePages($data){
        $objects = new selector('pages');
        $objects->types('hierarchy-type')->name($data['module'], $data['method']);


        $result = $objects->result();

        echo "Всего страниц ".$objects->length().PHP_EOL;

        foreach($result as $r){

            echo "Страница удалена с Id ".$r->getId().PHP_EOL;

            $r->delete();
        }

        echo "Очищаем корзину".PHP_EOL;
        umiHierarchy::getInstance()->removeDeletedAll();

    }

    /**
     * Удаляет все дочерние типы данных от указанного
     */
    public static function deteleTypesObject($module, $method){
        $typesCollection = umiObjectTypesCollection::getInstance();
        $typeId = $typesCollection->getTypeIdByHierarchyTypeName($module, $method);


        $childrens = $typesCollection->getChildTypeIds($typeId);

        echo $typeId.PHP_EOL;

        foreach($childrens as $childrenId){


            $typesCollection->delType($childrenId);

            echo "Тип с $childrenId удален".PHP_EOL;

        }
    }

}