<?php

namespace App\Http\Controllers\Admin\SpecialDateTime;

use App\Http\Controllers\Controller;
use App\Model\Admin\SpecialDateTime;
use App\Model\Admin\Time;
use App\Model\Admin\Date;
use App\Model\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Validator;

class SpecialDateTimeController extends Controller
{
    public function __construct(SpecialDateTime $special_datetime, Time $time, Date $date)
    {
        $this->special_datetime = $special_datetime;
        $this->time             = $time;
        $this->date             = $date;
    }

    public function index(Request $request)
    {
		$model_special_datetime = $this->special_datetime;
		
		$offset     = 10;
		$start_page = 1;
		$page                  = $request->get('page');
		$page_special_datetime = $this->indexTable($page, $offset);

        $special_datetime = $model_special_datetime->orderBy('date', 'desc')->paginate($offset);
        $special_datetime->setPath(URL::current());

    	return view('User.Admin.SpecialDateTime.index',[
			'special_datetime'       => $special_datetime,
			'model_special_datetime' => $model_special_datetime,
			'page_special_datetime'  => $page_special_datetime
    	]);
    }

    public function addSpecialHour()
    {
        $model_special_datetime = $this->special_datetime;
        $model_time             = $this->time;

        // Create array time slot
        $array_time_slot = array();
        $time_slots = $model_time->all();
        foreach ($time_slots as $time_slot) {
            $array_time_slot[$time_slot->id] = $time_slot->name;
        }
        
    	return view('User.Admin.SpecialDateTime.add_special_time', [
            'model_special_datetime' => $model_special_datetime,
            'array_time_slot'        => $array_time_slot
    	]);
    }

    public function storeSpecialHour(Request $request)
    {
        $time_request = $request->get('time');
        if(!empty($datetime_request['time_slot_start'])){
            $datetime_request['time_slot_start'] = intval($datetime_request['time_slot_start']);
        }
        if(!empty($datetime_request['time_slot_end'])){
            $datetime_request['time_slot_end'] = intval($datetime_request['time_slot_end']);
        }
        $time_request['increase_price']  = preg_replace('/[^0-9]/', '', $time_request['increase_price']);

        $this->validatorSpecialTime($time_request)->validate();

        $model_special_datetime = $this->special_datetime;
        $model_time = $this->time;
        $array_time = array();
        for ($time=$time_request['time_slot_start']; $time <= $time_request['time_slot_end']; $time++) { 
            $isset_time = $model_special_datetime->where('time_slot_id', '=', $time)
                ->whereNull('date')->count();
            if($isset_time > 0){
                continue;
            }
            array_push($array_time, $time);
        }

        $name_route = 'admin.specialdatetime';
        // Return if array null
        if(empty($array_time)){
            return redirect()->route($name_route)
                ->with('error', 'Giờ bạn chọn đã tồn tại');
        }

        // Save all dates
        foreach ($array_time as $time_slot_id) {
            $time_slot = $model_time->where('id', '=', $time_slot_id)->first();
            $special_date                 = new SpecialDateTime();
            $special_date->time_slot_id   = $time_slot_id;
            $special_date->time_slot_name = $time_slot->name;
            $special_date->date           = null;
            $special_date->increase_price = $time_request['increase_price'];
            $special_date->status         = ACTIVE;
            $special_date->created_at     = Helper::getCurrentDateTime();
            $special_date->updated_at     = Helper::getCurrentDateTime();
            $special_date->save();
        }

        return redirect()->route($name_route)
            ->with('success', 'Bạn đã thêm mới khoảng thời gian tăng giá');
    }

    public function addSpecialDate()
    {
		$model_special_datetime = $this->special_datetime;
    	return view('User.Admin.SpecialDateTime.add_special_date', [
    		'model_special_datetime' => $model_special_datetime
    	]);
    }

    public function storeSpecialDate(Request $request)
    {
        $date_request = $request->get('date');
        $date_request['increase_price'] = preg_replace('/[^0-9]/', '', $date_request['increase_price']);

        $this->validatorSpecialDate($date_request)->validate();

        // Get all dates between two dates
        $model_special_datetime = $this->special_datetime;
        $model_date = $this->date;
        $array_date = array();
        $date_start = new \DateTime($date_request['date_start']);
        $interval   = new \DateInterval('P1D');
        $date_end   = new \DateTime($date_request['date_end']);
        $date_end->setTime(0,0,1);
        $date_period = new \DatePeriod($date_start, $interval, $date_end);
        foreach ($date_period as $date) {
            $date = $date->format('Y-m-d');
            $isset_date = $model_special_datetime->where('date', '=', $date)
                ->whereNull('time_slot_id')->count();
            if($isset_date > 0){
                continue;
            }
            array_push($array_date, $date);
        }


        $name_route = 'admin.specialdatetime';
        // Return if array null
        if(empty($array_date)){
            return redirect()->route($name_route)
                ->with('error', 'Ngày bạn chọn đã tồn tại');
        }


        // Save all dates
        foreach ($array_date as $value_date) {
            $special_date                 = new SpecialDateTime();
            $special_date->time_slot_id   = null;
            $special_date->time_slot_name = null;
            $special_date->date           = $value_date;
            $special_date->increase_price = $date_request['increase_price'];
            $special_date->status         = ACTIVE;
            $special_date->created_at     = Helper::getCurrentDateTime();
            $special_date->updated_at     = Helper::getCurrentDateTime();
            $special_date->save();
        }

        return redirect()->route($name_route)
            ->with('success', 'Bạn đã thêm mới khoảng thời gian tăng giá');
    }

