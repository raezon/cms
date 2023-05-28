<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\CustomerAddress;
use App\Model\Notification;
use App\Model\Order;
use App\Model\TableOrder;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use function App\CentralLogics\translate;
use Carbon\Carbon;


class OrderController extends Controller
{
    public function list(Request $request, $status)
    {
        
        $query_param = [];
        $search = $request['search'];
        $from = $request['from'];
        $to = $request['to'];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = Order::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            })
            ->when($from && $to, function ($query) use($from, $to){
                $query->whereBetween('created_at', [$from, $to]);
            });
            $query_param = ['search' => $request['search']];
        }
        else {
            if (session()->has('branch_filter') == false) {
                session()->put('branch_filter', 0);
            }
            Order::where(['checked' => 0])->update(['checked' => 1]);

            //all branch
            if (session('branch_filter') == 0) {
                if ($status == 'schedule') {
                    $query = Order::with(['customer', 'branch'])->schedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } elseif ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } else {
                    $query = Order::with(['customer', 'branch'])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                }
            } //selected branch
            else {
                if ($status == 'schedule') {
                    $query = Order::with(['customer', 'branch'])->where('branch_id', session('branch_filter'))->schedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } elseif ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status, 'branch_id' => session('branch_filter')])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } else {
                    $query = Order::with(['customer', 'branch'])->where(['branch_id' => session('branch_filter')])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                }
            }
            $query_param = ['branch' => $request->branch, 'from' => $request->from, 'to' => $request->to];
        }

        $order_count = [
            'pending' =>    Order::notPos()->notDineIn()->where(['order_status'=>'pending'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'confirmed' =>  Order::notPos()->notDineIn()->where(['order_status'=>'confirmed'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'processing' => Order::notPos()->notDineIn()->where(['order_status'=>'processing'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'out_for_delivery' => Order::notPos()->notDineIn()->where(['order_status'=>'out_for_delivery'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'delivered' =>  Order::notPos()->notDineIn()->where(['order_status'=>'delivered'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'canceled' =>   Order::notPos()->notDineIn()->where(['order_status'=>'canceled'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'returned' =>   Order::notPos()->notDineIn()->where(['order_status'=>'returned'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
            'failed' =>     Order::notPos()->notDineIn()->where(['order_status'=>'failed'])
                ->when(!is_null($from) && !is_null($to), function ($query) use($from, $to) {
                    $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                })->count(),
        ];
     

        $orders = $query->notPos()->notDineIn()->latest()->paginate(Helpers::getPagination())->appends($query_param);
        return view('admin-views.order.list', compact('orders', 'status', 'search', 'from', 'to', 'order_count'));
    }

    public function details($id)
    {
        $order = Order::with(['details', 'customer', 'delivery_address', 'branch', 'delivery_man'])
            ->where(['id' => $id])
            ->first();

      //        dd ($order->branch);

        if(!isset($order)) {
            Toastr::info(translate('No more orders!'));
            return back();
        }

        //remaining delivery time
        $delivery_date_time =  $order['delivery_date']. ' ' .$order['delivery_time'];
        $ordered_time = Carbon::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s", strtotime($delivery_date_time)));
        $remaining_time = $ordered_time->add($order['preparation_time'], 'minute')->format('Y-m-d H:i:s');
        $order['remaining_time'] = $remaining_time;

        return view('admin-views.order.order-view', compact('order'));
    }

    public function search(Request $request)
    {
        $key = explode(' ', $request['search']);
        $orders = Order::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->get();
        return response()->json([
            'view' => view('admin-views.order.partials._table', compact('orders'))->render()
        ]);
    }

    public function status(Request $request)
    {
        $order = Order::find($request->id);
        if (($request->order_status == 'delivered' || $request->order_status == 'out_for_delivery') && $order['delivery_man_id'] == null && $order['order_type'] != 'take_away') {
            Toastr::warning(translate('Please assign delivery man first!'));
            return back();
        }
        if($request->order_status == 'completed' && $order->payment_status != 'paid') {
            Toastr::warning(translate('Please update payment status first!'));
            return back();
        }
        $order->order_status = $request->order_status;
        $order->save();

        $fcm_token = null;
        if(isset($order->customer)) {
            $fcm_token = $order->customer->cm_firebase_token;
        }

        $value = Helpers::order_status_update_message($request->order_status);
        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=>'order_status',
                ];
                if(isset($fcm_token)) {
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }

            }
        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification send failed for Customer!'));
        }

        //delivery man notification
        if ($request->order_status == 'processing' && $order->delivery_man != null) {
            $fcm_token = $order->delivery_man->fcm_token;
            $value = translate('One of your order is in processing');
            try {
                if ($value) {
                    $data = [
                        'title' => translate('Order'),
                        'description' => $value,
                        'order_id' => $order['id'],
                        'image' => '',
                    ];
                    Helpers::send_push_notif_to_device($fcm_token, $data);
                }
            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification failed for DeliveryMan!'));
            }
        }

        //kitchen order notification
        if($request->order_status == 'confirmed') {
            $data = [
                'title' => translate('You have a new order - (Order Confirmed).'),
                'description' => $order->id,
                'order_id' => $order->id,
                'image' => '',
            ];

            try {
                Helpers::send_push_notif_to_topic($data, "kitchen-{$order->branch_id}",'general');

            } catch (\Exception $e) {
                Toastr::warning(translate('Push notification failed!'));
            }
        }
        $table_order = TableOrder::where(['id' => $order->table_order_id])->first();

        if($request->order_status == 'completed' && $order->payment_status == 'paid') {

            if (isset($table_order->id)){
                //dd($table_order);
                $orders = Order::where(['table_order_id' => $table_order->id])->get();
                $status = 1;
                foreach ($orders as $order){
                    if($order->order_status != 'completed'){
                        $status=0;
                        break;
                    }
                }

                if ($status == 1){
                    $table_order->branch_table_token_is_expired = 1;
                    //dd($table_order);
                    $table_order->save();
                }
            }
        }

        if($request->order_status == 'canceled') {

            if (isset($table_order->id)){
                //dd($table_order);
                $orders = Order::where(['table_order_id' => $table_order->id])->get();
                $status = 1;
                foreach ($orders as $order){
                    if($order->order_status != 'canceled'){
                        $status=0;
                        break;
                    }
                }

                if ($status == 1){
                    $table_order->branch_table_token_is_expired = 1;
                    //dd($table_order);
                    $table_order->save();
                }
            }
        }

        Toastr::success(translate('Order status updated!'));
        return back();
    }

    public function preparation_time(Request $request, $id)
    {
        $order = Order::with(['customer'])->find($id);
        $delivery_date_time =  $order['delivery_date']. ' ' .$order['delivery_time'];

        $ordered_time = Carbon::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s", strtotime($delivery_date_time)));
        $remaining_time = $ordered_time->add($order['preparation_time'], 'minute')->format('Y-m-d H:i:s');

        //if delivery time is not over
        if (strtotime(date('Y-m-d H:i:s')) < strtotime($remaining_time)) {
            $delivery_time = new DateTime($remaining_time); //time when preparation will be over
            $current_time = new DateTime(); // time now
            $interval = $delivery_time->diff($current_time);
            $remainingMinutes = $interval->i;
            $remainingMinutes += $interval->days * 24 * 60;
            $remainingMinutes += $interval->h * 60;

            $order->preparation_time += ($request->extra_minute - $remainingMinutes);

        } else {
            //if delivery time is over
            $delivery_time = new DateTime($remaining_time);
            $current_time = new DateTime();
            $interval = $delivery_time->diff($current_time);
            $diffInMinutes = $interval->i;
            $diffInMinutes += $interval->days * 24 * 60;
            $diffInMinutes += $interval->h * 60;

            $order->preparation_time += $diffInMinutes + $request->extra_minute;
        }
        $order->save();

        //notification send
        $customer = $order->customer;
        $fcm_token = null;
        if (isset($customer)) {
            $fcm_token = $customer->cm_firebase_token;
        }
        $value = Helpers::order_status_update_message('customer_notify_message_for_time_change');

        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=>'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            } else {
                throw new \Exception(translate('failed'));
            }

        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification send failed for Customer!'));
        }

        Toastr::success(translate('Order preparation time increased'));
        return back();
    }


    public function add_delivery_man($order_id, $delivery_man_id)
    {
        if ($delivery_man_id == 0) {
            return response()->json([], 401);
        }
        $order = Order::find($order_id);
        if($order->order_status == 'delivered' ||
         $order->order_status == 'returned' ||
          $order->order_status == 'failed' || 
          $order->order_status == 'canceled' || 
          $order->order_status == 'scheduled') {
            return response()->json(['status' => false], 200);
        }
        $order->delivery_man_id = $delivery_man_id;
        $order->save();

        $fcm_token = $order->delivery_man->fcm_token;
        $customer_fcm_token = null;
        if(isset($order->customer)) {
            $customer_fcm_token = $order->customer->cm_firebase_token;
        }
        $value = Helpers::order_status_update_message('del_assign');
        try {
            if ($value) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => $order_id,
                    'image' => '',
                    'type'=>'order_status',
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
                if(isset($order->customer)) {
                    $data['description'] = Helpers::order_status_update_message('customer_notify_message');
                }
                if(isset($customer_fcm_token)) {
                    Helpers::send_push_notif_to_device($customer_fcm_token, $data);
                }
            }
        } catch (\Exception $e) {
            Toastr::warning(translate('Push notification failed for DeliveryMan!'));
        }

        return response()->json(['status' => true], 200);
    }

    public function payment_status(Request $request)
    {
        $order = Order::find($request->id);
        if ($request->payment_status == 'paid' && $order['transaction_reference'] == null && $order['payment_method'] != 'cash_on_delivery' && $order['order_type'] != 'dine_in') {
            Toastr::warning(translate('Add your payment reference code first!'));
            return back();
        }
        $order->payment_status = $request->payment_status;
        $order->save();
        Toastr::success(translate('Payment status updated!'));
        return back();
    }

    public function update_shipping(Request $request, $id)
    {
//        return $request;
        $request->validate([
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required|min:5|max:20',
            'address' => 'required'
        ]);

        $address = [
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'road' => $request->road,
            'house' => $request->house,
            'floor' => $request->floor,
            'address' => $request->address,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'created_at' => now(),
            'updated_at' => now()
        ];
        if ($id) {
            DB::table('customer_addresses')->where('id', $id)->update($address);
            Toastr::success(translate('Address updated!'));
        }
        else {
            $address = new CustomerAddress;
            $address->contact_person_name = $request->input('contact_person_name');
            $address->contact_person_number = $request->input('contact_person_number');
            $address->address_type = $request->input('address_type');
            $address->address = $request->input('address');
            $address->longitude = $request->input('longitude');
            $address->latitude = $request->input('latitude');
            $address->user_id = $request->input('user_id');
            $address->house = $request->house;
            $address->floor = $request->floor;
            $address->address = $request->address;
            $address->save();
            Order::where('id', $request->input('order_id'))->update(['delivery_address_id' => $address->id]);
            Toastr::success(translate('Address added!'));
        }
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where('id', $id)->first();
        return view('admin-views.order.invoice', compact('order'));
    }

    public function add_payment_ref_code(Request $request, $id)
    {
        Order::where(['id' => $id])->update([
            'transaction_reference' => $request['transaction_reference']
        ]);

        Toastr::success(translate('Payment reference code is added!'));
        return back();
    }

    public function branch_filter($id)
    {
        session()->put('branch_filter', $id);
        return back();
    }

    public function export_data()
    {
        $orders = Order::all();
        return (new FastExcel($orders))->download('orders.xlsx');
    }

    public function export_excel(Request $request)
    {
        $status =$request->status;
        $query_param = [];
        $search = $request['search'];
        $from = $request['from'];
        $to = $request['to'];

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = Order::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            })
                ->when($from && $to, function ($query) use($from, $to){
                    $query->whereBetween('created_at', [$from, $to]);
                });
        }
        else {
            if (session()->has('branch_filter') == false) {
                session()->put('branch_filter', 0);
            }

            //all branch
            if (session('branch_filter') == 0) {
                if ($status == 'schedule') {
                    $query = Order::with(['customer', 'branch'])->schedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } elseif ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status])->notSchedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } else {

                    $query = Order::with(['customer', 'branch'])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                }
            } //selected branch
            else {
                if ($status == 'schedule') {
                    $query = Order::with(['customer', 'branch'])->where('branch_id', session('branch_filter'))->schedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } elseif ($status != 'all') {
                    $query = Order::with(['customer', 'branch'])->where(['order_status' => $status, 'branch_id' => session('branch_filter')])->notSchedule()
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                } else {
                    $query = Order::with(['customer', 'branch'])->where(['branch_id' => session('branch_filter')])
                        ->when($from && $to, function ($query) use($from, $to){
                            $query->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
                        });;
                }
            }
        }

        $orders = $query->notPos()->notDineIn()->latest()->get();
        if ($orders->count()<1) {
            Toastr::warning('No Data Available');
            return back();
        }
        $data = array();
        foreach ($orders as $key =>$order) {
            $data[] = array(
                'SL' => ++$key,
                'Order ID' => $order->id,
                'Order Date' => date('d M Y h:m A',strtotime($order['created_at'])),
                'Customer Info' => $order['user_id'] == null? 'Walk in Customer' : ($order->customer == null? 'Customer Unavailable' : $order->customer['f_name']. ' '. $order->customer['l_name']),
                'Branch' => $order->branch? $order->branch->name : 'Branch Deleted',
                'Total Amount' => Helpers::set_symbol($order['order_amount']),
                'Payment Status' => $order->payment_status=='paid'? 'Paid' : 'Unpaid',
                'Order Status' => $order['order_status']=='pending'? 'Pending' : ($order['order_status']=='confirmed'? 'Confirmed' : ($order['order_status']=='processing' ? 'Processing' : ($order['order_status']=='delivered'? 'Delivered': ($order['order_status']=='picked_up'? 'Out For Delivery' : str_replace('_',' ',$order['order_status']))))),
            );
        }
        return (new FastExcel($data))->download('Order_List.xlsx');

    }

    public function ajax_change_delivery_time_date(Request $request)
    {
        $order = Order::where('id', $request->order_id)->first();
        if(!$order) {
            return response()->json(['status' => false]);
        }
        $order->delivery_date = $request->input('delivery_date')?? $order->delivery_date;
        $order->delivery_time = $request->input('delivery_time')?? $order->delivery_time;
        $order->save();

        return response()->json(['status' => true]);

    }

}
