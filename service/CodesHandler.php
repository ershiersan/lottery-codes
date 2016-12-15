<?php

/**
 * Class CodesHandler
 */

class CodesHandler
{
    private $_code_length = null;

    private $_bin_index = null;
    private $_index = null;
    private $_bin_numbering = null;
    private $_numbering = null;
    private $_bin_garble = null;

    private $_salt = null;
    private $_interference = null;

    // 每个32进制字符，对应5位二进制
    //*
    private $_code_map = 'FXJHW7M6KLRGT4APE3N9YQC'; // 0O1I8B2Z5SDUV
    private $_digit_per_code = 4.5; // 每个字符代表的二进制数【只允许0.5整倍数】
    private $_ary_per_code = 23;    // 每个字符的进制
    private $_numbering_bin_length  = 20; // 1-1,048,576
    private $_index_bin_length      = 17; // 1-131,072
    private $_garble_bin_length     = null;
    // 最小位数
    private $_min_digit = 10;

    private $_error_codes = [
        '9001'=> 'Can not handle character codes this length',
        '9002'=> 'Index message id not exist or not correct',
        '9003'=> 'Verification failed',
        '9004'=> 'Numbering exceed the maximum',
        '9005'=> 'Index exceed the maximum',
    ];

    function __construct() {

    }

    /**
     * @ 根据码的位数，初始化各数值
     * @param $code_length     位数
     * @return boolen
     **/
    private function initLength($code_length) {
        $this->_code_length = $code_length;

        // 大于10位的码都可以生成
        if(!is_int($code_length) || $code_length < $this->_min_digit) {
            return false;
        }

        // index个数和每个index对应的码数是固定的，只是加密位数不一致
        $this->_garble_bin_length     = floor($code_length * $this->_digit_per_code) - $this->_numbering_bin_length - $this->_index_bin_length; 
        return true;
    }

    /**
     * @ 根据主键序号获取主键信息，需要继承重载此方法
     * @param $index            根据$index获取信息
     * @param $autoincreament   是否需要current自增，生成码时传true
     * @param $count            生成码的数量
     * @return array
     */
    protected function getCodingMsgByIndex($index, $autoincreament=false, $count=1) {
        /**
         * 根据$index获取该索引的信息，并返回:
         *     [
         *         (int)    'current'=> '当前创建码的起始数',
         *         (string) 'salt'=> '秘钥',
         *         (int)    'interference'=> '参与计算的乱序随机数',
         *     ]
         * 如果$autoincreament为true，current的值加$count保存
         */
        return false;
    }

