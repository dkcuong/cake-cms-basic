<?php
App::uses('AppController', 'Controller');
/**
 * Home Controller
 *
 */
class TicketingpageController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler');

	public function index() {

		$data   = $this->request->query;

		$objSetting = ClassRegistry::init('Setting.Setting');
		$preorder_day = $objSetting->get_value('preorder-day');

		if ($preorder_day <= 0) {
			$preorder_day = 3;
		}

		$current_date = date('Y-m-d');
		$first_date = $current_date;
		$objSchedule = ClassRegistry::init('Movie.Schedule');
		$option_get_date = array(
			'fields' => array(
				'ScheduleDetail.date',
			),
			'joins' => array(
				array(
					'alias' => 'ScheduleDetail',
					'table' => Environment::read('table_prefix') . 'schedule_details',
					'type' => 'left',
					'conditions' => array(
						'Schedule.id = ScheduleDetail.schedule_id',
					),
				),
			),
			'conditions' => array(
				'ScheduleDetail.date >= ' => $current_date 
			),
			'group' => array('Schedule.id', 'ScheduleDetail.date'),
			'order' => array('ScheduleDetail.date' => 'asc'),
			'limit' => $preorder_day
		);

		$data_schedule_date = $objSchedule->find('all', $option_get_date);

		$is_set = false;
		$list = array();
		foreach($data_schedule_date as $data_date) {
			$new_date = date('Y-m-d', strtotime($data_date['ScheduleDetail']['date']));
			if (!$is_set) {
				$first_date = $new_date;
				$is_set = true;
			}
			$label = ($new_date == $current_date) ? 'Today - ' . date('d/m/Y', strtotime($new_date)) : date('d/m/Y', strtotime($new_date)) . date('(D)', strtotime($new_date));
			$list[$new_date] = array('label' => $label);
		}

		$staff = $this->Session->read('staff');
		//$user_roles = $staff['Staff']['role'];
		// $is_manager = ($staff['Staff']['role'] == 'manager') ? 1 : 0;
		$is_manager = false;
		$is_today = ($first_date == $current_date) ? true : false; 

		$data_schedule =  $objSchedule->get_schedule($data, $first_date, $is_manager, $is_today);

        $list_movie_id = Hash::extract($data_schedule, '{n}.movie_id');
        // get movie name
        $objMovie = ClassRegistry::init('Movie.Movie');
        $list_name_movie = $objMovie->get_movie_name($list_movie_id);

		$schedule['Schedule'] = $list;
		$schedule['Schedule'][$first_date]['Movie'] = $data_schedule;

		$current_schedule = $schedule['Schedule'][$first_date];
		$schedule_json = json_encode($schedule['Schedule']);

		$display_link_signout = true;

		$this->set(compact('schedule', 'current_schedule', 'schedule_json', 'display_link_signout', 'list_name_movie', 'first_date'));
	}
}