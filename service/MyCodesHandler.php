<?php
/**
 * 集成兑奖码处理类，实现getCodingMsgByIndex方法
 */
class MyCodesHandler extends CodesHandler{
    /**
     * @ 根据主键序号获取主键信息，需要继承重载此方法
     * @param $index            根据$index获取信息
     * @param $autoincreament   是否需要current自增，生成码时传true
     * @param $count            生成码的数量
     * @return array
     */
    public function getCodingMsgByIndex($index, $autoincreament=false, $count=1) {
        /**
         * 根据$index获取生成码的参数，并返回:
         *     [
         *         (int)    'current'=> '当前已创建码的个数，自增',
         *         (string) 'salt'=> '秘钥',
         *         (int)    'interference'=> '参与计算的乱序随机数',
         *     ]
         */
        // these should be read and write from database
        $arrGenarationMsg = [
            '1' => [
                'salt' => 'EWdb~bUB-*gUzrXs8#1#cxM<NkB.F(L#',
                'interference'=> 60272430,
            ],
            '2' => [
                'salt' => 'i^:3ieOb]*)^q-(j.47p2C&#8[[.i:@_',
                'interference'=> 67042795,
            ],
            '3' => [
                'salt' => 'iUN}WTDMQ6/=T-$W)sIW%,cAW7/]<]bo',
                'interference'=> 48102708,
            ],
            '4' => [
                'salt' => '6=)^or_{Qrzx9C]zbW(a[_=]9#TAs^)^',
                'interference'=> 77337345,
            ],
            '5' => [
                'salt' => '4az^AKBaH^a;{!qwmy7ikxCPQuGr84bR',
                'interference'=> 47471007,
            ],
            '6' => [
                'salt' => '_`&uNtK]-+q}|#(^$eqh4(TX{}c`5TKB',
                'interference'=> 10175627,
            ],
        ];
        if(!array_key_exists($index, $arrGenarationMsg))
            die('No such index msg!');
        $arrReturn = [
            'current'=> $index,
            'salt'=> $arrGenarationMsg[$index]['salt'],                  // A-Za-z0-9!@#$%^&*
            'interference'=> $arrGenarationMsg[$index]['interference'],  // 参加计算的随机数
        ];
        return $arrReturn;
    }

    /**
     * 生成秘钥
     * @param $length
     * @return
     */
    private static function generate_salt ($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

}
