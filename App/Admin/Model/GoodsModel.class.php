<?php
/**
 * Created by PhpStorm.
 * User: huanglele
 * Date: 2015/12/24
 * Time: 16:10
 */

namespace Admin\Model;
use Think\Model;

class GoodsModel extends Model
{
    protected $_validate = array(
        array('name','require','填写商品名字',),   //商品名字
        array('market_price','double','市场价格式不对'),   //市场价
        array('buy_price','double','市场价格式不对'),   //平台价
        array('left_num','number','库存数量格式不对格式不对'),   //库存数量格式不对格式不对
        array('rate','number','平台分成格式不对格式不对'),   //平台分成格式不对格式不对
    );
}