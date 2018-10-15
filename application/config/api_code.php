<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['api_code'] = [
    0     => '正确',
    500   => '内部错误',

    //公共返回信息
    1001  => '无效token,请重新登录',
    1002  => '表单验证未通过',
    1003  => '没有找到该用户',
    1004  => '文件上传失败',
    1005  => '没有输入查询所需要的必要信息',
    1006  => '登陆出错',
    1007  => '没有查询到记录',
    1008  => '查询到有重复的记录',
    1009  => '操作数据库出错',
    1010  => 'Redis错误',
    1011  => '没有操作权限',
    1012  => '操作log出错',
    1013  => '员工姓名已存在',
    1014  => '职位已存在',
    1015  => '手机号已存在',
    1016  => '请检查手机号',
    1017  => '该扫码信息已存在',
    1018  => '没有对应的门店信息',
    1019  => '没有该门店的操作权限',
    1020  => '操作失败',
    //普通返回信息

    //登陆相关
    10002 => '没有输入code',
    10003 => '没有输入手机号',
    10007 => '用户频繁发送短信',
    10008 => '短信验证码不匹配',

    //boss端
    //账单管理
    10020 => '请选择正确的年月',

    10031 => '该优惠券的截止日期小于当前时间，请核对后再派发',
    10032 => '请填写正确的门店和房间号',
    10033 => '该房间里没有住户',
    10042 => '这笔账单用户已经支付过了，无法关闭',

    //员工端
    //办理入住
    10010 => '房间状态不是空闲',
    10011 => '不允许的操作，请检查住户状态',
    10012 => '没有操作权限，请切换到该门店管理员',
    10013 => '该房间不是占用状态',
    10021 => '房间状态不是预定!',
    10034 => '请检查房间状态',
    10038 => '该房间状态不支持该操作',
    //取消入住和取消预定
    10014 => '取消失败',
    10015 => '用户已经有支付过的订单',
    //确认支付
    10016 => '未找到订单信息或者订单状态错误!',
    10017 => '未检测到该住户的合同信息, 请生成后重试!',
    //现场支付
    10018 => '不支持的支付方式!',
    10019 => '未找到优惠券信息或者优惠券错误!',
    //续租
    10022 => '该房间已经有人租用, 请选择其他房间',
    10023 => '检测到未完成的账单, 请处理后重试!',
    10024 => '住户当前状态不能办理该业务!',
    10035 => '没有查询到该房间在住的住户信息',
    10036 => '没有查询到住户信息',
    10037 => '续租房间跟原房间不一致',
    //退房
    10025 => '正常退房与押金抵扣相冲突!',
    10026 => '该住户的退房订单已经存在!',
    10027 => '当前状态无法进行该操作!',
    10028 => '住户有未缴清的账单, 请核实或者选择押金抵扣!',
    10029 => '住户的住宿押金金额低于欠费金额, 无法抵扣!',
    10030 => '已经有支付的订单, 无法删除!',
    10040 => '用户有待确认的账单，请前往缴费待确认处理后再次提交',
    10041 => '用户有未审核的账单，请先处理后再次提交',
    10043 => '该用户已经超过三天免责期，不能办理三天免责退房！',
    ////水电及智能设备相关
    10050 => '蛋贝接口请求出错，数据返回失败',
    10051 => '上传读数错误',
    10052 => '上传Excel数据有误',
    10053 => '已经生成账单，不可修改读数',
    //小程序相关
    10101 => '房间号不存在！',
    10102 => '功能升级中！',
	10103 => '获取unionid失败',
	10104 => '没有查询到该员工',
    //fdd签署
    10080 => '签章出错',
    10081 => '合同目前状态无法进行此操作',
    10082 => '请先确认双方都已经成功签署了合同',
    10083 => '该公寓没有客户编号,请设置CA后重试!',
    10084 => '合同归档失败',

    //活动相关
    11101 => '奖品不能重复选择',
    11102 => '活动已下架',
    11103  =>  '一个门店只能有一个此活动',
    11104  =>  '没有查询到该活动' ,
    11105  =>  '入住奖品发放完' ,
    11106  =>  '奖品数量更改出错' ,
    11107  =>  '老用户奖品发放完' ,
    11108  =>  '以从活动该领取过奖品' ,
    11109  => '已经存在一个吸粉活动，不允许创建多个吸粉活动',
    11110  => '每人发放量不能多于奖品总量',

    //任务流相关
    11201   => '该记录不需要审核',
    11202   => '该记录已经存在审核任务',
    11203   => '此房间已经存在审核中的调价任务',
    11204   => '没有设置任务流',
    11205   => '该通知任务只能设置一步',
    
    //第三方授权相关
    11301  => '未授权',
];