    public function addSelectSpecialDateTime()
    {
        $model_special_datetime = $this->special_datetime;
        $model_time             = $this->time;

        // Create array time slot
        $array_time_slot = array();
        $time_slots = $model_time->all();
        foreach ($time_slots as $time_slot) {
            $array_time_slot[$time_slot->id] = $time_slot->name;
        }

    	return view('User.Admin.SpecialDateTime.add_special_datetime', [
            'model_special_datetime' => $model_special_datetime,
            'array_time_slot'        => $array_time_slot
    	]);
    }

    public function storeSpecialDateTime(Request $request)
    {
        $datetime_request = $request->get('datetime');
        if(!empty($datetime_request['time_slot_start'])){
            $datetime_request['time_slot_start'] = intval($datetime_request['time_slot_start']);
        }
        if(!empty($datetime_request['time_slot_end'])){
            $datetime_request['time_slot_end'] = intval($datetime_request['time_slot_end']);
        }
        $datetime_request['increase_price']  = preg_replace('/[^0-9]/', '', $datetime_request['increase_price']);
        
        $this->validatorSpecialDateTime($datetime_request)->validate();

        $model_special_datetime = $this->special_datetime;
        $model_time = $this->time;
        $model_date = $this->date;
        $array_time = array();
        $array_date = array();
        $array_datetime = array();
        $array_value_datetime = array();
        // Get all times between two times
        for ($time = $datetime_request['time_slot_start']; $time <= $datetime_request['time_slot_end']; $time++) {
            array_push($array_time, $time);
        }
        // Get all dates between two dates
        $date_start = new \DateTime($datetime_request['date_start']);
        $interval   = new \DateInterval('P1D');
        $date_end   = new \DateTime($datetime_request['date_end']);
        $date_end->setTime(0,0,1);
        $date_period = new \DatePeriod($date_start, $interval, $date_end);
        foreach ($date_period as $date) {
            $date = $date->format('Y-m-d');
            array_push($array_date, $date);
        }

        foreach ($array_date as $date) {
            foreach ($array_time as $time) {
                $isset_datetime = $model_special_datetime->where('date', '=', $date)
                    ->where('time_slot_id', '=', $time)->count();
                if($isset_datetime > 0){
                    continue;
                }
                $array_value_datetime = array(
                    'date'         => $date,
                    'time_slot_id' => $time,
                );
                array_push($array_datetime, $array_value_datetime);
            }
        }

        $name_route = 'admin.specialdatetime';
        // Return if array null
        if(empty($array_datetime)){
            return redirect()->route($name_route)
                ->with('error', 'Khoảng thời gian bạn chọn đã tồn tại');
        }

        // Save all dates
        foreach ($array_datetime as $datetime) {
            $time_slot = $model_time->where('id', '=', $datetime['time_slot_id'])->first();
            $special_date                 = new SpecialDateTime();
            $special_date->time_slot_id   = $time_slot->id;
            $special_date->time_slot_name = $time_slot->name;
            $special_date->date           = $datetime['date'];
            $special_date->increase_price = $datetime_request['increase_price'];
            $special_date->status         = ACTIVE;
            $special_date->created_at     = Helper::getCurrentDateTime();
            $special_date->updated_at     = Helper::getCurrentDateTime();
            $special_date->save();
        }

        return redirect()->route($name_route)
            ->with('success', 'Bạn đã thêm mới khoảng thời gian tăng giá');
    }


    private $array_validate = [
        'increase_price' => ['required', 'string', 'min:5', 'max:7'],
    ];

    private function validatorSpecialDate(array $data)
    {
        $array_validate = $this->array_validate;
        $array_validate['date_start'] = ['required', 'date_format:Y-m-d'];
        $array_validate['date_end']   = ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'];
        return Validator::make($data, $array_validate, $this->messages());
    }

    private function validatorSpecialTime(array $data)
    {
        $array_validate = $this->array_validate;
        $array_validate['time_slot_start'] = ['required', 'integer'];
        $array_validate['time_slot_end']   = ['required', 'integer', 'gte:time_slot_start'];
        return Validator::make($data, $array_validate, $this->messages());
    }

    private function validatorSpecialDateTime(array $data)
    {
        $array_validate = $this->array_validate;
        $array_validate['date_start']      = ['required', 'date_format:Y-m-d'];
        $array_validate['date_end']        = ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'];
        $array_validate['time_slot_start'] = ['required', 'integer'];
        $array_validate['time_slot_end']   = ['required', 'integer', 'gte:time_slot_start'];
        return Validator::make($data, $array_validate, $this->messages());
    }

    private function messages()
    {
        return [
            'required'    => 'Không được để trống',
            'string'      => 'Sai định dạng',
            'max'         => 'Sai định dạng, dài hơn :max ký tự',
            'min'         => 'Sai định dạng, ngắn hơn :min ký tự',
            'date_format' => 'Sai định dạng',
            'status.max'       => 'Sai định dạng',
            'status.min'       => 'Sai định dạng',
            'date_special.max' => 'Sai định dạng',
            'date_special.min' => 'Sai định dạng',
            'date_end.after_or_equal' => 'Ngày không hợp lệ',
            'time_slot_end.gte'       => 'Giờ không hợp lệ',
        ];
    }
}
