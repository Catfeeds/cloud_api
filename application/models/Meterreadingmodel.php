<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        14:44
 * Describe:
 */

/**
 * 表计的读数记录表，相当于抄表记录。
 */
class Meterreadingmodel extends Basemodel {
    protected $table = 'boss_meter_reading';

    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = [
        'room_id',
        'type',
        'reading',
        'created_at',
        'updated_at',
    ];

    const TYPE_WATER_H  = 'HOT_WATER_METER'; //冷水表
    const TYPE_WATER_C  = 'COLD_WATER_METER'; //热水表
    const TYPE_ELECTRIC = 'ELECTRIC_METER'; //电表
    const TYPE_GAS      = 'GAS_METER'; //燃气

    public static function typeName($type) {
        $types = [
            self::TYPE_WATER_C  => '冷水费',
            self::TYPE_WATER_H  => '热水费',
            self::TYPE_ELECTRIC => '电费',
            self::TYPE_GAS      => '燃气',
        ];

        if (!isset($types[$type])) {
            return '未知';
        }

        return $types[$type];
    }

    /**
     * 读书的计量单位
     */
    public static function typeUnit($type) {
        $units = [
            self::TYPE_WATER_C  => '立',
            self::TYPE_WATER_H  => '立',
            self::TYPE_ELECTRIC => '度',
            self::TYPE_GAS      => '立',
        ];

        if (!isset($units[$type])) {
            return '未知';
        }

        return $units[$type];
    }

    /**
     * 该记录所属房间
     */
    public function roomunion() {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }
}
