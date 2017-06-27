<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 15.06.2017
 * Time: 20:19
 */

class BXIblockActions {
    protected $successAr = array();
    protected $errorAr = array();
    protected $showResult = false;
    static protected $instance = null;

    function output($showOutput = true)
    {
        BXIblockActionsNoticement::getInstance()->output($showOutput);
        return $this;
    }
    static public function getInstance()
    {
        self::$instance = self::$instance ? self::$instance : new self();
        return self::$instance;
    }
    public function addIblock()
    {
        return new BXIblock();
    }
    public function addProperty()
    {
        return FabricBXProperty::getInstance();
    }
    public function addIblockType()
    {
        return new BXIblockType();
    }

}
class BXIblockActionsNoticement{
    static protected $instance = null;
    private $showResult = false;
    private function __construct(){}
    public function output($showOutput)
    {
        $this->showResult = $showOutput;
    }
    static public function getInstance()
    {
        self::$instance = self::$instance ? self::$instance : new self();
        return self::$instance;
    }
    public function showError($errMsg, $errType = false)
    {
        if ($this->showResult)
            echo '<p><b>ERROR</b> ' . $errMsg . PHP_EOL . '</p>';
    }
    public function showSuccess($msg)
    {
        if ($this->showResult)
            echo '<p><b>SUCCESS</b> ' . $msg . PHP_EOL . '</p>';
    }
}
interface InterfaceBXActions {
    public function add();
    public function getParams();
}
class BXIblockType implements InterfaceBXActions{
    private $params = array();
    public function __construct()
    {
        return $this;
    }
    public function add()
    {
        global $DB;
        $arFields = $this->getParams();
        $obBlocktype = new CIBlockType;
        $DB->StartTransaction();
        $res = $obBlocktype->Add($arFields);
        if (!$res)
            BXIblockActionsNoticement::getInstance()->showError('Iblock type ' . $arFields['NAME'] . ' has not been added. ' . $obBlocktype->LAST_ERROR, 'iblockTypeAdd');
        else
            BXIblockActionsNoticement::getInstance()->showSuccess('Iblock type ' . $arFields['NAME'] . ' has been added');
        $DB->Commit();
        return $res;
    }
    public function code($code)
    {
        $this->params['ID'] = $code;
        return $this;
    }
    public function name($name)
    {
        if (!$this->params['LANG']) $this->addLang();
        $this->params['LANG']['en']['NAME'] = $name;
        return $this;
    }
    public function elementName($ename)
    {
        if (!$this->params['LANG']) $this->addLang();
        $this->params['LANG']['en']['ELEMENT_NAME'] = $ename;
        return $this;
    }
    public function sort($sort)
    {
        $this->params['SORT'] = $sort;
        return $this;
    }
    public function sections()
    {
        $this->params['SECTIONS'] = 'Y';
        return $this;
    }
    private function addLang()
    {
        $this->params['LANG'] = Array(
            'en'=>Array(
                'NAME' => 'name',
                'SECTION_NAME' => 'Sections',
                'ELEMENT_NAME' => 'element'
            )
        );
    }
    public function getParams()
    {
        $defFields = Array(
            'SECTIONS'=>'N',
            'IN_RSS'=>'N',
            'SORT'=>100
        );
        return array_merge($defFields, $this->params);
    }
}
class BXIblock implements InterfaceBXActions{
    private $iblockParams = array();
    public function __construct()
    {
        $this->iblockParams['ACTIVE'] = 'Y';
        $this->iblockParams["SITE_ID"] = SITE_ID;
        return $this;
    }
    public function type($type)
    {
        $this->iblockParams['IBLOCK_TYPE_ID'] = $type;
        return $this;
    }
    public function name($name)
    {
        $this->iblockParams['NAME'] = $name;
        return $this;
    }
    public function code($code)
    {
        $this->iblockParams['CODE'] = $code;
        return $this;
    }
    public function add()
    {
        $params = $this->getParams();
        if ($ID = $this->getIblockIdByCode($params['CODE'])) {
            BXIblockActionsNoticement::getInstance()->showError('Iblock ' . $params['CODE'] . ' already exists. ', 'iblockAddExists');
            return $ID;
        }
        $ib = new CIBlock;
        $ID = $ib->Add($params);
        if (!$ID)
            BXIblockActionsNoticement::getInstance()->showError('Iblock ' . $params['CODE'] . ' has not been added. ' . $ib->LAST_ERROR, 'iblockAdd');
        else
            BXIblockActionsNoticement::getInstance()->showSuccess('Iblock ' . $params['CODE'] . ' has been added');
        return $ID;
    }
    public function getParams()
    {
        return $this->iblockParams;
    }
    public function getIblockIdByCode($code)
    {
        $iblock = CIBlock::GetList(array(), array('CODE' => $code))->fetch();
        return $iblock ? $iblock['ID'] : false;
    }
}
abstract class AbstractBXIblockProperty implements InterfaceBXActions{
    protected $prop = array();
    public function __construct()
    {
        $this->prop['ACTIVE'] = 'Y';
        $this->prop['SORT'] = 100;
    }
    public function getParams()
    {
        return $this->prop;
    }

