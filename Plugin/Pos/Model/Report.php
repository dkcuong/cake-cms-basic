<?php
App::uses('PosAppModel', 'Pos.Model');

class Report extends PosAppModel {


	public $validate = array(
	);

	public $belongsTo = array(

	);

	public $hasMany = array(

	);

	public function report_1($now, $lang) {
        $obj = ClassRegistry::init('Pos.Order');

        //$now = date('Y-m-d');
        //$now = '2020-11-30';
        $begining_of_month = date("Y-m-01", strtotime($now));
        $next_day = date('Y-m-d', strtotime(' +1 day', strtotime($now)));
        $time_condition = '06:00:00';

        $conditions = array(
            'Order.date >=' => $begining_of_month . " " . $time_condition,
            'Order.date <' => $next_day . " " . $time_condition,
            'Order.status' => 3,
            'Order.void' => 0,
        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.grand_total",
                "DATE(Order.date) as transaction_date",
                "Hall.id",
                "Hall.code",
                "Schedule.id",
                "MovieLanguage.movie_id",
                "MovieType.id",
                "CONCAT(MovieLanguage.movie_id, '_', MovieType.id) as key_movie",
                "CONCAT(MovieLanguage.name, ' (', MovieType.name, ')') as movie_name",
                "sum(OrderDetail.qty) as total_ticket"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
                array(
                    'alias' => 'Schedule',
                    'table' => Environment::read('table_prefix') . 'schedules',
                    'type' => 'left',
                    'conditions' => array(
                        'Schedule.id = ScheduleDetail.schedule_id',
                    ),
                ),
                array(
                    'alias' => 'Hall',
                    'table' => Environment::read('table_prefix') . 'halls',
                    'type' => 'left',
                    'conditions' => array(
                        'Hall.id = Schedule.hall_id',
                    ),
                ),
                array(
                    'alias' => 'MovieLanguage',
                    'table' => Environment::read('table_prefix') . 'movie_languages',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieLanguage.movie_id = Schedule.movie_id',
                        'MovieLanguage.language' => $lang,
                    ),
                ),
                array(
                    'alias' => 'MovieType',
                    'table' => Environment::read('table_prefix') . 'movie_types',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieType.id = Schedule.movie_type_id',
                    ),
                ),
                array(
                    'alias' => 'OrderDetail',
                    'table' => Environment::read('table_prefix') . 'order_details',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetail.order_id = Order.id'
                    ),
                )
            ),
            'contain' => array (
                // 'Staff' => array()
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array('Order.date' => 'DESC'),
            'group' => array(
                'Order.id',
//                'Schedule.movie_id',
//                'Schedule.id',
            )
        );
        $result = $obj->find('all', $all_settings);

        // group by by date in code because cannot sum grand total in query
        $result_format = array();
        $result_format_total = array();
        $result_format_total_final = array();

        foreach ($result as $k=>$v) {
            //$key = $v[0]['transaction_date'] . "_" . $v['Schedule']['id'];
            $key = $v[0]['transaction_date'] . "_" . $v['MovieLanguage']['movie_id']
                    . '_' . $v['MovieType']['id'] . '_' . $v['Hall']['id'];

            $key_all_hall = $v[0]['transaction_date'] . "_" . $v['MovieLanguage']['movie_id']
                . '_' . $v['MovieType']['id'];

            $key_day = $v[0]['transaction_date'];
            if (!empty($v[0]['movie_name'])) {
                if (isset($result_format[$key])) {
                    $result_format[$key]['total_sale'] += $v['Order']['grand_total'];
                    $result_format[$key]['total_ticket'] += $v[0]['total_ticket'];
                    //$result_format[$key]['list_hall_code'][] = $v['Hall']['code'];
                    $result_format[$key]['list_order_id'][] = $v['Order']['id'];
                } else {
                    $result_format[$key]['total_sale'] = $v['Order']['grand_total'];
                    $result_format[$key]['total_ticket'] = $v[0]['total_ticket'];
                    //$result_format[$key]['list_hall_code'][] = $v['Hall']['code'];
                    $result_format[$key]['date'] = $v[0]['transaction_date'];
                    $result_format[$key]['hall'] = array (
                        'id' => $v['Hall']['id'],
                        'code' => $v['Hall']['code']
                    );
                    $result_format[$key]['movie_name'] = $v[0]['movie_name'];
                    $result_format[$key]['movie_id'] = $v['MovieLanguage']['movie_id'];
                    $result_format[$key]['movie_type_id'] = $v['MovieType']['id'];
                    $result_format[$key]['key_movie'] = $v[0]['key_movie'];
                    $result_format[$key]['list_order_id'][] = $v['Order']['id'];
                    $result_format[$key]['key'] = $key;
                }

                if (isset($result_format_total[$key_all_hall])) {
                    $result_format_total[$key_all_hall]['total_sale'] += $v['Order']['grand_total'];
                    $result_format_total[$key_all_hall]['total_ticket'] += $v[0]['total_ticket'];
                } else {
                    $result_format_total[$key_all_hall]['total_sale'] = $v['Order']['grand_total'];
                    $result_format_total[$key_all_hall]['total_ticket'] = $v[0]['total_ticket'];
                }

                if (isset($result_format_total_final[$key_day])) {
                    $result_format_total_final[$key_day]['total_sale'] += $v['Order']['grand_total'];
                    $result_format_total_final[$key_day]['total_ticket'] += $v[0]['total_ticket'];
                } else {
                    $result_format_total_final[$key_day]['total_sale'] = $v['Order']['grand_total'];
                    $result_format_total_final[$key_day]['total_ticket'] = $v[0]['total_ticket'];
                }

            }
        }

        $return_result = array(
            'result_format' => $result_format,
            'result_format_total' => $result_format_total,
            'result_format_total_final' => $result_format_total_final
        );
        return
            $return_result;
	}

    public function report_2($now, $lang) {
        $obj = ClassRegistry::init('Pos.Order');

        //$now = date('Y-m-d');
        //$now = '2020-11-30';
        $begining_of_month = date("Y-m-01", strtotime($now));
        $from_day = date('Y-m-d', strtotime($now));
        $next_day = date('Y-m-d', strtotime(' +1 day', strtotime($now)));
        $time_condition = '06:00:00';

        $conditions = array(
            'DATE(ScheduleDetail.date)' => $from_day,
            'Order.status' => 3,
//            'Order.void' => 0,
        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.is_pos",
                "Order.void",
                "Order.grand_total",
                "Order.total_amount",
                "Order.discount_amount",
                "Order.discount_percentage",
                "Order.total_discount_coupon",
                "DATE(Order.date) as transaction_date",
                "Staff.id",
                "Staff.name",
                "Hall.id",
                "Hall.code",
                "Schedule.id",
                "ScheduleDetail.id",
                "ScheduleDetail.date",
                "ScheduleDetail.time",
                "ScheduleDetail.gv_value",
                "MovieLanguage.movie_id",
                "MovieType.id",
                "CONCAT(MovieLanguage.movie_id, '_', MovieType.id) as key_movie",
                "CONCAT(MovieLanguage.name, ' (', MovieType.name, ')') as movie_name",
//                "GROUP_CONCAT(PaymentMethod.id) as group_payment_id",
//                "GROUP_CONCAT(PaymentMethod.name) as group_payment_name",
//                "GROUP_CONCAT(PaymentMethod.type) as group_payment_type",
//                "GROUP_CONCAT(PaymentMethod.code) as group_payment_code",
//                "OrderPaymentLog.payType",
                'GROUP_CONCAT(Order.id) AS order_list',
                //"sum(OrderDetail.qty) as total_ticket"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'Staff',
                    'table' => Environment::read('table_prefix') . 'staffs',
                    'type' => 'left',
                    'conditions' => array(
                        'Staff.id = Order.staff_id',
                    ),
                ),
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
                array(
                    'alias' => 'Schedule',
                    'table' => Environment::read('table_prefix') . 'schedules',
                    'type' => 'left',
                    'conditions' => array(
                        'Schedule.id = ScheduleDetail.schedule_id',
                    ),
                ),
                array(
                    'alias' => 'Hall',
                    'table' => Environment::read('table_prefix') . 'halls',
                    'type' => 'left',
                    'conditions' => array(
                        'Hall.id = Schedule.hall_id',
                    ),
                ),
                array(
                    'alias' => 'MovieLanguage',
                    'table' => Environment::read('table_prefix') . 'movie_languages',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieLanguage.movie_id = Schedule.movie_id',
                        'MovieLanguage.language' => $lang,
                    ),
                ),
                array(
                    'alias' => 'MovieType',
                    'table' => Environment::read('table_prefix') . 'movie_types',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieType.id = Schedule.movie_type_id',
                    ),
                ),
                array(
                    'alias' => 'OrderDetailPayment',
                    'table' => Environment::read('table_prefix') . 'order_detail_payments',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetailPayment.order_id = Order.id'
                    ),
                ),