    // getCodingMsgByIndex调用方法
    private function callCoding($action='verify', $count=1, $number_start=false) {
        try{
            if($number_start === false) {
                $arrReturn = $this->getCodingMsgByIndex($this->_index, $action=="create", $count);
            } else {
                $arrReturn = $this->getCodingMsgByIndex($this->_index, false, $count);
                $arrReturn['current'] = $number_start;
            }
        } catch (Exception $e) {
            return false;
        }
        if(is_array($arrReturn)
            && array_key_exists('current', $arrReturn)
            && preg_match("/^\d+$/", $arrReturn['current'])
            && array_key_exists('interference', $arrReturn)
            && preg_match("/^\d+$/", $arrReturn['interference'])
            && array_key_exists('salt', $arrReturn)
        ) {
            $this->_numbering = $arrReturn['current'];
            $this->_interference = $arrReturn['interference'];
            $this->_salt = $arrReturn['salt'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ 创建唯一码的入口
     * @param $index
     * @param $code_length // 生成码的位数
     * @param $count // 生成码的数量
     * @param $out_file 生成的码输出到文件
     * @param $number_start 传入该值，则不去获取和更新index的值
     * @return array
     */
    public function encode($index, $code_length=11, $count = 1, $out_file='', $number_start=false) {
        @unlink($out_file);
        if(!$this->initLength($code_length)) {
            return $this->returnArray(9001,
                "Can not handle {$code_length}-character codes"
            );
        }
        $this->_index = $index;
        $indexMsg = $this->callCoding('create', $count, $number_start);

        $arrCodings = [];
        for($i=0; $i<$count; $i++) {
            if ($indexMsg === false) { // 不存在解析出来的index
                return $this->returnArray(9002);
            }
            $this->_bin_numbering = $this->nuToBin($this->_numbering, $this->_numbering_bin_length);
            // 编号超出最大存储范围
            if (strlen($this->_bin_numbering) > $this->_numbering_bin_length) {
                return $this->returnArray(9004);
            }

            // 索引ID超出最大存储范围
            $this->_bin_index = $this->nuToBin($this->_index, $this->_index_bin_length);
            if (strlen($this->_bin_index) > $this->_index_bin_length) {
                return $this->returnArray(9005);
            }

            $disruptedBin41 = $this->makeSignAndMixWithNumber();
            $endMix = $this->implodeBins($this->binNot($this->_bin_index), $disruptedBin41);
//echo $disruptedBin41."\t";
            $str_code = $this->binToStr($endMix);
            /*if($count <= 100) {
                $arrCodings[] = $str_code;
            }*/
            if($out_file) {
                file_put_contents($out_file, $str_code."\n", FILE_APPEND);
            } else {
                $arrCodings[] = $str_code;
            }
            $this->_numbering++;
        }
        return $this->returnArray(0, 'success', [
            'number'=>['start'=>$this->_numbering-$count, 'end'=>$this->_numbering-1, 'count'=>$count],
            'codes'=>$arrCodings,
            'out_file'=>$out_file,
        ]);
    }

    private $_code_map_64 = 'IBXyfuMiLF64C8tE-kUcSw0QHqex3oDWP9RVrAaNhY1d572vznOgGpTZsmKJ_jlb';
    private $_digit_per_code_64 = 6; // 每个字符代表的二进制数【只允许0.5整倍数】
    private $_ary_per_code_64 = 64;    // 每个字符的进制

    /**
     * @ 64进制加密兑奖码转换原兑奖码，扫码兑奖加一层保护
     * @param $code64
     * @return array
     */
    public function decode_64($code64) {
        $code64 = trim($code64);
        $endMix = '';
        $this->_code_length = $codeLength = strlen($code64);
        $lengthMix = floor($this->_digit_per_code*$codeLength);
        $codeLength64 = ceil($lengthMix / $this->_digit_per_code_64);
        for($i=0; $i<$codeLength64; $i++) {
            $char64 = $code64{$codeLength-$i-1};
            $position = strpos($this->_code_map_64, $char64);
            $strBin = decbin($position);
            for(; strlen($strBin) < $this->_digit_per_code_64; ) {
                $strBin = '0'.$strBin;
            }
            $endMix = $strBin.$endMix;
        }

        // 重新排序
        $endMixNew = '';
        for($i=0; $i<$codeLength64; $i++) {
            for($j=0; $j<$this->_digit_per_code_64; $j++) {
                $indexCurrent = (int)($j*$codeLength64+$i);
                $endMixNew .= $endMix{$indexCurrent};
            }
        }
        $endMix = $endMixNew;
        unset($endMixNew);

        if(strlen($endMix) > $lengthMix) {
            $endMix = substr($endMix, strlen($endMix) - $lengthMix);
        }
        $code = $this->binToStr($endMix);
        return $code;
    }

    /**
     * @ 兑奖码转换64进制，扫码兑奖加一层保护
     * @param $code
     * @return array
     */
    public function encode_64($code) {
        $code = trim($code);
        $endMix = $this->strToBin($code);
        $lengthMix = strlen($endMix);
        $codeLength = strlen($code);
        $codeLength64 = ceil($lengthMix / $this->_digit_per_code_64);

        for($i=0; $i<$codeLength64*$this->_digit_per_code_64-$lengthMix; $i++) {
            $endMix = '0'.$endMix;
        }
        $lengthMix = strlen($endMix);

        // 重新排序
        $endMixNew = '';
        for($j=0; $j<$this->_digit_per_code_64; $j++) {
            for($i=0; $i<$codeLength64; $i++) {
                $indexCurrent = (int)($j+$i*$this->_digit_per_code_64);
                $endMixNew .= $endMix{$indexCurrent};
            }
        }
        $endMix = $endMixNew;
        unset($endMixNew);

        $code64 = '';
        for($i=0; $i<$codeLength; $i++) {
            if($i<$codeLength64) {
                $intSubBegin = $lengthMix-(($i+1)*$this->_digit_per_code_64);
                $strBin = substr(
                    $endMix, 
                    $intSubBegin < 0 ? 0 : $intSubBegin,
                    $intSubBegin < 0 ? $this->_digit_per_code_64+$intSubBegin : $this->_digit_per_code_64
                );
                $code64 = $this->_code_map_64{bindec($strBin)}.$code64;
            } else {
                // 补齐到你原来的位数
                $code64 = $this->_code_map_64{rand(0, $this->_ary_per_code_64-1)}.$code64;
            }
        }
        return $code64;
    }

    /**
     * @ 唯一码解码及验证
     * @param $code
     * @return array
     */
    public function decode($code) {
        $code = trim($code);
        $code_length = strlen($code);
        if(!$this->initLength($code_length)) {
            return $this->returnArray(9001,
                "Can not handle {$code_length}-character codes"
                );
        }
        $endMix = $this->strToBin($code);
        // 混合二进制串还原成混淆码和主键ID
        list($bin_index, $disruptedBin41) = $this->explodeBins($endMix, $this->_index_bin_length);
//        list($bin_index, $disruptedBin41) =  (c_explodeBins($endMix, $this->_index_bin_length));
        $this->_bin_index = $this->binNot($bin_index);

        // 索引ID
        $this->_index = bindec($this->_bin_index);

        $indexMsg = $this->callCoding();
        if($indexMsg === false){ // 不存在解析出来的index
            return $this->returnArray(9002);
        }

        $verifiedResult = $this->splitMixedAndVerified($disruptedBin41);

        if(!$verifiedResult) {
            return $this->returnArray(9003);
        }

        return $this->returnArray(0, 'success', [
            'index'=>$this->_index,
            'number'=>$this->_numbering,
        ]);
    }

    /**
     * @生成乱序的签名二进制，和序号二进制拼接并使用干扰码进行乱序
     * @return bin41
     **/
    private function makeSignAndMixWithNumber() {
        /* prefer to C code
        input:
            $this->_salt
            $this->_numbering
            $this->_garble_bin_length
            $this->_bin_numbering
            $this->_numbering_bin_length
            $this->_interference

        output:
            $disruptedBin41
        */
        {
            // 签名二进制
            $bin_garble = $this->getGarbleBin($this->_salt, $this->_numbering, $this->_garble_bin_length);
            // 混合签名和编号二进制
            $implodeBin41 = $this->implodeBins($bin_garble, $this->_bin_numbering);
            // 获取乱序的参数
            $arrDisruptParams = $this->getDisruptParams($this->_garble_bin_length, $this->_numbering_bin_length, $this->_interference+pow($this->getBinSum($implodeBin41), 3));
            // 获取乱序的（混合签名和编号二进制）
            $disruptedBin41 = $this->disruptOrder($implodeBin41, $arrDisruptParams);
        }

//        echo json_encode($arrDisruptParams)."\t";

        return $disruptedBin41;


        /*return (c_makeSignAndMixWithNumber(
            $this->_salt,
            $this->_numbering,
            $this->_bin_numbering,
            $this->_numbering_bin_length,
            $this->_garble_bin_length,
            $this->_interference
        ));*/

    }

    /**
     * @乱序恢复，拆分出序号二进制和签名二进制并校验
     * @return boolean
     **/
    private function splitMixedAndVerified($disruptedBin41) {
        /* prefer to C code
        input:
            $this->_garble_bin_length
            $this->_numbering_bin_length
            $this->_interference
            $this->_salt

        output:
            $this->_numbering
            $this->_bin_numbering
        */
        {
            // 获取乱序的参数
            $arrDisruptParams = $this->getDisruptParams($this->_garble_bin_length, $this->_numbering_bin_length, $this->_interference+pow($this->getBinSum($disruptedBin41), 3));
            // 获取原顺序的（混合签名和编号二进制）
            $implodeBin41 = $this->recoverOrder($disruptedBin41, $arrDisruptParams);
            list($bin_garble_to_verified, $bin_numbering) = $this->explodeBins($implodeBin41, $this->_garble_bin_length);
//            echo $bin_garble_to_verified."\t".$bin_numbering."\n";
            $numbering = bindec($bin_numbering);
            $bin_garble = $this->getGarbleBin($this->_salt, $numbering, $this->_garble_bin_length);
        }
        $this->_bin_numbering = $bin_numbering;
        $this->_numbering = $numbering;

//        echo "\n".json_encode($arrDisruptParams)."\t";

        if($bin_garble == $bin_garble_to_verified) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @根据二进制获取所有字节的和
     * @param $bin
     * @return int
     * */
    private function getBinSum($bin) {
        $sum = 0;
        for($i=0; $i<strlen($bin); $i++) {
            $sum += $bin{$i};
        }
        return $sum;
    }

    /**
     * 根据interference获取乱序的参数
     * @params $garble_bin_length
     * @params $numbering_bin_length
     * @params $interference
     * @return bin_string
     **/
    private function getDisruptParams($garble_bin_length, $numbering_bin_length, $interference) {
        $_bin_length = $garble_bin_length + $numbering_bin_length;
        $i = $interference%(pow($_bin_length, 3));
        // 散列参数顺序：宽|长|位移
        $step = $i%$_bin_length;
        $length = ($i-$step)/$_bin_length%$_bin_length;
        $width = ($i-$step-$length)/pow($_bin_length,2)%$_bin_length;
//        echo $interference."|".$step."|".$length."|".$width."\t";
        return ['step'=>$step, 'length'=>$length, 'width'=>$width];
    }

    /**
     * @使用参数对二进制串进行乱序
     * @param $bin
     * @param $params
     * @return string
     **/
    private function disruptOrder($bin, $params) {
        // 根据$params对混合二进制串进行乱序 ......
        if($params['step'] != 0) {
            $bin = substr($bin, $params['step']).substr($bin, 0, $params['step']);
        }
        $binMix = '';
        $strLength = strlen($bin);

        $tH = 0;
        $tW = 0;
        $tL = 0;
        $params['width'] ++;
        $params['length'] ++;
        for($i=0; $i<$strLength; $i++) {
            if($tH*$params['length']*$params['width']+$tW*$params['length']+$tL >= $strLength) {
                $tH = 0;
                $tL++;
            }
            if($tL >= $params['length']) {
                $tL = 0;
                $tW++;
            }
            $binMix .= $bin{$tH*$params['length']*$params['width']+$tW*$params['length']+$tL};
            $tH++;
        }

        return $binMix;
    }

    /**
     * 使用参数对二进制串恢复原顺序
     * @param $endMix
     * @param $params
     * @return string
     **/
    private function recoverOrder($endMix, $params) {
        // 根据$params对乱序的混合二进制串恢复排序 ......
        $arrBin = [];
        $strLength = strlen($endMix);
        $tH = 0;
        $tW = 0;
        $tL = 0;
        $params['width'] ++;
        $params['length'] ++;
        for($i=0; $i<$strLength; $i++) {
            if($tH*$params['length']*$params['width']+$tW*$params['length']+$tL >= $strLength) {
                $tH = 0;
                $tL++;
            }
            if($tL >= $params['length']) {
                $tL = 0;
                $tW++;
            }
            $arrBin[$tH*$params['length']*$params['width']+$tW*$params['length']+$tL] = $endMix{$i};
            $tH++;
        }
        ksort($arrBin);
        $returnBin = implode($arrBin);
        $returnBin = substr($returnBin, $strLength-$params['step']).substr($returnBin, 0, $strLength-$params['step']);
        return $returnBin;
    }

    /**
     * 两个二进制串混合
     **/
    private function implodeBins($strBin1, $strBin2) {
        $strBin1 = strrev($strBin1);
        $lengthBin1 = strlen($strBin1);
        $lengthBin2 = strlen($strBin2);
        $lengthSum = $lengthBin1+$lengthBin2;

        $tempSum1 = 0;
        $tempSum2 = 0;
        $tempindex1 = 0;
        $tempindex2 = 0;
        $binReturn = '';
        for($i=0; $i<$lengthSum; $i++) {
            if($tempSum1 >= $tempSum2) {
                $binReturn .= $strBin1{$tempindex1++};
                $tempSum2 += $lengthBin2;
            } else {
                $binReturn .= $strBin2{$tempindex2++};
                $tempSum1 += $lengthBin1;
            }
        }
        $halfSum = floor($lengthSum/4);
//        echo substr($binReturn, $halfSum)."|".substr($binReturn, 0, $halfSum)."\t";
        return (substr($binReturn, $halfSum).substr($binReturn, 0, $halfSum));
//        return strrev($binReturn);
    }

    /**
     * 对混合的两个二进制串，按照各自长度进行拆分
     **/
    private function explodeBins($binMix, $intLength1) {
        $lengthSum = strlen($binMix);
        $halfSum = $lengthSum - floor($lengthSum/4);
        $binMix = substr($binMix, $halfSum).substr($binMix, 0, $halfSum);

        $lengthBin1 = $intLength1;
        $lengthBin2 = $lengthSum - $intLength1;
        $strBin1 = '';
        $strBin2 = '';
        $tempSum1 = 0;
        $tempSum2 = 0;
        for($i=0; $i<$lengthSum; $i++) {
            if($tempSum1 >= $tempSum2) {
                $strBin1 .= $binMix{$i};
                $tempSum2 += $lengthBin2;
            } else {
                $strBin2 .= $binMix{$i};
                $tempSum1 += $lengthBin1;
            }
        }

        return [strrev($strBin1), $strBin2];
    }

    /**
     * @ 根据salt和number进行散列，获取14位二进制不可逆混淆码
     * @params $salt
     * @params $number
     * @params $garble_bin_length
     * @return null
     */
    private function getGarbleBin($salt, $number, $garble_bin_length) {
        $str = strrev($salt.$number);
        $strSha = md5($str);
        $binReturn = '';
        while($garble_bin_length > strlen($strSha)) {
            $strSha = $strSha.$strSha;
        }
        for($i=0; $i<$garble_bin_length; $i++) {
            $binReturn .= ord($strSha{$i}) % 2;
        }
//        echo $binReturn."\t";
        return $binReturn;
    }

    /**
     * 十进制转二进制并补齐最小位数
     **/
    private function nuToBin($nu, $mixLength) {
        $bin = decbin($nu);
        $lengthBin = strlen($bin);
        for($i=0; $i<$mixLength-$lengthBin; $i++) {
            $bin = '0'.$bin;
        }
        return $bin;
    }

    /**
     * 二进制串转32进制字符串
     **/
    private function binToStr($bin) {
        $strReturn = '';

        $double_degit = 2*$this->_digit_per_code;
        $total_length = strlen($bin);
        for($i=0; $i<ceil($total_length/$double_degit); $i++) {
            $subStart = $total_length-($i+1)*$double_degit;
            $subLength = $double_degit;
            if($subStart<0) {
                $subLength = $double_degit + $subStart;
                $subStart = 0;
            }
            $strReturn = self::binToDoubleStr(
                substr($bin, $subStart, $subLength)
            ).$strReturn;
        }

        if(strlen($strReturn) > $this->_code_length) {
            $strReturn = substr($strReturn, strlen($strReturn)-$this->_code_length);
        }
// echo $bin."\t".$strReturn."\n";
        return $strReturn;
    }

    private function binToDoubleStr($bin) {
        $doubleInt = bindec($bin);
        $int_one = (int)floor($doubleInt / $this->_ary_per_code);
        $int_two = (int)($doubleInt % $this->_ary_per_code);
        return $this->_code_map{$int_one}.$this->_code_map{$int_two};
    }

    private function doubleStrToBin($str) {
        if(strlen($str) == 1) {
            $position = strpos($this->_code_map, $str);
            return $this->nuToBin($position, floor($this->_digit_per_code));
        } else {
            $position_one = strpos($this->_code_map, $str{0});
            $position_two = strpos($this->_code_map, $str{1});
            return $this->nuToBin($position_one*$this->_ary_per_code+$position_two, floor(2*$this->_digit_per_code));
        }
    }

    /**
     * 32进制字符串转二进制串
     **/
    private function strToBin($str) {
        $str = trim($str);
        $binReturn = '';
        $double_degit = 2*$this->_digit_per_code;
        $total_length = strlen($str);
        for($i=0; $i<ceil($total_length/2); $i++) {
            $subStart = $total_length-($i+1)*2;
            $subLength = 2;
            if($subStart<0) {
                $subLength = 2 + $subStart;
                $subStart = 0;
            }

            $binReturn = self::doubleStrToBin(
                substr($str, $subStart, $subLength)
            ).$binReturn;
        }
        return $binReturn;
    }

    /**
     * 按位取反
     **/
    private function binNot($bin) {
        $binReturn = '';
        $binLength = strlen($bin);
        for($i=0; $i<$binLength; $i++) {
            $binReturn .= 1-$bin{$i};
        }
        return $binReturn;
    }

    /**
     * encode、decode返回的数组结构
     **/
    public function returnArray($error_code, $msg='', $data=[]) {
        if(!empty($error_code) && $msg == '' && array_key_exists($error_code, $this->_error_codes)) {
            $msg = $this->_error_codes[$error_code];
        }
        return [
            'errcode'=> $error_code,
            'msg'=> $msg,
            'data'=> $data,
        ];
    }
}
