<?php
/**
 * Created by PhpStorm.
 * User: min-fo026
 * Date: 17/5/2
 * Time: 下午2:09
 */

defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

//通告 组件名.Controller
class EpcControllerOems extends JControllerAdmin{

    public function getModel($name = 'Oem', $prefix = 'EpcModel', $config = array('ignore_request'=>true)){
        $model = parent::getModel($name, $prefix, $config);
        return $model;
    }

    public function save()
    {
        $jFileInput = new JInput($_FILES);
        $files = $jFileInput->get('jform', array(), 'array');

        $now_time = time();
        $save_path = JPATH_ROOT . '/daoru/xls/oem.xlsx';

        if (!JFile::upload($files['tmp_name']['xls'], $save_path)) {
            $this->setRedirect(JRoute::_('index.php?option=com_oem&view=oems', false));
            return false;
        }


        include 'PHPExcel/Classes/PHPExcel.php';

        $objReader = PHPExcel_IOFactory::createReaderForFile($save_path);
        $objPHPExcel = $objReader->load($save_path);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();           //取得总行数
        $highestColumn = $sheet->getHighestColumn();     //取得总列数

        $db = JFactory::getDbo();

        for($j=2;$j<=$highestRow;$j++)                        //从第二行开始读取数据
        {
            $str="";
            for($k='A';$k<=$highestColumn;$k++)            //从A列读取数据
            {
                $str .=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue().'|*|';//读取单元格

            }
            $str=mb_convert_encoding($str,'utf-8','auto');//根据自己编码修改
            $strs = explode("|*|",$str);

            if(!empty($strs[0])){
                $sql = "insert into #__oem (id,name,oem,position,num,syear,eyear,price,hprice,danwei,epcid,xzhuang,loureplace,lounewjian,note,wlshunote,addtime) values ('{$strs[0]}','{$strs[1]}','{$strs[2]}','{$strs[3]}','{$strs[4]}','{$strs[5]}','{$strs[6]}','{$strs[7]}','{$strs[8]}','{$strs[9]}','{$strs[10]}','{$strs[11]}','{$strs[12]}','{$strs[13]}','{$strs[14]}','{$strs[15]}','{$strs[16]}')";
                $db->setQuery($sql);
                $result = $db->loadResult();
            }
        }

        $this->setRedirect(JRoute::_('index.php?option=com_epc&view=epcs', false));
        return false;
    }

    //显示导入订单页面
    public function toin(){
        $this->setRedirect(JRoute::_('index.php?option=com_epc&view=inoem&layout=edit', false));
        return false;
    }

}
