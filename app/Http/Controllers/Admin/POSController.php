<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\CPU\BackEndHelper;
use App\Http\Controllers\Controller;
use App\Model\AddOn;
use App\Model\Admin;
use App\Model\Branch;
use App\Model\Category;
use App\Model\Coupon;
use App\Model\CustomerAddress;
use App\Model\Notification;
use App\Model\Product;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Table;
use App\Model\TableOrder;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MercadoPago\Customer;
use Rap2hpoutre\FastExcel\FastExcel;
use function App\CentralLogics\translate;

class POSController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category_id', 0);
        $categories = Category::active()->get();
        $keyword = $request->keyword;
        $key = explode(' ', $keyword);
        $selected_customer =User::where('id', session('customer_id'))->first();
        $selected_table =Table::where('id', session('table_id'))->first();

        $products = Product::
        when($request->has('category_id') && $request['category_id'] != 0, function ($query) use ($request) {
            $query->whereJsonContains('category_ids', [['id' => (string)$request['category_id']]]);
        })->when($keyword, function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
        })
            ->active()->latest()->paginate(Helpers::getPagination());

        $current_branch = Admin::find(auth('admin')->id());
        $branches = Branch::select('id', 'name')->get();
        return view('admin-views.pos.index', compact('categories', 'products', 'category', 'keyword', 'current_branch', 'branches', 'selected_customer', 'selected_table'));
    }

    public function quick_view(Request $request)
    {
        $product = Product::findOrFail($request->product_id);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos._quick-view-data', compact('product'))->render(),
        ]);
    }

    public function variant_price(Request $request)
    {
        //dd($request->all());
        $product = Product::find($request->id);

        $str = '';
        $quantity = 0;
        $price = 0;
        $addon_price = 0;

        foreach (json_decode($product->choice_options) as $key => $choice) {
            if ($str != null) {
                $str .= '-' . str_replace(' ', '', $request[$choice->name]);
            } else {
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }

        if ($request['addon_id']) {
            foreach ($request['addon_id'] as $id) {
                $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
            }
        }

        if ($str != null) {
            $count = count(json_decode($product->variations));
            for ($i = 0; $i < $count; $i++) {
                if (json_decode($product->variations)[$i]->type == $str) {
                    $price = json_decode($product->variations)[$i]->price - Helpers::discount_calculate($product, $product->price);
                }
            }
        } else {
            $price = $product->price - Helpers::discount_calculate($product, $product->price);
        }

        return array('price' =>  \App\CentralLogics\Helpers::set_symbol(($price * $request->quantity) + $addon_price));
    }

    public function get_customers(Request $request)
    {
        $key = explode(' ', $request['q']);
        $data = DB::table('users')
            ->where(['user_type' => null])
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            })
            ->whereNotNull(['f_name', 'l_name', 'phone'])
            ->limit(8)
            ->get([DB::raw('id, CONCAT(f_name, " ", l_name, " (", phone ,")") as text')]);

        $data[] = (object)['id' => false, 'text' => translate('walk_in_customer')];

        return response()->json($data);
    }

    public function update_tax(Request $request)
    {
        if ($request->tax < 0) {
            Toastr::error(translate('Tax_can_not_be_less_than_0_percent'));
            return back();
        } elseif ($request->tax > 100) {
            Toastr::error(translate('Tax_can_not_be_more_than_100_percent'));
            return back();
        }

        $cart = $request->session()->get('cart', collect([]));
        $cart['tax'] = $request->tax;
        $request->session()->put('cart', $cart);
        return back();
    }

    public function update_discount(Request $request)
    {
        if ($request->type == 'percent' && $request->discount < 0) {
            Toastr::error(translate('Extra_discount_can_not_be_less_than_0_percent'));
            return back();
        } elseif ($request->type == 'percent' && $request->discount > 100) {
            Toastr::error(translate('Extra_discount_can_not_be_more_than_100_percent'));
            return back();
        }
        $total_price = 0;
        foreach (session('cart') as $cart) {
            if (isset($cart['price'])){
                $total_price += ($cart['price'] - $cart['discount']);
            }
        }

        if ($request->type == 'amount' && $request->discount > $total_price) {
            Toastr::error(translate('Extra_discount_can_not_be_more_total_product_price'));
            return back();
        }

        $cart = $request->session()->get('cart', collect([]));
        $cart['extra_discount_type'] = $request->type;
        $cart['extra_discount'] = $request->discount;

        $request->session()->put('cart', $cart);
        return back();
    }

    public function updateQuantity(Request $request)
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart = $cart->map(function ($object, $key) use ($request) {
            if ($key == $request->key) {
                $object['quantity'] = $request->quantity;
            }
            return $object;
        });
        $request->session()->put('cart', $cart);
        return response()->json([], 200);
    }

    public function addToCart(Request $request)
    {
        $product = Product::find($request->id);

        $data = array();
        $data['id'] = $product->id;
        $str = '';
        $variations = [];
        $price = 0;
        $addon_price = 0;

        //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
        foreach (json_decode($product->choice_options) as $key => $choice) {
            $data[$choice->name] = $request[$choice->name];
            $variations[$choice->title] = $request[$choice->name];
            if ($str != null) {
                $str .= '-' . str_replace(' ', '', $request[$choice->name]);
            } else {
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }
        $data['variations'] = $variations;
        $data['variant'] = $str;
        if ($request->session()->has('cart')) {
            if (count($request->session()->get('cart')) > 0) {
                foreach ($request->session()->get('cart') as $key => $cartItem) {
                    if (is_array($cartItem) && $cartItem['id'] == $request['id'] && $cartItem['variant'] == $str) {
                        return response()->json([
                            'data' => 1
                        ]);
                    }
                }

            }
        }
        //Check the string and decreases quantity for the stock
        if ($str != null) {
            $count = count(json_decode($product->variations));
            for ($i = 0; $i < $count; $i++) {
                if (json_decode($product->variations)[$i]->type == $str) {
                    $price = json_decode($product->variations)[$i]->price;
                }
            }
        } else {
            $price = $product->price;
        }

        $data['quantity'] = $request['quantity'];
        $data['price'] = $price;
        $data['name'] = $product->name;
        $data['discount'] = Helpers::discount_calculate($product, $price);
        $data['image'] = $product->image;
        $data['add_ons'] = [];
        $data['add_on_qtys'] = [];

        if ($request['addon_id']) {
            foreach ($request['addon_id'] as $id) {
                $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                $data['add_on_qtys'][] = $request['addon-quantity' . $id];
            }
            $data['add_ons'] = $request['addon_id'];
        }

        $data['addon_price'] = $addon_price;

        if ($request->session()->has('cart')) {
            $cart = $request->session()->get('cart', collect([]));
            $cart->push($data);
        } else {
            $cart = collect([$data]);
            $request->session()->put('cart', $cart);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function cart_items()
    {
        return view('admin-views.pos._cart');
    }

    public function emptyCart(Request $request)
    {
        session()->forget('cart');
        return response()->json([], 200);
    }

    public function removeFromCart(Request $request)
    {
        if ($request->session()->has('cart')) {
            $cart = $request->session()->get('cart', collect([]));
            $cart->forget($request->key);
            $request->session()->put('cart', $cart);
        }

        return response()->json([], 200);
    }

    public function store_keys(Request $request)
    {
        session()->put($request['key'], $request['value']);
        return response()->json($request['key'], 200);
    }

    //order
    public function order_list(Request $request)
    {

        $query_param = [];
        $search = $request['search'];
        $branch_id = $request['branch_id'];
        $from = $request['from'];
        $to = $request['to'];

        $query = Order::pos()->with(['customer', 'branch']);
        $branches = Branch::all();

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }
        elseif ($request->has('filter')){

            $query->when($from && $to && $branch_id == 'all', function ($q) use($from, $to){
                $q->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            })
                ->when($from && $to && $branch_id != 'all', function ($q) use($from, $to, $branch_id) {
                    $q->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()])
                        ->whereHas('branch', function ($q) use($branch_id){
                            $q->where('id', $branch_id);
                        });
                })
                ->when($from == null && $to == null && $branch_id != 'all', function ($q) use($from, $to, $branch_id) {
                    $q->whereHas('branch', function ($q) use($branch_id){
                            $q->where('id', $branch_id);
                        });
                })
                ->get();
            $query_param = ['filter' => '', 'branch_id' => $request['branch_id']?? '', 'from' => $request['from']?? '', 'to' => $request['to']?? ''];

        }

        $orders = $query->latest()->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.pos.order.list', compact('orders', 'search', 'branches', 'from', 'to', 'branch_id'));
    }

    public function order_details($id)
    {
        $order = Order::with('details')->where(['id' => $id])->first();
        if (isset($order)) {
            return view('admin-views.order.order-view', compact('order'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }

    public function place_order(Request $request)
    {

        if ($request->session()->has('cart')) {
            if (count($request->session()->get('cart')) < 1) {
                Toastr::error(translate('cart_empty_warning'));
                return back();
            }
        } else {
            Toastr::error(translate('cart_empty_warning'));
            return back();
        }

        if (session('people_number') != null && (session('people_number') > 99 || session('people_number') <1)){
            Toastr::error(translate('enter valid people number'));
            return back();
        }

        $cart = $request->session()->get('cart');
        $total_tax_amount = 0;
        $total_addon_price = 0;
        $product_price = 0;
        $order_details = [];

        $order_id = 100000 + Order::all()->count() + 1;
        if (Order::find($order_id)) {
            $order_id = Order::orderBy('id', 'DESC')->first()->id + 1;
        }

        $order = new Order();
        $order->id = $order_id;

        $order->user_id = session()->get('customer_id') ?? null;
        $order->coupon_discount_title = $request->coupon_discount_title == 0 ? null : 'coupon_discount_title';
        $order->payment_status = $request->type == 'pay_after_eating' ? 'unpaid' : 'paid';
        $order->order_status = session()->get('table_id') ? 'confirmed' : 'delivered';
        $order->order_type = session()->get('table_id') ? 'dine_in' : 'pos';
        $order->coupon_code = $request->coupon_code ?? null;
        $order->payment_method = $request->type;
        $order->transaction_reference = $request->transaction_reference ?? null;
        $order->delivery_charge = 0; //since pos, no distance, no d. charge
        $order->delivery_address_id = $request->delivery_address_id ?? null;
        $order->delivery_date = Carbon::now()->format('Y-m-d');
        $order->delivery_time = Carbon::now()->format('H:i:s');
        $order->order_note = null;
        $order->checked = 1;
        $order->created_at = now();
        $order->updated_at = now();

        $total_product_main_price = 0;

        // check if discount is more than total price
        $total_price_for_discount_validation = 0;

        foreach ($cart as $c) {
            if (is_array($c)) {
                $discount_on_product = 0;
                $product_subtotal = ($c['price']) * $c['quantity'];
                $discount_on_product += ($c['discount'] * $c['quantity']);

                $total_price_for_discount_validation += $c['price'];

                $product = Product::find($c['id']);
                if ($product) {
                    $price = $c['price'];

                    $product = Helpers::product_data_formatting($product);
                    $addon_data = Helpers::calculate_addon_price(AddOn::whereIn('id', $c['add_ons'])->get(), $c['add_on_qtys']);

                    //***bypass check for POS variation***
                    $result = [];
                    if(!empty($c['variations'])) {
                        foreach (gettype($product['variations']) == 'array' ? $product['variations'] : json_decode($product['variations'], true) as $product_variation) {
                            //Here 'Size' is coupled with POS order's variation architecture, think before you change
                            if ($product_variation['type'] == current($c['variations']) || $product_variation['type'] == str_replace(" ","",current($c['variations']))) {
                                $result[] = [
                                    'type' => $product_variation['type'],
                                    'price' => Helpers::set_price($product_variation['price'])
                                ];
                            }
                        }
                    }

                    if(count($result) > 0) {
                        $encoded_variation = json_encode($result);
                    } else {
                        $encoded_variation = json_encode([]);
                    }
                    //***end***

                    //*** addon quantity integer casting ***
                    array_walk($c['add_on_qtys'], function (&$add_on_qtys) {
                        $add_on_qtys = (int) $add_on_qtys;
                    });
                    //***end***

                    $or_d = [
                        'product_id' => $c['id'],
                        'product_details' => $product,
                        'quantity' => $c['quantity'],
                        'price' => $price,
                        'tax_amount' => Helpers::tax_calculate($product, $price),
                        'discount_on_product' => Helpers::discount_calculate($product, $price),
                        'discount_type' => 'discount_on_product',
                        'variant' => json_encode($c['variant']),
                        'variation' => $encoded_variation,
                        'add_on_ids' => json_encode($addon_data['addons']),
                        'add_on_qtys' => json_encode($c['add_on_qtys']),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $total_tax_amount += $or_d['tax_amount'] * $c['quantity'];
                    $total_addon_price += $addon_data['total_add_on_price'];
                    $product_price += $product_subtotal - $discount_on_product;
                    $total_product_main_price += $product_subtotal;
                    $order_details[] = $or_d;
                }
            }
        }

        $total_price = $product_price + $total_addon_price;
        if (isset($cart['extra_discount'])) {
            $extra_discount = $cart['extra_discount_type'] == 'percent' && $cart['extra_discount'] > 0 ? (($total_product_main_price * $cart['extra_discount']) / 100) : $cart['extra_discount'];
            $total_price -= $extra_discount;
        }
        if(isset($cart['extra_discount']) && $cart['extra_discount_type'] == 'amount') {
            if ($cart['extra_discount'] > $total_price_for_discount_validation) {
                Toastr::error(translate('discount_can_not_be_more_total_product_price'));
                return back();
            }
        }
        $tax = isset($cart['tax']) ? $cart['tax'] : 0;
        $total_tax_amount = ($tax > 0) ? (($total_price * $tax) / 100) : $total_tax_amount;
        try {
            $order->extra_discount = $extra_discount ?? 0;
            $order->total_tax_amount = $total_tax_amount;
            $order->order_amount = $total_price + $total_tax_amount + $order->delivery_charge;
            $order->coupon_discount_amount = 0.00;
            $order->branch_id = session()->get('branch_id');
            $order->table_id = session()->get('table_id');
            $order->number_of_people = session()->get('people_number');

            if(session('branch_id')){
                $order->save();

                foreach ($order_details as $key => $item) {
                    $order_details[$key]['order_id'] = $order->id;
                }
                OrderDetail::insert($order_details);

                session()->forget('cart');
                session(['last_order' => $order->id]);
                session()->forget('customer_id');
                session()->forget('branch_id');
                session()->forget('table_id');
                session()->forget('people_number');

                Toastr::success(translate('order_placed_successfully'));

                //send notification to kitchen
                if ($order->order_type == 'dine_in'){
                    $notification = new Notification;
                    $notification->title =  "You have a new order from POS - (Order Confirmed). ";
                    $notification->description = $order->id;
                    $notification->status = 1;

                    try {
                        Helpers::send_push_notif_to_topic($notification, "kitchen-{$order->branch_id}",'general');
                        Toastr::success(translate('Notification sent successfully!'));
                    } catch (\Exception $e) {
                        Toastr::warning(translate('Push notification failed!'));
                    }
                }

                return back();
            }

            else{
                Toastr::warning(translate('Branch select is required'));
            }

        } catch (\Exception $e) {
            info($e);
        }
        Toastr::warning(translate('failed_to_place_order'));
        return back();
    }

    public function generate_invoice($id)
    {
        $order = Order::where('id', $id)->first();

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.order.invoice', compact('order'))->render(),
        ]);
    }

    public function getTableListByBranch(Request $request)
    {
        $data = [
            'tables' => Table::where(['is_active' => 1, 'branch_id' => $request->branch_id])->get(),
        ];
        return response()->json($data);
    }

    public function clear_session_data()
    {
        session()->forget('customer_id');
        session()->forget('branch_id');
        session()->forget('table_id');
        session()->forget('people_number');
        Toastr::success(translate('clear data successfully'));
        return back();
    }

    public function export_excel(Request $request)
    {
        $query = Order::pos()->with(['customer', 'branch']);
        if ($request->search != null) {
            $key = explode(' ', $request['search']);
            $orders = $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('order_status', 'like', "%{$value}%")
                        ->orWhere('transaction_reference', 'like', "%{$value}%");
                    }
                })
            ->get();
        }
        else {
            $branch_id = $request->branch_id != null? $request->branch_id: 'all';
            $to = $request->to;
            $from = $request->from;


            $orders = $query->when($from && $to && $branch_id == 'all', function ($q) use($from, $to){
                $q->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()]);
            })
                ->when($from && $to && $branch_id != 'all', function ($q) use($from, $to, $branch_id) {
                    $q->whereBetween('created_at', [$from, Carbon::parse($to)->endOfDay()])
                        ->whereHas('branch', function ($q) use($branch_id){
                            $q->where('id', $branch_id);
                        });
                })
                ->when($from == null && $to == null && $branch_id != 'all', function ($q) use($from, $to, $branch_id) {
                    $q->whereHas('branch', function ($q) use($branch_id){
                        $q->where('id', $branch_id);
                    });
                })
                ->get();
        }


        if ($orders->count()<1){
            Toastr::warning(translate('No Data Found'));
            return back();
        }

        $data = array();
        foreach ($orders as $key => $order) {
            $data[] = array(
                'SL' => ++$key,
                'Order ID' => $order->id,
                'Order Date' => date('d M Y',strtotime($order['created_at'])). ' ' . date("h:i A",strtotime($order['created_at'])),
                'Customer Info' => $order['user_id'] == null? 'Walk in Customer' : $order->customer['f_name']. ' '. $order->customer['l_name'],
                'Branch' => $order->branch? $order->branch->name : 'Branch Deleted',
                'Total Amount' => Helpers::set_symbol($order['order_amount']),
                'Payment Status' => $order->payment_status=='paid'? 'Paid' : 'Unpaid',
                'Order Status' => $order['order_status']=='pending'? 'Pending' : ($order['order_status']=='confirmed'? 'Confirmed' : ($order['order_status']=='processing' ? 'Processing' : ($order['order_status']=='delivered'? 'Delivered': ($order['order_status']=='picked_up'? 'Out For Delivery' : str_replace('_',' ',$order['order_status']))))),
                'Order Type' => $order['order_type']=='take_away'? 'Take Away': 'Delivery',
            );
        }
        return (new FastExcel($data))->download('Order_Details.xlsx');


    }

    public function customer_store(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'phone' => 'required|unique:users',
            'email' => 'required|email|unique:users',

        ]);
        $user = User::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt('password'),
        ]);
        Toastr::success(translate('customer added successfully'));
        return back();
    }

}