//                array(
//                    'alias' => 'OrderPaymentLog',
//                    'table' => Environment::read('table_prefix') . 'order_payment_logs',
//                    'type' => 'left',
//                    'conditions' => array(
//                        'OrderPaymentLog.id = Order.payment_log_id'
//                    ),
//                ),
//                array(
//                    'alias' => 'PaymentMethod',
//                    'table' => Environment::read('table_prefix') . 'payment_methods',
//                    'type' => 'left',
//                    'conditions' => array(
//                        'PaymentMethod.id = OrderDetailPayment.payment_method_id'
//                    ),
//                ),
//                array(
//                    'alias' => 'OrderDetail',
//                    'table' => Environment::read('table_prefix') . 'order_details',
//                    'type' => 'left',
//                    'conditions' => array(
//                        'OrderDetail.order_id = Order.id'
//                    ),
//                ),
//                array(
//                    'alias' => 'ScheduleDetailTicketType',
//                    'table' => Environment::read('table_prefix') . 'schedule_detail_ticket_types',
//                    'type' => 'left',
//                    'conditions' => array(
//                        'ScheduleDetailTicketType.id = OrderDetail.schedule_detail_ticket_type_id'
//                    ),
//                ),
//                array(
//                    'alias' => 'TicketType',
//                    'table' => Environment::read('table_prefix') . 'ticket_types',
//                    'type' => 'left',
//                    'conditions' => array(
//                        'TicketType.id = ScheduleDetailTicketType.ticket_type_id'
//                    ),
//                ),
            ),
            'contain' => array (
                 'OrderDetail' => array(
                     'fields' => array ('id', 'service_charge', 'price_hkbo'),
                     'ScheduleDetailTicketType' => array (
                         'fields' => array('id'),
                         'TicketType' => array (
                             'fields' => array('code','is_main')
                         )
                     )
                 ),
                'OrderDetailPayment' => array(
                    'fields' => array ('amount'),
                    'PaymentMethod' => array('code', 'name')
                ),
                'OrderPaymentLog' => array(
                    'fields' => array ('amt', 'payType')
                )
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(
                'MovieLanguage.name' => 'ASC',
                'Hall.code' => 'ASC',
                'ScheduleDetail.date' => 'ASC',
                'ScheduleDetail.time' => 'ASC',
            ),
            'group' => array(
                'Order.id',
                //'ScheduleDetail.time',
                //'Schedule.id',
            )
        );
        $result = $obj->find('all', $all_settings);

        $objScheduleDetail = ClassRegistry::init('Movie.ScheduleDetail');

        $option_ticket = array(
            'fields' => array(
                'ScheduleDetail.*',
                'ScheduleDetailTicketType.price',
                'ScheduleDetailTicketType.price_hkbo',
                'TicketType.code',
                'TicketType.is_main',
            ),
            'joins' => array(
                array(
                    'alias' => 'ScheduleDetailTicketType',
                    'table' => Environment::read('table_prefix') . 'schedule_detail_ticket_types',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetailTicketType.schedule_detail_id = ScheduleDetail.id'
                    ),
                ),
                array(
                    'alias' => 'TicketType',
                    'table' => Environment::read('table_prefix') . 'ticket_types',
                    'type' => 'left',
                    'conditions' => array(
                        'TicketType.id = ScheduleDetailTicketType.ticket_type_id'
                    ),
                ),
            ),
            'contain' => array(
//                'ScheduleDetailTicketType' => array(
//                    'fields' => array ('id', 'price', 'price_hkbo'),
//                    'TicketType' => array(
//                        'fields' => array ('code', 'is_main'),
//                        'conditions' => array (
//                            'is_main' => 1
//                        )
//                    )
//                )
            ),
            'conditions' => array(
                'DATE(ScheduleDetail.date)' => $from_day,
                'TicketType.is_main' => 1
            ),
        );


        $data_schedule_tickets = $objScheduleDetail->find('all', $option_ticket);

        $data_schedule_tickets_format = array();
        foreach ($data_schedule_tickets as $k=>$v) {
            $key_schedule_detail = $v['ScheduleDetail']['id'];
            $data_schedule_tickets_format[$key_schedule_detail]['price'] = $v['ScheduleDetailTicketType']['price'];
            $data_schedule_tickets_format[$key_schedule_detail]['price_hkbo'] = $v['ScheduleDetailTicketType']['price_hkbo'];

        }

        $option_schedule_show = array(
            'fields' => array(
                'ScheduleDetail.id',
                'Schedule.id',
                'Hall.id',
                'Hall.code',
                'MovieLanguage.movie_id',
                'MovieType.id',
                'ScheduleDetail.date',
                'ScheduleDetail.time',
                "CONCAT(MovieLanguage.name, ' (', MovieType.name, ')') as movie_name"
            ),
            'joins' => array(
                array(
                    'alias' => 'Schedule',
                    'table' => Environment::read('table_prefix') . 'schedules',
                    'type' => 'left',
                    'conditions' => array(
                        'Schedule.id = ScheduleDetail.schedule_id',
                    ),
                ),
                array(
                    'alias' => 'Hall',
                    'table' => Environment::read('table_prefix') . 'halls',
                    'type' => 'left',
                    'conditions' => array(
                        'Hall.id = Schedule.hall_id',
                    ),
                ),
                array(
                    'alias' => 'MovieLanguage',
                    'table' => Environment::read('table_prefix') . 'movie_languages',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieLanguage.movie_id = Schedule.movie_id',
                        'MovieLanguage.language' => $lang,
                    ),
                ),
                array(
                    'alias' => 'MovieType',
                    'table' => Environment::read('table_prefix') . 'movie_types',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieType.id = Schedule.movie_type_id',
                    ),
                ),
            ),
            'contain' => array(

            ),
            'order' => array(
                'MovieLanguage.name' => 'ASC',
                'Hall.code' => 'ASC',
                'ScheduleDetail.date' => 'ASC',
                'ScheduleDetail.time' => 'ASC',
            ),
            'conditions' => array(
                'DATE(ScheduleDetail.date)' => $from_day,
            ),
        );

        $list_schedule_show = $objScheduleDetail->find('all', $option_schedule_show);

        $list_schedule_show_display = array();
        foreach ($list_schedule_show as $k=>$v) {
            $key_movie = $v['MovieLanguage']['movie_id'] . '_' . $v['MovieType']['id'];
            $key_time = $v['ScheduleDetail']['id'];
            $time_display = date('Y-m-d', strtotime($v['ScheduleDetail']['date'])) . ' ' . date('H:i', strtotime($v['ScheduleDetail']['time']));
            $key_hall = $v['Hall']['id'];

            $list_schedule_show_display[$key_movie]['movie_name'] = $v[0]['movie_name'];
            $list_schedule_show_display[$key_movie]['list_hall'][$key_hall]['hall_code'] = $v['Hall']['code'];
            $list_schedule_show_display[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['time_display'] = $time_display;
            $list_schedule_show_display[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['price_origin'] = $data_schedule_tickets_format[$key_time]['price'];
        }



//pr("\n" . __CLASS__ . ' :: ' . __FUNCTION__ . ' Line:' . __LINE__);pr( $data_schedule_tickets_format );exit;

        $objPaymentMethod = ClassRegistry::init('Pos.PaymentMethod');
        $type = $objPaymentMethod->type;
        //pr("\n" . _CLASS_ . ' :: ' . _FUNCTION_ . ' Line:' . __LINE__);pr( $result );exit;
//        "GROUP_CONCAT(PaymentMethod.name) as group_payment_name",
//                "GROUP_CONCAT(PaymentMethod.type) as group_payment_type",
//                "GROUP_CONCAT(PaymentMethod.code) as group_payment_code",
//                "OrderPaymentLog.payType",

        // group by by date in code because cannot sum grand total in query
        $result_format = array();
        $result_format_total = array();

        $result_format_total_final['total_ticket'] = 0;
        $result_format_total_final['total_ticket_adult'] = 0;
        $result_format_total_final['total_ticket_other'] = 0;
        $result_format_total_final['internet_charge'] = 0;
        $result_format_total_final['total_sale'] = 0;
        $result_format_total_final['total_discount_amount'] = 0;
        $result_format_total_final['total_amount'] = 0;
        $result_format_total_final['total_sale_custom'] = 0;
        $result_format_total_final['list_payment_amount'] = array();
        $result_format_total_final['total_refund_ticket'] = 0;
        $result_format_total_final['total_refund_sale'] = 0;

        $list_map_payment = array (
            'WECHAT' => 'WECHAT PAY',
            'ALIPAY_HK' => 'ALIPAY HK'
        );
        $list_count_payment = array('GV', 'Exchange Coupon');
        $list_title_payment = array();

        foreach ($result as $k=>$v) {
            $key_movie = $v['MovieLanguage']['movie_id'] . '_' . $v['MovieType']['id'];
//            $key_time = $v['ScheduleDetail']['date'] . '_' . $v['ScheduleDetail']['time'];
            $key_time = $v['ScheduleDetail']['id'];
            $time_display = date('Y-m-d', strtotime($v['ScheduleDetail']['date'])) . ' ' . date('H:i', strtotime($v['ScheduleDetail']['time']));
            $key_hall = $v['Hall']['id'];

            if (empty($v['ScheduleDetail']['id'])) {
                continue;
            }

            $discount_member_percentage = $v['Order']['discount_percentage'];
            $grand_total_calculate = 0;
            $discount_amount_calculate = 0;
            $total_amount_calculate = 0;


            if ($v['Order']['is_pos'] == 1) {
//                $key_staff = $v['Staff']['id'];
//                $staff_name = $v['Staff']['name'];
//                $key_payment_method = $v[0]['group_payment_id'];
//                $payment_method_code = $v[0]['group_payment_code'];
//                $group_payment_method_type = $v[0]['group_payment_type'];
            } else {
//                $key_staff = $staff_name = 'Internet';
//                $key_payment_method = $v['OrderPaymentLog']['payType'];
//                $payment_method_code = strtoupper($v['OrderPaymentLog']['payType']);
//                $group_payment_method_type = $v[0]['group_payment_type'];
            }

            $total_ticket_adult = $total_ticket_other = 0;
            $internet_charge = 0;
            $price_custom = 0;
            $list_payment_amount = array();

            if ($v['Order']['void'] == 0) {
                /////////////// BEGIN  : 4 calculate ///////////////
                if (!empty($v['ScheduleDetail']['id'])) {
                    $is_gv = false;
                    $count_gv = 0;
                    $is_exchange = false;
                    if ($v['Order']['is_pos']) {
//                    $list_payment_method_code = explode(',', $payment_method_code);
//                    $list_payment_method_type = explode(',', $group_payment_method_type);
//                    foreach ($list_payment_method_type as $kType => $vType) {
//                        if ($type[$vType] == 'Exchange Ticket') {
//                            if (strpos($list_payment_method_code[$kType], 'GV') === 0) {
//                                $is_gv = true;
//                                $count_gv ++ ;
//                            } else {
//                                $is_exchange = true;
//                            }
//                        }
//                    }
//                }

                        foreach ($v['OrderDetailPayment'] as $kGV => $vGV) {
                            if (trim($vGV['PaymentMethod']['code']) == 'GV') {
                                $is_gv = true;
                                $count_gv++;
                            }
                        }
                    }
                    if ($is_gv == true) {
                        $list_price = Hash::extract($v['OrderDetail'], "{n}.price_hkbo");
                        sort($list_price);


                        foreach ($list_price as $kPrice => $vPrice) {
                            if ($kPrice < $count_gv) {
                                $price_custom += $v['ScheduleDetail']['gv_value'];
                            } else {
                                $price_custom += $vPrice;
                            }
                        }
                    } else {
                        $price_custom = array_sum(Hash::extract($v['OrderDetail'], "{n}.price_hkbo"));
                    };

                    if ($discount_member_percentage > 0) {
                        $price_custom = $price_custom * (100 - $discount_member_percentage) / 100;
                    }
                }
                /////////////// END  : 4 calculate ///////////////


                foreach ($v['OrderDetail'] as $kDetail => $vDetail) {
                    if (in_array($vDetail['ScheduleDetailTicketType']['TicketType']['code'], array('A'))) {
                        $total_ticket_adult += 1;
                    }
                    if (in_array($vDetail['ScheduleDetailTicketType']['TicketType']['code'], array('S', 'SE', 'C'))) {
                        $total_ticket_other += 1;
                    }
                }


                if ($v['Order']['is_pos']) {
                    foreach ($v['OrderDetailPayment'] as $kPayment => $vPayment) {
                        $key_unique_payment = $vPayment['PaymentMethod']['code'];
                        if (isset($list_payment_amount[$key_unique_payment])) {
                            if (in_array($key_unique_payment, $list_count_payment)) {
                                $list_payment_amount[$key_unique_payment]['amount'] += 1;
                            } else {
                                $list_payment_amount[$key_unique_payment]['amount'] += $vPayment['amount'];
                            }
                        } else {
                            if (in_array($key_unique_payment, $list_count_payment)) {
                                $list_payment_amount[$key_unique_payment]['amount'] = 1;
                            } else {
                                $list_payment_amount[$key_unique_payment]['amount'] = $vPayment['amount'];
                            }

                            $list_payment_amount[$key_unique_payment]['display_name'] = $vPayment['PaymentMethod']['code'];
                        }
                        $list_title_payment[] = $key_unique_payment;
                    }
                } else {
                    $key_unique_payment = isset($list_map_payment[strtoupper($v['OrderPaymentLog']['payType'])]) ?
                        $list_map_payment[strtoupper($v['OrderPaymentLog']['payType'])] :
                        strtoupper($v['OrderPaymentLog']['payType']);
                    $list_payment_amount[$key_unique_payment]['amount'] = $v['OrderPaymentLog']['amt'] / 100;
                    $list_payment_amount[$key_unique_payment]['display_name'] = $key_unique_payment;
                    $list_title_payment[] = $key_unique_payment;

                }

                if (empty($v['Order']['is_pos'])) {
                    foreach ($v['OrderDetail'] as $kTemp => $vTemp) {
                        $internet_charge += $vTemp['service_charge'];
                    }
                }
            }

            $result_format[$key_movie]['movie_name'] = $v[0]['movie_name'];
            $result_format[$key_movie]['list_hall'][$key_hall]['hall_code'] = $v['Hall']['code'];
            $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['time_display'] = $time_display;
//            $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['price_origin'] = $data_schedule_tickets_format[$key_time]['price'];

            if ($v['Order']['void'] == 0) {
                $grand_total_calculate = $v['Order']['grand_total'];
                $discount_amount_calculate = $v['Order']['discount_amount'] + $v['Order']['total_discount_coupon'];
                $total_amount_calculate = $v['Order']['total_amount'];

                if (isset($result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket'])) {
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket'] += count($v['OrderDetail']);
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket_adult'] += $total_ticket_adult;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket_other'] += $total_ticket_other;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['internet_charge'] += $internet_charge;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_sale'] += $grand_total_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_discount_amount'] += $discount_amount_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_amount'] += $total_amount_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_sale_custom'] += $price_custom;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['order_list'] .= "-" . $v[0]['order_list'];

                    $list_payment_amount_old = $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['list_payment_amount'];
                    $list_payment_amount_new = $list_payment_amount;
                    $list_payment_amount_update = array();
                    foreach ($list_payment_amount_old as $kPaymentAmountOld => $PaymentAmountOld) {
                        if (isset($list_payment_amount_new[$kPaymentAmountOld])) {
                            $list_payment_amount_old[$kPaymentAmountOld]['amount'] += $list_payment_amount_new[$kPaymentAmountOld]['amount'];
                            unset($list_payment_amount_new[$kPaymentAmountOld]);
                        }
                    }
                    $list_payment_amount_update = array_merge($list_payment_amount_old, $list_payment_amount_new);
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['list_payment_amount'] = $list_payment_amount_update;
                } else {

                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket'] = count($v['OrderDetail']);
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket_adult'] = $total_ticket_adult;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_ticket_other'] = $total_ticket_other;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['internet_charge'] = $internet_charge;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_sale'] = $grand_total_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_discount_amount'] = $discount_amount_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_amount'] = $total_amount_calculate;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_sale_custom'] = $price_custom;
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['order_list'] = $v[0]['order_list'];

                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['list_payment_amount'] = $list_payment_amount;
                }

                if (isset($result_format_total[$key_movie]['total_ticket'])) {
                    $result_format_total[$key_movie]['total_ticket'] += count($v['OrderDetail']);
                    $result_format_total[$key_movie]['total_ticket_adult'] += $total_ticket_adult;
                    $result_format_total[$key_movie]['total_ticket_other'] += $total_ticket_other;
                    $result_format_total[$key_movie]['internet_charge'] += $internet_charge;
                    $result_format_total[$key_movie]['total_sale'] += $grand_total_calculate;
                    $result_format_total[$key_movie]['total_discount_amount'] += $discount_amount_calculate;
                    $result_format_total[$key_movie]['total_amount'] += $total_amount_calculate;
                    $result_format_total[$key_movie]['total_sale_custom'] += $price_custom;

                    $list_payment_amount_old = $result_format_total[$key_movie]['list_payment_amount'];
                    $list_payment_amount_new = $list_payment_amount;
                    $list_payment_amount_update = array();
                    foreach ($list_payment_amount_old as $kPaymentAmountOld => $PaymentAmountOld) {
                        if (isset($list_payment_amount_new[$kPaymentAmountOld])) {
                            $list_payment_amount_old[$kPaymentAmountOld]['amount'] += $list_payment_amount_new[$kPaymentAmountOld]['amount'];
                            unset($list_payment_amount_new[$kPaymentAmountOld]);
                        }
                    }
                    $list_payment_amount_update = array_merge($list_payment_amount_old, $list_payment_amount_new);
                    $result_format_total[$key_movie]['list_payment_amount'] = $list_payment_amount_update;
                } else {
                    $result_format_total[$key_movie]['total_ticket'] = count($v['OrderDetail']);
                    $result_format_total[$key_movie]['total_ticket_adult'] = $total_ticket_adult;
                    $result_format_total[$key_movie]['total_ticket_other'] = $total_ticket_other;
                    $result_format_total[$key_movie]['internet_charge'] = $internet_charge;
                    $result_format_total[$key_movie]['total_sale'] = $grand_total_calculate;
                    $result_format_total[$key_movie]['total_discount_amount'] = $discount_amount_calculate;
                    $result_format_total[$key_movie]['total_amount'] = $total_amount_calculate;
                    $result_format_total[$key_movie]['total_sale_custom'] = $price_custom;

                    $result_format_total[$key_movie]['list_payment_amount'] = $list_payment_amount;
                }

                $result_format_total_final['total_ticket'] += count($v['OrderDetail']);
                $result_format_total_final['total_ticket_adult'] += $total_ticket_adult;
                $result_format_total_final['total_ticket_other'] += $total_ticket_other;
                $result_format_total_final['internet_charge'] += $internet_charge;
                $result_format_total_final['total_sale'] += $grand_total_calculate;
                $result_format_total_final['total_discount_amount'] += $discount_amount_calculate;
                $result_format_total_final['total_amount'] += $total_amount_calculate;
                $result_format_total_final['total_sale_custom'] += $price_custom;

                $list_payment_amount_old = $result_format_total_final['list_payment_amount'];
                $list_payment_amount_new = $list_payment_amount;
                $list_payment_amount_update = array();
                foreach ($list_payment_amount_old as $kPaymentAmountOld => $PaymentAmountOld) {
                    if (isset($list_payment_amount_new[$kPaymentAmountOld])) {
                        $list_payment_amount_old[$kPaymentAmountOld]['amount'] += $list_payment_amount_new[$kPaymentAmountOld]['amount'];
                        unset($list_payment_amount_new[$kPaymentAmountOld]);
                    }
                }
                $list_payment_amount_update = array_merge($list_payment_amount_old, $list_payment_amount_new);
                $result_format_total_final['list_payment_amount'] = $list_payment_amount_update;
            } else {
                if (isset($result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_refund_ticket'])) {
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_refund_ticket'] += count($v['OrderDetail']);
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_refund_sale'] += $v['Order']['grand_total'];
                } else {
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_refund_ticket'] = count($v['OrderDetail']);
                    $result_format[$key_movie]['list_hall'][$key_hall]['list_time'][$key_time]['total_refund_sale'] = $v['Order']['grand_total'];
                }

                if (isset($result_format_total[$key_movie]['total_refund_ticket'])) {
                    $result_format_total[$key_movie]['total_refund_ticket'] += count($v['OrderDetail']);
                    $result_format_total[$key_movie]['total_refund_sale'] += $v['Order']['grand_total'];

                } else {
                    $result_format_total[$key_movie]['total_refund_ticket'] = count($v['OrderDetail']);
                    $result_format_total[$key_movie]['total_refund_sale'] = $v['Order']['grand_total'];
                }

                $result_format_total_final['total_refund_ticket'] += count($v['OrderDetail']);
                $result_format_total_final['total_refund_sale'] += $v['Order']['grand_total'];
            }
        }

        $list_title_payment = array_unique($list_title_payment);

        sort($list_title_payment);

        $return_result = array(
            'result_format' => $result_format,
            'result_format_total' => $result_format_total,
            'result_format_total_final' => $result_format_total_final,
            'list_title_payment' => array_unique($list_title_payment),
            'list_schedule_show_display' => $list_schedule_show_display
        );

        return
            $return_result;
    }

    public function report_3($now, $lang) {
        $obj = ClassRegistry::init('Pos.Order');

        //$now = date('Y-m-d');
        //$now = '2020-11-30';
        $begining_of_month = date("Y-m-01", strtotime($now));
        $end_of_month = date("Y-m-t", strtotime($now));
        $first_day_next_month = date('Y-m-d', strtotime('first day of +1 month', strtotime($now)));
        $time_condition = '06:00:00';

        $conditions = array(
            'Order.date >=' => $begining_of_month . " " . $time_condition,
            'Order.date <' => $first_day_next_month . " " . $time_condition,
            'Order.status' => 3,
            'Order.void' => 0,
        );
//        $conditions['OR'][] = array(
//            'DATE(ScheduleDetail.date)' => $begining_of_month,
//            'ScheduleDetail.time >=' => $time_condition
//        );
//        $conditions['OR'][] = array(
//            'DATE(ScheduleDetail.date) >' => $begining_of_month,
//            'DATE(ScheduleDetail.date) <' => $end_of_month
//        );
//        $conditions['OR'][] = array(
//            'DATE(ScheduleDetail.date)' => $end_of_month,
//            'ScheduleDetail.time <' => $time_condition
//        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.grand_total",
                "DATE(Order.date) as transaction_date",
                "Hall.id",
                "Hall.code",
                "Schedule.id",
                "ScheduleDetail.date",
                "ScheduleDetail.time",
                "MovieLanguage.movie_id",
                "MovieType.id",
                "CONCAT(MovieLanguage.movie_id, '_', MovieType.id) as key_movie",
                "CONCAT(MovieLanguage.name, ' (', MovieType.name, ')') as movie_name",
                "sum(OrderDetail.qty) as total_ticket"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
                array(
                    'alias' => 'Schedule',
                    'table' => Environment::read('table_prefix') . 'schedules',
                    'type' => 'left',
                    'conditions' => array(
                        'Schedule.id = ScheduleDetail.schedule_id',
                    ),
                ),
                array(
                    'alias' => 'Hall',
                    'table' => Environment::read('table_prefix') . 'halls',
                    'type' => 'left',
                    'conditions' => array(
                        'Hall.id = Schedule.hall_id',
                    ),
                ),
                array(
                    'alias' => 'MovieLanguage',
                    'table' => Environment::read('table_prefix') . 'movie_languages',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieLanguage.movie_id = Schedule.movie_id',
                        'MovieLanguage.language' => $lang,
                    ),
                ),
                array(
                    'alias' => 'MovieType',
                    'table' => Environment::read('table_prefix') . 'movie_types',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieType.id = Schedule.movie_type_id',
                    ),
                ),
                array(
                    'alias' => 'OrderDetail',
                    'table' => Environment::read('table_prefix') . 'order_details',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetail.order_id = Order.id'
                    ),
                )
            ),
            'contain' => array (
                //'OrderDetail' => array()
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(
                'MovieLanguage.name' => 'ASC',
                //'ScheduleDetail.time' => 'ASC',
                'Hall.code' => 'ASC'
            ),
            'group' => array(
                'Order.id',
                //'ScheduleDetail.time',
                //'Schedule.id',
            )
        );
        $result = $obj->find('all', $all_settings);


        // group by by date in code because cannot sum grand total in query
        $result_format = array();
        $result_format_total = array();
        $result_format_total_final['total_sale'] = 0;
        $result_format_total_final['total_ticket'] = 0;


        foreach ($result as $k=>$v) {
            $key_movie = $v['MovieLanguage']['movie_id'] . '_' . $v['MovieType']['id'];
            $key_hall = $v['Hall']['id'];

            if (!empty($v[0]['movie_name'])) {
                if (isset($result_format[$key_movie]['list_hall'][$key_hall])) {
                    $result_format[$key_movie]['list_hall'][$key_hall]['total_sale'] += $v['Order']['grand_total'];
                    $result_format[$key_movie]['list_hall'][$key_hall]['total_ticket'] += $v[0]['total_ticket'];
                } else {
                    $result_format[$key_movie]['list_hall'][$key_hall]['total_sale'] = $v['Order']['grand_total'];
                    $result_format[$key_movie]['list_hall'][$key_hall]['total_ticket'] = $v[0]['total_ticket'];
                    $result_format[$key_movie]['list_hall'][$key_hall]['hall_code'] = $v['Hall']['code'];

                    $result_format[$key_movie]['movie_name'] = $v[0]['movie_name'];
                }

                if (isset($result_format_total[$key_movie])) {
                    $result_format_total[$key_movie]['total_sale'] += $v['Order']['grand_total'];
                    $result_format_total[$key_movie]['total_ticket'] += $v[0]['total_ticket'];
                } else {
                    $result_format_total[$key_movie]['total_sale'] = $v['Order']['grand_total'];
                    $result_format_total[$key_movie]['total_ticket'] = $v[0]['total_ticket'];
                }


                $result_format_total_final['total_sale'] += $v['Order']['grand_total'];
                $result_format_total_final['total_ticket'] += $v[0]['total_ticket'];

            }
        }

        $return_result = array(
            'result_format' => $result_format,
            'result_format_total' => $result_format_total,
            'result_format_total_final' => $result_format_total_final
        );
        return
            $return_result;
    }

    public function report_4($now, $lang) {
        $obj = ClassRegistry::init('Pos.Order');

        //$now = date('Y-m-d');
        //$now = '2020-11-30';
        $begining_of_month = date("Y-m-01", strtotime($now));
        $from_day = date('Y-m-d', strtotime($now));
        $next_day = date('Y-m-d', strtotime(' +1 day', strtotime($now)));
        $time_condition = '06:00:00';

        $conditions = array(
            //'DATE(ScheduleDetail.date)' => $now,
            'Order.date >=' => $from_day . " " . $time_condition,
            'Order.date <' => $next_day . " " . $time_condition,
            'Order.status' => 3,
            'Order.void' => 0,
            'Order.is_pos' => 1,
        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.grand_total",
                "DATE(Order.date) as transaction_date",
                "Hall.id",
                "Hall.code",
                "Schedule.id",
                "ScheduleDetail.date",
                "ScheduleDetail.time",
                "MovieLanguage.movie_id",
                "MovieType.id",
                "CONCAT(MovieLanguage.movie_id, '_', MovieType.id) as key_movie",
                "CONCAT(MovieLanguage.name, ' (', MovieType.name, ')') as movie_name",
                "sum(OrderDetail.qty) as total_ticket"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
                array(
                    'alias' => 'Schedule',
                    'table' => Environment::read('table_prefix') . 'schedules',
                    'type' => 'left',
                    'conditions' => array(
                        'Schedule.id = ScheduleDetail.schedule_id',
                    ),
                ),
                array(
                    'alias' => 'Hall',
                    'table' => Environment::read('table_prefix') . 'halls',
                    'type' => 'left',
                    'conditions' => array(
                        'Hall.id = Schedule.hall_id',
                    ),
                ),
                array(
                    'alias' => 'MovieLanguage',
                    'table' => Environment::read('table_prefix') . 'movie_languages',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieLanguage.movie_id = Schedule.movie_id',
                        'MovieLanguage.language' => $lang,
                    ),
                ),
                array(
                    'alias' => 'MovieType',
                    'table' => Environment::read('table_prefix') . 'movie_types',
                    'type' => 'left',
                    'conditions' => array(
                        'MovieType.id = Schedule.movie_type_id',
                    ),
                ),
                array(
                    'alias' => 'OrderDetail',
                    'table' => Environment::read('table_prefix') . 'order_details',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetail.order_id = Order.id'
                    ),
                )
            ),
            'contain' => array (
                //'OrderDetail' => array()
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(
                'Hall.code' => 'ASC',
                'MovieLanguage.name' => 'ASC',
                'ScheduleDetail.date' => 'ASC',
                'ScheduleDetail.time' => 'ASC',
            ),
            'group' => array(
                'Order.id',
                //'ScheduleDetail.time',
                //'Schedule.id',
            )
        );
        $result = $obj->find('all', $all_settings);


        // group by by date in code because cannot sum grand total in query
        $result_format = array();
        $result_format_total_movie = array();
        $result_format_total_hall = array();

        $result_format_total_final['total_sale'] = 0;
        $result_format_total_final['total_ticket'] = 0;


        foreach ($result as $k=>$v) {
            $key_hall = $v['Hall']['id'];
            $key_movie = $v['MovieLanguage']['movie_id'] . '_' . $v['MovieType']['id'];
            $key_time = date('Y-m-d', strtotime($v['ScheduleDetail']['date'])) . '_' . date('H:i', strtotime($v['ScheduleDetail']['time']));

            if (!empty($v[0]['movie_name'])) {
                if (isset($result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time])) {
                    $result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time]['total_sale'] += $v['Order']['grand_total'];
                    $result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time]['total_ticket'] += $v[0]['total_ticket'];
                } else {
                    $result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time]['total_sale'] = $v['Order']['grand_total'];
                    $result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time]['total_ticket'] = $v[0]['total_ticket'];

                    $result_format[$key_hall]['list_movie'][$key_movie]['list_time'][$key_time]['schedule_date_time_display'] = date('Y-m-d', strtotime($v['ScheduleDetail']['date'])) . ' ' . date('H:i', strtotime($v['ScheduleDetail']['time']));
                    $result_format[$key_hall]['hall_code'] = $v['Hall']['code'];
                    $result_format[$key_hall]['list_movie'][$key_movie]['movie_name'] = $v[0]['movie_name'];
                }
            }

            if (isset($result_format_total_movie[$key_movie])) {
                $result_format_total_movie[$key_movie]['total_sale'] += $v['Order']['grand_total'];
                $result_format_total_movie[$key_movie]['total_ticket'] += $v[0]['total_ticket'];
            } else {
                $result_format_total_movie[$key_movie]['total_sale'] = $v['Order']['grand_total'];
                $result_format_total_movie[$key_movie]['total_ticket'] = $v[0]['total_ticket'];
            }

            if (isset($result_format_total_hall[$key_hall])) {
                $result_format_total_hall[$key_hall]['total_sale'] += $v['Order']['grand_total'];
                $result_format_total_hall[$key_hall]['total_ticket'] += $v[0]['total_ticket'];
            } else {
                $result_format_total_hall[$key_hall]['total_sale'] = $v['Order']['grand_total'];
                $result_format_total_hall[$key_hall]['total_ticket'] = $v[0]['total_ticket'];
            }

            $result_format_total_final['total_sale'] += $v['Order']['grand_total'];
            $result_format_total_final['total_ticket'] += $v[0]['total_ticket'];
        }