    public function add()
    {
        $prop = $this->getParams();
        $iblockId = $prop['IBLOCK_ID'];
        if ($pid = $this->checkProp($iblockId, $prop['CODE'])) {
            return $this->updateProperty($iblockId, $pid, $prop);
        } else {
            return $this->addProperty($iblockId, $prop);
        }
    }
    protected function checkProp($iblockId, $propCode)
    {
        return CIBlockProperty::GetList(array(), array('IBLOCK_ID' => $iblockId, 'CODE' => $propCode))->fetch();
    }
    protected function updateProperty($iblockId, $pid, $prop)
    {
        $prop['IBLOCK_ID'] = $iblockId;
        $ibp = new CIBlockProperty;
        $res = $ibp->Update($pid, $prop);
        if ($res) {
            BXIblockActionsNoticement::getInstance()->showSuccess('Property ' . $prop['NAME'] . ' has been updated');
        } else {
            BXIblockActionsNoticement::getInstance()->showError('Property ' . $prop['NAME'] . ' has not been updated' . PHP_EOL . ' ' . $ibp->LAST_ERROR, 'propertyUpdate');
        }
        return $res;
    }
    protected function addProperty($iblockId, $prop)
    {
        $prop['IBLOCK_ID'] = $iblockId;
        $ibp = new CIBlockProperty;
        $PropID = $ibp->Add($prop);
        if ($PropID) {
            BXIblockActionsNoticement::getInstance()->showSuccess('Property ' . $prop['NAME'] . ' has been added');
        } else {
            BXIblockActionsNoticement::getInstance()->showError('Property ' . $prop['NAME'] . ' has not been added' . PHP_EOL . ' ' . $ibp->LAST_ERROR, 'propertyAdd');
        }
        return $PropID;
    }
    public function iblock($iblock)
    {
        $this->prop['IBLOCK_ID'] = $iblock;
        return $this;
    }
    public function sort($sort)
    {
        $this->prop['SORT'] = $sort;
        return $this;
    }
    public function name($name)
    {
        $this->prop['NAME'] = $name;
        return $this;
    }
    public function code($code)
    {
        $this->prop['CODE'] = $code;
        return $this;
    }
    public function requred()
    {
        $this->prop['IS_REQUIRED'] = 'Y';
        return $this;
    }
    public function multiple()
    {
        $this->prop['MULTIPLE'] = 'Y';
    }
}
class BXIblockPropertyString extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "S";
        parent::__construct();
    }
}
class BXIblockPropertyHTMLString extends BXIblockPropertyString {
    public function __construct()
    {
        $this->prop['USER_TYPE'] = 'HTML';
        parent::__construct();
    }
}
class BXIblockPropertyVideo extends BXIblockPropertyString {
    public function __construct()
    {
        $this->prop['USER_TYPE'] = 'video';
        parent::__construct();
    }
}
class BXIblockPropertyNumber extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "N";
        parent::__construct();
    }
}
class BXIblockPropertyList extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "L";
        $this->prop['VALUES'] = array();
        parent::__construct();
    }
    public function value($val, $def, $sort)
    {
        $this->prop["VALUES"][] = Array(
            "VALUE" => $val,
            "DEF" => $def,
            "SORT" => $sort
        );
        return $this;
    }
}
class BXIblockPropertyLinkIblock extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "E";
        parent::__construct();
    }
    public function extIblockId($iblockId)
    {
        $this->prop['LINK_IBLOCK_ID'] = $iblockId;
        return $this;
    }
}
class BXIblockPropertyLinkUser extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "S";
        $this->prop['USER_TYPE'] = 'UserID';
        parent::__construct();
    }
}
class BXIblockPropertyFile extends AbstractBXIblockProperty {
    public function __construct()
    {
        $this->prop["PROPERTY_TYPE"] = "F";
        $this->prop['FILE_TYPE'] = '';
        parent::__construct();
    }
    public function filterDoc()
    {
        $this->prop['FILE_TYPE'] = $this->prop['FILE_TYPE'] ? $this->prop['FILE_TYPE'] . ', doc, txt, rtf' : 'doc, txt, rtf';
    }
    public function filterImage()
    {
        $this->prop['FILE_TYPE'] = $this->prop['FILE_TYPE'] ? $this->prop['FILE_TYPE'] . ', jpg, gif, bmp, png, jpeg' : 'jpg, gif, bmp, png, jpeg';
        $this->prop['FILE_TYPE'] = 'jpg, gif, bmp, png, jpeg';
    }
    public function filterMusic()
    {
        $this->prop['FILE_TYPE'] = $this->prop['FILE_TYPE'] ? $this->prop['FILE_TYPE'] . ', jpg, gif, bmp, png, jpeg' : 'jpg, gif, bmp, png, jpeg';
    }
    public function filterVideo()
    {
        $this->prop['FILE_TYPE'] = $this->prop['FILE_TYPE'] ? $this->prop['FILE_TYPE'] . ', mpg, avi, wmv, mpeg, mpe, flv' : 'mpg, avi, wmv, mpeg, mpe, flv';
    }
    public function filterStr($filterStr)
    {
        $this->prop['FILE_TYPE'] = $this->prop['FILE_TYPE'] ? $this->prop['FILE_TYPE'] . ', ' . $filterStr : $filterStr;
    }
}
class FabricBXProperty {
    private static $instance = null;
    private function __construct(){}
    public static function getInstance()
    {
        if (!self::$instance) self::$instance = new self;
        return self::$instance;
    }
    public function string(){ return new BXIblockPropertyString(); }
    public function stringHTML(){ return new BXIblockPropertyHTMLString(); }
    public function video(){ return new BXIblockPropertyVideo(); }
    public function number(){ return new BXIblockPropertyNumber(); }
    public function typelist(){ return new BXIblockPropertyList(); }
    public function linkIblock(){ return new BXIblockPropertyLinkIblock(); }
    public function linkUser(){ return new BXIblockPropertyLinkUser(); }
    public function file(){ return new BXIblockPropertyFile(); }
}