//pr("\n" . _CLASS_ . ' :: ' . _FUNCTION_ . ' Line:' . __LINE__);pr( $result_format );exit;

        $return_result = array(
            'result_format' => $result_format,
            'result_format_total_movie' => $result_format_total_movie,
            'result_format_total_hall' => $result_format_total_hall,
            'result_format_total_final' => $result_format_total_final
        );

        return
            $return_result;
    }

    public function report_5($now, $lang) {
        $obj = ClassRegistry::init('Pos.Order');

        $begining_of_month = date("Y-m-01", strtotime($now));
        $from_day = date('Y-m-d', strtotime($now));
        $next_day = date('Y-m-d', strtotime(' +1 day', strtotime($now)));
        $time_condition = '06:00:00';

        $conditions = array(
            //'DATE(Order.date)' => $now,
            'Order.date >=' => $from_day . " " . $time_condition,
            'Order.date <' => $next_day . " " . $time_condition,
            'Order.status' => 3,
            'Order.void' => 0,
            'Order.is_pos' => 1,
        );
//        $conditions['OR'][] = array(
//            'DATE(ScheduleDetail.date)' => $from_day,
//            'ScheduleDetail.time >=' => $time_condition
//        );
//        $conditions['OR'][] = array(
//            'DATE(ScheduleDetail.date)' => $next_day,
//            'ScheduleDetail.time <' => $time_condition
//        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.grand_total",
                "Staff.id",
                "Staff.name",
                "DATE(Order.date) as transaction_date",
                "GROUP_CONCAT(PaymentMethod.id, '') as payment_method_id_group",
                "ScheduleDetail.id",
                "GROUP_CONCAT(PaymentMethod.code, '') as payment_method_group",
                "SUM(OrderDetailPayment.amount) as sum_payment"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'Staff',
                    'table' => Environment::read('table_prefix') . 'staffs',
                    'type' => 'left',
                    'conditions' => array(
                        'Staff.id = Order.staff_id'
                    ),
                ),
                array(
                    'alias' => 'OrderDetailPayment',
                    'table' => Environment::read('table_prefix') . 'order_detail_payments',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetailPayment.order_id = Order.id'
                    ),
                ),
                array(
                    'alias' => 'PaymentMethod',
                    'table' => Environment::read('table_prefix') . 'payment_methods',
                    'type' => 'left',
                    'conditions' => array(
                        'PaymentMethod.id = OrderDetailPayment.payment_method_id'
                    ),
                ),
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
            ),
            'contain' => array (
                'OrderDetail' => array()
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(

            ),
            'group' => array(
                'Order.id',
            )
        );
        $result = $obj->find('all', $all_settings);


        // group by by date in code because cannot sum grand total in query
        $result_format = array();

        $result_format_total_final['total_sale'] = 0;
        $result_format_total_final['total_ticket'] = 0;
        $result_format_total_final['total_transaction'] = 0;
        $result_format_total_final['total_payment'] = 0;




        foreach ($result as $k => $v) {
            $key_method_id_group = $v[0]['payment_method_id_group'];

            if (!empty($v['ScheduleDetail']['id'])) {
                if (isset($result_format[$key_method_id_group])) {
                    $result_format[$key_method_id_group]['total_sale']+= $v['Order']['grand_total'];
                    $result_format[$key_method_id_group]['total_payment']+= $v[0]['sum_payment'];
                    $result_format[$key_method_id_group]['total_ticket']+= count($v['OrderDetail']);
                    $result_format[$key_method_id_group]['total_transaction'] += 1;
                    $result_format[$key_method_id_group]['staff_list'][$v['Staff']['id']] = $v['Staff']['name'];
                } else {
                    $result_format[$key_method_id_group]['total_sale'] = $v['Order']['grand_total'];
                    $result_format[$key_method_id_group]['total_payment'] = $v[0]['sum_payment'];
                    $result_format[$key_method_id_group]['total_ticket'] = count($v['OrderDetail']);
                    $result_format[$key_method_id_group]['total_transaction'] = 1;
                    $result_format[$key_method_id_group]['group_method_name'] = $v[0]['payment_method_group'];
                    $result_format[$key_method_id_group]['staff_list'][$v['Staff']['id']] = $v['Staff']['name'];

                    $payment_method_code = $v[0]['payment_method_group'];
                    $payment_method_code_format_array = explode(',', $payment_method_code);
                    $payment_method_code_format_temp = array();
                    foreach ($payment_method_code_format_array as $kFormatPaymentMethod => $vFormatPaymentMethod) {
                        if (isset($payment_method_code_format_temp[$vFormatPaymentMethod])) {
                            $payment_method_code_format_temp[$vFormatPaymentMethod] += 1;
                        } else {
                            $payment_method_code_format_temp[$vFormatPaymentMethod] = 1;
                        }
                    }
                    $payment_method_code_format = array();
                    foreach ($payment_method_code_format_temp as $kFormat => $vFormat) {
                        if ($vFormat > 1) {
                            $payment_method_code_format[$kFormat] = $kFormat . "(" . $vFormat . ")";
                        } else {
                            $payment_method_code_format[$kFormat] = $kFormat;
                        }
                    }
                    $payment_method_code = implode(', ', $payment_method_code_format);
                    $result_format[$key_method_id_group]['group_method_name'] = $payment_method_code;
                }
            }

            $result_format_total_final['total_sale'] += $v['Order']['grand_total'];
            $result_format_total_final['total_payment'] += $v[0]['sum_payment'];
            $result_format_total_final['total_ticket'] += count($v['OrderDetail']);
            $result_format_total_final['total_transaction'] += 1;
        }

        $return_result = array(
            'result_format' => $result_format,
            'result_format_total_final' => $result_format_total_final
        );

        return
            $return_result;
    }

    public function report_6($now, $lang) {

        $begining_of_month = date("Y-m-01", strtotime($now));
        $end_of_month = date("Y-m-t", strtotime($now));
        $first_day_next_month = date('Y-m-d', strtotime('first day of +1 month', strtotime($now)));
        $time_condition = '06:00:00';

        $obj = ClassRegistry::init('Pos.Order');

        $conditions = array(
            'Order.date >=' => $begining_of_month . " " . $time_condition,
            'Order.date <' => $first_day_next_month . " " . $time_condition,
            'Order.status' => 3,
            'Order.void' => 0,
            'Order.is_pos' => 1,
        );

        $all_settings = array(
            'fields' => array(
                "Order.id",
                "Order.grand_total",
                "Order.date",
                "DATE(Order.date) as transaction_date",
                //"GROUP_CONCAT(PaymentMethod.id, '') as payment_method_id_group",
                "ScheduleDetail.id",
                //"GROUP_CONCAT(PaymentMethod.code) as group_payment_code",
                //"GROUP_CONCAT(PaymentMethod.name) as group_payment_name",
                //"SUM(OrderDetailPayment.amount) as sum_payment",
                "OrderDetailPayment.id",
                "OrderDetailPayment.amount",
                "PaymentMethod.name",
                "PaymentMethod.id"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'OrderDetailPayment',
                    'table' => Environment::read('table_prefix') . 'order_detail_payments',
                    'type' => 'left',
                    'conditions' => array(
                        'OrderDetailPayment.order_id = Order.id'
                    ),
                ),
                array(
                    'alias' => 'PaymentMethod',
                    'table' => Environment::read('table_prefix') . 'payment_methods',
                    'type' => 'left',
                    'conditions' => array(
                        'PaymentMethod.id = OrderDetailPayment.payment_method_id'
                    ),
                ),
                array(
                    'alias' => 'ScheduleDetail',
                    'table' => Environment::read('table_prefix') . 'schedule_details',
                    'type' => 'left',
                    'conditions' => array(
                        'ScheduleDetail.id = Order.schedule_detail_id',
                    ),
                ),
            ),
            'contain' => array (
                'OrderDetail' => array()
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(

            ),
            'group' => array(
                'Order.id',
                'PaymentMethod.id',
            )
        );
        $result = $obj->find('all', $all_settings);


        // group by by date in code because cannot sum grand total in query
        $result_format = array();
        $result_format_total = array();

        foreach ($result as $k => $v) {
            $key_method_id = strtoupper( $v['PaymentMethod']['name'] );
            $key_date = date('Y-m-d', strtotime($v['Order']['date']));

            if (!empty($v['ScheduleDetail']['id'])) {
                if (isset($result_format[$key_date][$key_method_id])) {
                    $result_format[$key_date][$key_method_id]['amount']+= $v['OrderDetailPayment']['amount'];
                } else {
                    $result_format[$key_date][$key_method_id]['amount']= $v['OrderDetailPayment']['amount'];

                    //$result_format[$key_date][$key_method_id]['name']= $v['PaymentMethod']['name'];
                }
                if (isset($result_format_total[$key_date])) {
                    $result_format_total[$key_date]['amount'] += $v['OrderDetailPayment']['amount'];
                } else {
                    $result_format_total[$key_date]['amount'] = $v['OrderDetailPayment']['amount'];
                }
            }
        }


        /////// Purchase //////
        $obj = ClassRegistry::init('Pos.Purchase');

        $conditions = array(
            'Purchase.date >=' => $begining_of_month . " " . $time_condition,
            'Purchase.date <' => $first_day_next_month . " " . $time_condition,
            'Purchase.status' => 3,
            'Purchase.void' => 0
        );

        $all_settings = array(
            'fields' => array(
                "Purchase.id",
                "Purchase.grand_total",
                "Purchase.date",
                "DATE(Purchase.date) as transaction_date",
                //"GROUP_CONCAT(PaymentMethod.id, '') as payment_method_id_group",
                //"GROUP_CONCAT(PaymentMethod.code) as group_payment_code",
                //"GROUP_CONCAT(PaymentMethod.name) as group_payment_name",
                //"SUM(OrderDetailPayment.amount) as sum_payment",
                "PurchaseDetailPayment.id",
                "PurchaseDetailPayment.amount",
                "PaymentMethod.name",
                "PaymentMethod.id"
            ),
            'conditions' => array($conditions),
            'joins' => array(
                array(
                    'alias' => 'PurchaseDetailPayment',
                    'table' => Environment::read('table_prefix') . 'purchase_detail_payments',
                    'type' => 'left',
                    'conditions' => array(
                        'PurchaseDetailPayment.purchase_id = Purchase.id'
                    ),
                ),
                array(
                    'alias' => 'PaymentMethod',
                    'table' => Environment::read('table_prefix') . 'payment_methods',
                    'type' => 'left',
                    'conditions' => array(
                        'PaymentMethod.id = PurchaseDetailPayment.payment_method_id'
                    ),
                ),
            ),
            'contain' => array (
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(

            ),
            'group' => array(
                'Purchase.id',
                'PaymentMethod.id',
            )
        );
        $result_purchase = $obj->find('all', $all_settings);

        $result_format_purchase = array();
        $result_format_purchase_total = array();

        foreach ($result_purchase as $k => $v) {
            $key_method_id = strtoupper($v['PaymentMethod']['name']);
            $key_date = date('Y-m-d', strtotime($v['Purchase']['date']));

            if (isset($result_format_purchase[$key_date][$key_method_id])) {
                $result_format_purchase[$key_date][$key_method_id]['amount']+= $v['PurchaseDetailPayment']['amount'];
            } else {
                $result_format_purchase[$key_date][$key_method_id]['amount']= $v['PurchaseDetailPayment']['amount'];
            }
            if (isset($result_format_purchase_total[$key_date])) {
                $result_format_purchase_total[$key_date]['amount'] += $v['PurchaseDetailPayment']['amount'];
            } else {
                $result_format_purchase_total[$key_date]['amount'] = $v['PurchaseDetailPayment']['amount'];
            }
        }

        /////// Member //////
        $objRenewalPaymentLog = ClassRegistry::init('Member.RenewalPaymentLog');
        $conditions = array(
            'RenewalPaymentLog.date >=' => $begining_of_month . " " . $time_condition,
            'RenewalPaymentLog.date <' => $first_day_next_month . " " . $time_condition,
        );

        $all_settings = array(
            'fields' => array(

            ),
            'conditions' => array($conditions),
            'joins' => array(
            ),
            'contain' => array (
            ),
            //'limit' => Environment::read('web.limit_record'),
            'order' => array(
            ),
            'group' => array(

            )
        );
        $result_member = $objRenewalPaymentLog->find('all', $all_settings);

        $result_format_member = array();
        $result_format_member_total = array();

        foreach ($result_member as $k=>$v) {
            $key_method_id = strtoupper(str_replace('_', " ", $v['RenewalPaymentLog']['payType']));
            $key_date = date('Y-m-d', strtotime($v['RenewalPaymentLog']['date']));
            if (isset($result_format_member[$key_date][$key_method_id])) {
                $result_format_member[$key_date][$key_method_id]['amount']+= $v['RenewalPaymentLog']['amt'] / 100;
            } else {
                $result_format_member[$key_date][$key_method_id]['amount']= $v['RenewalPaymentLog']['amt'] / 100;

                $name = strtoupper(str_replace('_', " ", $v['RenewalPaymentLog']['payType']));
                $result_format_member[$key_date][$key_method_id]['name']= $name;

            }
            if (isset($result_format_member_total[$key_date])) {
                $result_format_member_total[$key_date]['amount'] += $v['RenewalPaymentLog']['amt'] / 100;
            } else {
                $result_format_member_total[$key_date]['amount'] = $v['RenewalPaymentLog']['amt'] / 100;
            }
        }


        $return_result = array(
            'order' => array(
                'result_format' => $result_format,
                'result_format_total' => $result_format_total,
            ),
            'purchase' => array(
                'result_format' => $result_format_purchase,
                'result_format_total' => $result_format_purchase_total,
            ),
            'member' => array(
                'result_format' => $result_format_member,
                'result_format_total' => $result_format_member_total,
            ),
        );

        return
            $return_result;
    }
}
