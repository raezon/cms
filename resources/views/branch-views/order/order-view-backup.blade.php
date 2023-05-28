@extends('layouts.branch.app')

@section('title', translate('Order Details'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-1">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/order_details.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Order_Details')}}
                </span>
            </h2>
            <span class="badge badge-soft-dark rounded-50 fz-14">{{$order->details->count()}}</span>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-lg-{{$order->customer!=null ? 8 : 12}} mb-3 mb-lg-0">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <div class="border-bottom px-card py-3">
                        <div class="row gy-2">
                            <div class="col-sm-6 d-flex flex-column justify-content-between">
                                <div>
                                    <h2 class="page-header-title h1 mb-3">{{translate('order')}} #{{$order['id']}}</h2>
                                    <h5 class="text-capitalize">
                                        <i class="tio-shop"></i>
                                        {{translate('branch')}} :
                                        <label class="badge-soft-info px-2 rounded">
                                            {{$order->branch?$order->branch->name:'Branch deleted!'}}
                                        </label>
                                    </h5>

                                    <div class="mt-2 d-flex flex-column">
                                        @if($order['order_type']!='take_away' && $order['order_type'] != 'pos' && $order['order_type'] != 'dine_in')
                                            <div class="hs-unfold">
                                                <select class="form-control" name="delivery_man_id"
                                                        onchange="addDeliveryMan(this.value)">
                                                    <option
                                                        value="0">{{translate('Select Delivery Man')}}</option>
                                                    @foreach(\App\Model\DeliveryMan::all() as $deliveryMan)
                                                        <option
                                                            value="{{$deliveryMan['id']}}" {{$order['delivery_man_id']==$deliveryMan['id']?'selected':''}}>
                                                            {{$deliveryMan['f_name'].' '.$deliveryMan['l_name']}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="hs-unfold">
                                                @if($order['order_status']=='out_for_delivery')
                                                    @php($origin=\App\Model\DeliveryHistory::where(['deliveryman_id'=>$order['delivery_man_id'],'order_id'=>$order['id']])->first())
                                                    @php($current=\App\Model\DeliveryHistory::where(['deliveryman_id'=>$order['delivery_man_id'],'order_id'=>$order['id']])->latest()->first())
                                                    @if(isset($origin))
                                                        {{--<a class="btn btn-outline-primary" target="_blank"
                                                        title="Delivery Boy Last Location" data-toggle="tooltip" data-placement="top"
                                                        href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$location['latitude']}}+{{$location['longitude']}}">
                                                            <i class="tio-map"></i>
                                                        </a>--}}
                                                        <a class="btn btn-outline-primary" target="_blank"
                                                        title="{{translate('Delivery Boy Last Location')}}" data-toggle="tooltip" data-placement="top"
                                                        href="https://www.google.com/maps/dir/?api=1&origin={{$origin['latitude']}},{{$origin['longitude']}}&destination={{$current['latitude']}},{{$current['longitude']}}">
                                                            <i class="tio-map"></i>
                                                        </a>
                                                    @else
                                                        <a class="btn btn-outline-primary" href="javascript:" data-toggle="tooltip"
                                                        data-placement="top" title="{{translate('Waiting for location...')}}">
                                                            <i class="tio-map"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    <a class="btn btn-outline-dark" href="javascript:" onclick="last_location_view()"
                                                    data-toggle="tooltip" data-placement="top"
                                                    title="{{translate('Only available when order is out for delivery!')}}">
                                                        <i class="tio-map"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        @if($order['order_type'] == 'dine_in')
                                            <div class="hs-unfold">
                                                <h5 class="text-capitalize">
                                                    <i class="tio-table"></i>
                                                    {{translate('table no')}} : <label
                                                        class="badge badge-secondary">{{$order->table?$order->table->number:'Table deleted!'}}</label>
                                                </h5>
                                            </div>
                                            @if($order['number_of_people'] != null)
                                            <div class="hs-unfold">
                                                <h5 class="text-capitalize">
                                                    <i class="tio-user"></i>
                                                    {{translate('number of people')}} : <label
                                                        class="badge badge-secondary">{{$order->number_of_people}}</label>
                                                </h5>
                                            </div>
                                            @endif
                                        @endif

                                        <div class="d-flex gap-2">
                                            <div class="hs-unfold">
                                                @if($order['order_type'] != 'pos')
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false">
                                                            {{translate('status')}}
                                                        </button>
                                                        <div class="dropdown-menu text-capitalize dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item"
                                                            onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'confirmed'])}}','{{\App\CentralLogics\translate("Change status to confirmed ?")}}')"
                                                            href="javascript:">{{translate('confirmed')}}</a>
                                                            @if($order['order_type'] != 'dine_in')
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'pending'])}}','{{\App\CentralLogics\translate("Change status to pending ?")}}')"
                                                                href="javascript:">{{translate('pending')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'processing'])}}','{{\App\CentralLogics\translate("Change status to processing ?")}}')"
                                                                href="javascript:">{{translate('processing')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'out_for_delivery'])}}','{{\App\CentralLogics\translate("Change status to out for delivery ?")}}')"
                                                                href="javascript:">{{translate('out_for_delivery')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'delivered'])}}','{{\App\CentralLogics\translate("Change status to delivered ?")}}')"
                                                                href="javascript:">{{translate('delivered')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'returned'])}}','{{\App\CentralLogics\translate("Change status to returned ?")}}')"
                                                                href="javascript:">{{translate('returned')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'failed'])}}','{{\App\CentralLogics\translate("Change status to failed ?")}}')"
                                                                href="javascript:">{{translate('failed')}}</a>
                                                            @else
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'completed'])}}','{{\App\CentralLogics\translate("Change status to completed ?")}}')"
                                                                href="javascript:">{{translate('completed')}}</a>
                                                                <a class="dropdown-item"
                                                                onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'cooking'])}}','{{\App\CentralLogics\translate("Change status to cooking ?")}}')"
                                                                href="javascript:">{{translate('cooking')}}</a>
                                                            @endif
                                                            <a class="dropdown-item"
                                                            onclick="route_alert('{{route('admin.orders.status',['id'=>$order['id'],'order_status'=>'canceled'])}}','{{\App\CentralLogics\translate("Change status to canceled ?")}}')"
                                                            href="javascript:">{{translate('canceled')}}</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="hs-unfold">
                                                @if($order['order_type'] != 'pos')
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false">
                                                            {{translate('payment')}}
                                                        </button>
                                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item"
                                                            onclick="route_alert('{{route('admin.orders.payment-status',['id'=>$order['id'],'payment_status'=>'paid'])}}','{{\App\CentralLogics\translate("Change status to paid ?")}}')"
                                                            href="javascript:">{{translate('paid')}}</a>
                                                            <a class="dropdown-item"
                                                            onclick="route_alert('{{route('admin.orders.payment-status',['id'=>$order['id'],'payment_status'=>'unpaid'])}}','{{\App\CentralLogics\translate("Change status to unpaid ?")}}')"
                                                            href="javascript:">{{translate('unpaid')}}</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <i class="tio-date-range"></i> {{date('d M Y',strtotime($order['created_at']))}} {{ date(config('time_format'), strtotime($order['created_at'])) }}
                                    </div>
                                </div>

                                <h5 class="mt-3">{{translate('order')}} {{translate('note')}} : {{$order['order_note']}}</h5>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-sm-right">
                                    <div class="d-flex align-items-center justify-content-sm-end gap-2">
                                        <div class="hs-unfold">
                                            @if($order['order_status']=='out_for_delivery')
                                                @php($origin=\App\Model\DeliveryHistory::where(['deliveryman_id'=>$order['delivery_man_id'],'order_id'=>$order['id']])->first())
                                                @php($current=\App\Model\DeliveryHistory::where(['deliveryman_id'=>$order['delivery_man_id'],'order_id'=>$order['id']])->latest()->first())
                                                @if(isset($origin))
                                                    {{--<a class="btn btn-outline-primary" target="_blank"
                                                    title="Delivery Boy Last Location" data-toggle="tooltip" data-placement="top"
                                                    href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$location['latitude']}}+{{$location['longitude']}}">
                                                        <i class="tio-map"></i>
                                                    </a>--}}
                                                    <a class="btn btn-outline-primary" target="_blank"
                                                    title="{{translate('Delivery Boy Last Location')}}" data-toggle="tooltip" data-placement="top"
                                                    href="https://www.google.com/maps/dir/?api=1&origin={{$origin['latitude']}},{{$origin['longitude']}}&destination={{$current['latitude']}},{{$current['longitude']}}">
                                                        <i class="tio-map"></i>
                                                    </a>
                                                @else
                                                    <a class="btn btn-outline-primary" href="javascript:" data-toggle="tooltip"
                                                    data-placement="top" title="{{translate('Waiting for location...')}}">
                                                        <i class="tio-map"></i>
                                                    </a>
                                                @endif
                                            @else
                                                <a class="btn btn-outline-dark" href="javascript:" onclick="last_location_view()"
                                                data-toggle="tooltip" data-placement="top"
                                                title="{{translate('Only available when order is out for delivery!')}}">
                                                    <i class="tio-map"></i>
                                                </a>
                                            @endif
                                        </div>

                                        <a class="btn btn-info" href={{route('admin.orders.generate-invoice',[$order['id']])}}>
                                            <i class="tio-print"></i> {{translate('Print_Invoice')}}
                                        </a>
                                    </div>

                                    @if($order['order_type']!='take_away' && $order['order_type'] != 'pos' && $order['order_status'] != 'delivered')
                                    <div class="hs-unfold mt-3">
                                        <select class="form-control" name="delivery_man_id" onchange="addDeliveryMan(this.value)">
                                            <option value="0">{{translate('Select Delivery Man')}}</option>
                                            @foreach(\App\Model\DeliveryMan::where(['branch_id'=>auth('branch')->id()])->orWhere(['branch_id'=>0])->get() as $deliveryMan)
                                                <option
                                                    value="{{$deliveryMan['id']}}" {{$order['delivery_man_id']==$deliveryMan['id']?'selected':''}}>
                                                    {{$deliveryMan['f_name'].' '.$deliveryMan['l_name']}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif

                                    <div class="d-flex gap-2 justify-content-sm-end mt-3">
                                        <div class="hs-unfold">
                                            @if($order['order_type'] != 'pos' && $order['order_status'] != 'delivered')
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                                            id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false">
                                                        {{translate('status')}}
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'pending'])}}','{{translate('Change status to pending ?')}}')"
                                                        href="javascript:">{{translate('pending')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'confirmed'])}}','{{translate('Change status to confirmed ?')}}')"
                                                        href="javascript:">{{translate('confirmed')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'processing'])}}','{{translate('Change status to processing ?')}}')"
                                                        href="javascript:">{{translate('processing')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'out_for_delivery'])}}','{{translate('Change status to out for delivery ?')}}')"
                                                        href="javascript:">{{translate('out_for_delivery')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'delivered'])}}','{{translate('Change status to delivered ?')}}')"
                                                        href="javascript:">{{translate('delivered')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'returned'])}}','{{translate('Change status to returned ?')}}')"
                                                        href="javascript:">{{translate('returned')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'failed'])}}','{{translate('Change status to failed ?')}}')"
                                                        href="javascript:">{{translate('failed')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.status',['id'=>$order['id'],'order_status'=>'canceled'])}}','{{translate('Change status to canceled ?')}}')"
                                                        href="javascript:">{{translate('canceled')}}</a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="hs-unfold">
                                            @if($order['order_type'] != 'pos' && $order['order_status'] != 'delivered')
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                                            id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                                                            aria-expanded="false">
                                                        {{translate('payment')}}
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.payment-status',['id'=>$order['id'],'payment_status'=>'paid'])}}','{{translate('Change status to paid ?')}}')"
                                                        href="javascript:">{{translate('paid')}}</a>
                                                        <a class="dropdown-item"
                                                        onclick="route_alert('{{route('branch.orders.payment-status',['id'=>$order['id'],'payment_status'=>'unpaid'])}}','{{translate('Change status to unpaid ?')}}')"
                                                        href="javascript:">{{translate('unpaid')}}</a>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex gap-3 justify-content-sm-end my-3">
                                        <strong class="text-dark">{{translate('Status')}} :</strong>
                                        @if($order['order_status']=='pending')
                                            <span class="badge-soft-info px-2 rounded text-capitalize">{{translate('pending')}}</span>
                                        @elseif($order['order_status']=='confirmed')
                                            <span class="badge-soft-info px-2 rounded text-capitalize">{{translate('confirmed')}}</span>
                                        @elseif($order['order_status']=='processing')
                                            <span class="badge-soft-warning px-2 rounded text-capitalize">{{translate('processing')}}</span>
                                        @elseif($order['order_status']=='out_for_delivery')
                                            <span class="badge-soft-warning px-2 rounded text-capitalize">{{translate('out_for_delivery')}}</span>
                                        @elseif($order['order_status']=='delivered')
                                            <span class="badge-soft-success px-2 rounded text-capitalize">{{translate('delivered')}}</span>
                                        @else
                                            <span class="badge-soft-danger px-2 rounded text-capitalize">{{str_replace('_',' ',$order['order_status'])}}</span>
                                        @endif
                                    </div>


                                    <h5 class="text-capitalize d-flex gap-3 justify-content-sm-end mb-3">
                                        <span>{{translate('payment')}} {{translate('method')}} :</span>
                                        <span>{{str_replace('_',' ',$order['payment_method'])}}</span>
                                    </h5>

                                    <h5 class="d-flex gap-3 justify-content-sm-end align-items-center mb-3">
                                        @if($order['transaction_reference']==null && $order['order_type']!='pos')
                                            {{translate('reference')}} {{translate('code')}} :
                                            <button class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                                    data-target=".bd-example-modal-sm">
                                                {{translate('add')}}
                                            </button>
                                        @elseif($order['order_type']!='pos')
                                            {{translate('reference')}} {{translate('code')}}
                                            : {{$order['transaction_reference']}}
                                        @endif
                                    </h5>

                                    <div class="d-flex gap-3 justify-content-sm-end mb-3">
                                        <strong class="text-dark">{{translate('Payment_Status')}} :</strong>
                                        @if($order['payment_status']=='paid')
                                            <span class="badge-soft-success px-2 rounded text-capitalize">{{translate('paid')}}</span>
                                        @else
                                            <span class="badge-soft-danger px-2 rounded text-capitalize">{{translate('unpaid')}}</span>
                                        @endif
                                    </div>

                                    <h5 class="d-flex gap-3 justify-content-sm-end mb-3 text-capitalize">
                                        {{translate('order')}} {{translate('type')}}
                                        : <label class="badge-soft-info px-2 rounded">
                                            {{str_replace('_',' ',$order['order_type'])}}
                                        </label>
                                    </h5>

                                    <div class="d-flex justify-content-sm-end">
                                        <div class="d-flex flex-column align-items-end gap-3">
                                            @if($order['delivery_date'] > \Carbon\Carbon::now()->format('Y-m-d'))
                                                <span class="badge-soft-success rounded px-2">
                                                <i class="tio-time"></i> {{translate('scheduled')}} : {{date('d-M-Y',strtotime($order['delivery_date']))}} {{ date(config('time_format'), strtotime($order['delivery_time'])) }}
                                                </span>
                                            @else
                                                <span class="badge-soft-success rounded px-2">
                                                <i class="tio-time"></i> {{date('d-M-Y',strtotime($order['delivery_date']))}} {{ date(config('time_format'), strtotime($order['delivery_time'])) }}
                                                </span>
                                            @endif

                                            {{-- counter --}}
                                            @if($order['order_type'] != 'pos' && $order['order_type'] != 'take_away' && ($order['order_status'] != DELIVERED && $order['order_status'] != RETURNED && $order['order_status'] != CANCELED && $order['order_status'] != FAILED && $order['order_status'] != COMPLETED))
                                                <span class="ml-2 ml-sm-3 ">
                                                    <i class="tio-timer d-none" id="timer-icon"></i>
                                                    <span id="counter" class="text-info"></span>
                                                    <i class="tio-edit p-2 d-none" id="edit-icon" style="cursor: pointer;" data-toggle="modal" data-target="#counter-change" data-whatever="@mdo"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </div>


                                    <span class="ml-2 ml-sm-3 ">
                                            <i class="tio-timer d-none" id="timer-icon"></i>
                                            <span id="counter" class="text-info"></span>
                                            <i class="tio-edit p-2 d-none" id="edit-icon" style="cursor: pointer;" data-toggle="modal" data-target="#counter-change" data-whatever="@mdo"></i>
                                        </span>

                                    @if($order['order_type'] != 'pos' && $order['order_type'] != 'take_away' && ($order['order_status'] != DELIVERED && $order['order_status'] != RETURNED && $order['order_status'] != CANCELED && $order['order_status'] != FAILED && $order['order_status'] != COMPLETED))
                                        <span class="ml-2 ml-sm-3 ">
                                            <i class="tio-timer d-none" id="timer-icon"></i>
                                            <span id="counter" class="text-info"></span>
                                            <i class="tio-edit p-2 d-none" id="edit-icon" style="cursor: pointer;" data-toggle="modal" data-target="#counter-change" data-whatever="@mdo"></i>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>SL</th>
                                    <th>Item Details</th>
                                    <th>Tax</th>
                                    <th>Discount</th>
                                    <th class="text-right">Price</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <div class="media gap-3">
                                            <img class="img-fluid avatar avatar-lg"
                                                 src="{{asset('public/assets/admin/img/icons/order_details.png')}}"
                                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                                 alt="Image Description">

                                            <div class="media-body text-dark fz-12">
                                                <h6 class="text-capitalize">Pomegranate</h6>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Unit :  </span>
                                                    <span>1Kg</span>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Price :  </span>
                                                    <span>$350</span>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Qty :  </span>
                                                    <span>1</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        $700
                                    </td>
                                    <td>
                                        $700
                                    </td>
                                    <td class="text-right">
                                        $700
                                    </td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <div class="media gap-3">
                                            <img class="img-fluid avatar avatar-lg"
                                                 src="{{asset('public/assets/admin/img/icons/order_details.png')}}"
                                                 onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                                 alt="Image Description">

                                            <div class="media-body text-dark fz-12">
                                                <h6 class="text-capitalize">Pomegranate</h6>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Unit :  </span>
                                                    <span>1Kg</span>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Price :  </span>
                                                    <span>$350</span>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <span class="font-weight-bold">Qty :  </span>
                                                    <span>1</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        $700
                                    </td>
                                    <td>
                                        $700
                                    </td>
                                    <td class="text-right">
                                        $700
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body pt-0">
                        @php($sub_total=0)
                        @php($total_tax=0)
                        @php($total_dis_on_pro=0)
                        @php($add_ons_cost=0)
                        @foreach($order->details as $detail)
                        @if($detail->product)
                            @php($add_on_qtys=json_decode($detail['add_on_qtys'],true))
                            <!-- <div class="media">
                                <div class="avatar avatar-xl mr-3">
                                    <img class="img-fluid"
                                            onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                            src="{{asset('storage/app/public/product')}}/{{$detail->product['image']}}"
                                            alt="Image Description">
                                </div>

                                <div class="media-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <strong> {{$detail->product['name']}}</strong>

                                            @if(count(json_decode($detail['variation'],true))>0)
                                                <strong><u>{{translate('variation')}} : </u></strong>
                                                @foreach(json_decode($detail['variation'],true)[0] as $key1 =>$variation)
                                                    <div class="font-size-sm text-body">
                                                        <span>{{$key1}} :  </span>
                                                        <span class="font-weight-bold">{{ $key1 == 'price' ?  Helpers::set_symbol($variation) : $variation }}</span>
                                                    </div>
                                                @endforeach
                                            @endif

                                            @foreach(json_decode($detail['add_on_ids'],true) as $key2 =>$id)
                                                @php($addon=\App\Model\AddOn::find($id))
                                                @if($key2==0)
                                                    @if(isset($addon))<strong><u>{{translate('addons')}} : </u></strong>@endif
                                                @endif

                                                @if($add_on_qtys==null)
                                                    @php($add_on_qty=1)
                                                @else
                                                    @php($add_on_qty=$add_on_qtys[$key2])
                                                @endif

                                                @if(isset($addon))
                                                    <div class="font-size-sm text-body">
                                                        <span>{{$addon['name']}} :  </span>
                                                        <span class="font-weight-bold">
                                                            {{$add_on_qty}} x {{ \App\CentralLogics\Helpers::set_symbol($addon['price']) }}
                                                        </span>
                                                    </div>
                                                    @php($add_ons_cost+=$addon['price']*$add_on_qty)
                                                @endif
                                            @endforeach
                                        </div>


                                        <div class="col col-md-2 align-self-center">
                                            @if($detail['discount_on_product']!=0)
                                                <h5>
                                                    <strike>
                                                        {{--{{\App\CentralLogics\Helpers::variation_price(json_decode($detail['product_details'],true),$detail['variation']) ." ".\App\CentralLogics\Helpers::currency_symbol()}}--}}
                                                    </strike>
                                                </h5>
                                            @endif
                                            <h6>{{ \App\CentralLogics\Helpers::set_symbol($detail['price']-$detail['discount_on_product']) }}</h6>
                                        </div>
                                        <div class="col col-md-1 align-self-center">
                                            <h5>{{$detail['quantity']}}</h5>
                                        </div>

                                        <div class="col col-md-3 align-self-center text-right">
                                            @php($amount=($detail['price']-$detail['discount_on_product'])*$detail['quantity'])
                                            <h5>{{ \App\CentralLogics\Helpers::set_symbol($amount) }}</h5>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                            @php($sub_total+=$amount)
                            @php($total_tax+=$detail['tax_amount']*$detail['quantity'])
                            @endif
                        @endforeach

                        <hr>

                        <div class="row justify-content-md-end mb-3">
                            <div class="col-md-9 col-lg-8">
                                <dl class="row dt-400 text-right">
                                    <dt class="col-6">{{translate('items')}} {{translate('price')}}:</dt>
                                    <dd class="col-6 text-dark">{{ \App\CentralLogics\Helpers::set_symbol($sub_total) }}</dd>
                                    <dt class="col-6">{{translate('tax')}} / {{translate('vat')}}:</dt>
                                    <dd class="col-6 text-dark">{{ \App\CentralLogics\Helpers::set_symbol($total_tax) }}</dd>
                                    <dt class="col-6">{{translate('addon')}} {{translate('cost')}}:</dt>
                                    <dd class="col-6 text-dark">
                                        {{ \App\CentralLogics\Helpers::set_symbol($add_ons_cost) }}
                                    </dd>

                                    <dt class="col-6">{{translate('subtotal')}}:</dt>
                                    <dd class="col-6 text-dark">
                                        {{ \App\CentralLogics\Helpers::set_symbol($sub_total+$total_tax+$add_ons_cost) }}</dd>
                                    <dt class="col-6">{{translate('coupon')}} {{translate('discount')}}:</dt>
                                    <dd class="col-6 text-dark">
                                        - {{ \App\CentralLogics\Helpers::set_symbol($order['coupon_discount_amount']) }}</dd>
                                    <dt class="col-6">{{translate('extra discount')}} :</dt>
                                    <dd class="col-6 text-dark">
                                        - {{ \App\CentralLogics\Helpers::set_symbol($order['extra_discount']) }}</dd>
                                    <dt class="col-6">{{translate('delivery')}} {{translate('fee')}}:</dt>
                                    <dd class="col-6 text-dark">
                                        @if($order['order_type']=='take_away')
                                            @php($del_c=0)
                                        @else
                                            @php($del_c=$order['delivery_charge'])
                                        @endif
                                        {{ \App\CentralLogics\Helpers::set_symbol($del_c) }}
                                    </dd>

                                    <dt class="col-6 border-top pt-2 fz-16 font-weight-bold">{{translate('total')}}:</dt>
                                    <dd class="col-6 border-top pt-2 fz-16 font-weight-bold text-dark">{{ \App\CentralLogics\Helpers::set_symbol($sub_total+$del_c+$total_tax+$add_ons_cost-$order['coupon_discount_amount']-$order['extra_discount']) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->customer)
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h4 class="mb-4 d-flex gap-2">
                                <i class="tio-user text-dark"></i>
                                Customer information
                            </h4>
                            <div class="media flex-wrap gap-3">
                                <div class="">
                                    <img class="avatar avatar-lg rounded-circle" src="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" onerror="this.src="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" alt="Image">
                                </div>
                                <div class="media-body d-flex flex-column gap-1">
                                    <span class="text-dark"><strong>{{$order->customer['f_name'].' '.$order->customer['l_name']}}</strong></span>
                                    <span class="text-dark"> <strong>{{\App\Model\Order::where('user_id',$order['user_id'])->count()}} </strong> {{translate('orders')}}</span>
                                    <span class="text-dark break-all"><strong>{{$order->customer['phone']}}</strong></span>
                                    <span class="text-dark break-all">{{$order->customer['email']}}</span>
                                    <span>
                                    @if($order['order_type']!='take_away')
                                        @php($address=\App\Model\CustomerAddress::find($order['delivery_address_id']))
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5>{{translate('Delivery address')}}</h5>
                                            @if(isset($address))
                                                <a class="link" data-toggle="modal" data-target="#shipping-address-modal"
                                                href="javascript:">{{translate('Edit')}}</a>
                                            @endif
                                        </div>
                                        @if(isset($address))
                                        <span class="d-block">
                                            {{$address['contact_person_name']}}<br>
                                            {{$address['contact_person_number']}}<br>
                                            {{$address['address_type']}}<br>
                                            <a target="_blank"
                                            href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$address['latitude']}}+{{$address['longitude']}}">
                                            <i class="tio-map"></i> {{$address['address']}}<br>
                                            </a>
                                        </span>
                                        @endif
                                    @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card -->
                    <!-- <div class="card">
                        <div class="card-header">
                            <h4 class="card-header-title">{{translate('customer')}}</h4>
                        </div>

                        <div class="card-body">
                            <div class="media align-items-center" href="javascript:">
                                <div class="avatar avatar-circle mr-3">
                                    <img
                                        class="avatar-img" style="width: 75px"
                                        onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                        src="{{asset('storage/app/public/profile/'.$order->customer->image)}}"
                                        alt="Image Description">
                                </div>
                                <div class="media-body">
                                <span
                                    class="text-body text-hover-primary">{{$order->customer['f_name'].' '.$order->customer['l_name']}}</span>
                                </div>
                                <div class="media-body text-right">
                                    {{--<i class="tio-chevron-right text-body"></i>--}}
                                </div>
                            </div>

                            <hr>

                            <div class="media align-items-center" href="javascript:">
                                <div class="icon icon-soft-info icon-circle mr-3">
                                    <i class="tio-shopping-basket-outlined"></i>
                                </div>
                                <div class="media-body">
                                    <span
                                        class="text-body text-hover-primary">{{\App\Model\Order::where('user_id',$order['user_id'])->count()}} {{translate('orders')}}</span>
                                </div>
                                <div class="media-body text-right">
                                    {{--<i class="tio-chevron-right text-body"></i>--}}
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <h5>{{translate('contact')}} {{translate('info')}}</h5>
                            </div>

                            <ul class="list-unstyled list-unstyled-py-2">
                                <li>
                                    <i class="tio-online mr-2"></i>
                                    {{$order->customer['email']}}
                                </li>
                                <li>
                                    <i class="tio-android-phone-vs mr-2"></i>
                                    {{$order->customer['phone']}}
                                </li>
                            </ul>

                            @if($order['order_type']!='take_away')
                                @php($address=\App\Model\CustomerAddress::find($order['delivery_address_id']))
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>{{translate('Delivery address')}}</h5>
                                    @if(isset($address))
                                        <a class="link" data-toggle="modal" data-target="#shipping-address-modal"
                                           href="javascript:">{{translate('Edit')}}</a>
                                    @endif
                                </div>
                                @if(isset($address))
                                <span class="d-block">
                                    {{$address['contact_person_name']}}<br>
                                    {{$address['contact_person_number']}}<br>
                                    {{$address['address_type']}}<br>
                                    <a target="_blank"
                                       href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$address['latitude']}}+{{$address['longitude']}}">
                                       <i class="tio-map"></i> {{$address['address']}}<br>
                                    </a>
                                </span>
                                @endif
                            @endif
                        </div>
                    </div> -->
                </div>
            @endif
        </div>
        <!-- End Row -->
    </div>

    <!-- Modal -->
    <div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{translate('reference')}} {{translate('code')}} {{translate('add')}}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                            aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{route('branch.orders.add-payment-ref-code',[$order['id']])}}" method="post">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="form-group">
                            <input type="text" name="transaction_reference" class="form-control"
                                   placeholder="{{translate('EX : Code123')}}" required>
                        </div>
                        <!-- End Input Group -->
                        <button class="btn btn-primary">{{translate('submit')}}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!-- Modal -->
    <div id="shipping-address-modal" class="modal fade" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalTopCoverTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-top-cover bg-dark text-center">
                    <figure class="position-absolute right-0 bottom-0 left-0" style="margin-bottom: -1px;">
                        <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                             viewBox="0 0 1920 100.1">
                            <path fill="#fff" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z"/>
                        </svg>
                    </figure>

                    <div class="modal-close">
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-light" data-dismiss="modal"
                                aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor"
                                      d="M11.5,9.5l5-5c0.2-0.2,0.2-0.6-0.1-0.9l-1-1c-0.3-0.3-0.7-0.3-0.9-0.1l-5,5l-5-5C4.3,2.3,3.9,2.4,3.6,2.6l-1,1 C2.4,3.9,2.3,4.3,2.5,4.5l5,5l-5,5c-0.2,0.2-0.2,0.6,0.1,0.9l1,1c0.3,0.3,0.7,0.3,0.9,0.1l5-5l5,5c0.2,0.2,0.6,0.2,0.9-0.1l1-1 c0.3-0.3,0.3-0.7,0.1-0.9L11.5,9.5z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- End Header -->

                <div class="modal-top-cover-icon">
                    <span class="icon icon-lg icon-light icon-circle icon-centered shadow-soft">
                      <i class="tio-location-search"></i>
                    </span>
                </div>

                @php($address=\App\Model\CustomerAddress::find($order['delivery_address_id']))
                @if(isset($address))
                    <form action="{{route('branch.order.update-shipping',[$order['delivery_address_id']])}}"
                          method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('type')}}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address_type"
                                           value="{{$address['address_type']}}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('contact')}}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_number"
                                           value="{{$address['contact_person_number']}}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('name')}}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_name"
                                           value="{{$address['contact_person_name']}}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('address')}}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address"
                                           value="{{$address['address']}}"
                                           required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('latitude')}}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="latitude"
                                           value="{{$address['latitude']}}"
                                           required>
                                </div>
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{translate('longitude')}}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="longitude"
                                           value="{{$address['longitude']}}" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">{{translate('close')}}</button>
                            <button type="submit" class="btn btn-primary">{{translate('save')}} {{translate('changes')}}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <!-- End Modal -->

    {{-- Invoice Modal --}}
    <div class="modal fade" id="print-invoice" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('print')}} {{translate('invoice')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body row" style="font-family: emoji;">
                    <div class="col-md-12">
                        <center>
                            <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                                   value="{{translate('Proceed, If thermal printer is ready.')}}"/>
                            <a href="{{url()->previous()}}" class="btn btn-danger non-printable">{{translate('Back')}}</a>
                        </center>
                        <hr class="non-printable">
                    </div>
                    <div class="row" id="printableArea" style="margin: auto;">

                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($order['order_type'] != 'pos' && $order['order_type'] != 'take_away' && ($order['order_status'] != DELIVERED && $order['order_status'] != RETURNED && $order['order_status'] != CANCELED && $order['order_status'] != FAILED))
        <div class="modal fade" id="counter-change" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel" style="font-size: 20px">{{ translate('Need time to prepare the food') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{route('branch.orders.increase-preparation-time', ['id' => $order->id])}}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group text-center">
                                <input type="number" min="0" name="extra_minute" id="extra_minute" class="form-control" placeholder="{{translate('EX : 20')}}" required>
                            </div>

                            <div class="form-group flex-between">
                                <div class="badge text-info shadow" onclick="predefined_time_input(10)" style="cursor: pointer">{{ translate('10min') }}</div>
                                <div class="badge text-info shadow" onclick="predefined_time_input(20)" style="cursor: pointer">{{ translate('20min') }}</div>
                                <div class="badge text-info shadow" onclick="predefined_time_input(30)" style="cursor: pointer">{{ translate('30min') }}</div>
                                <div class="badge text-info shadow" onclick="predefined_time_input(40)" style="cursor: pointer">{{ translate('40min') }}</div>
                                <div class="badge text-info shadow" onclick="predefined_time_input(50)" style="cursor: pointer">{{ translate('50min') }}</div>
                                <div class="badge text-info shadow" onclick="predefined_time_input(60)" style="cursor: pointer">{{ translate('60min') }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('Submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <!-- End Modal -->
@endsection

@push('script_2')
    <script>
        function addDeliveryMan(id) {
            $.ajax({
                type: "GET",
                url: '{{url('/')}}/branch/orders/add-delivery-man/{{$order['id']}}/' + id,
                data: $('#product_form').serialize(),
                success: function (data) {
                    toastr.success('{{ translate('Successfully added') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                error: function () {
                    toastr.error('Add valid data', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        }

        function last_location_view() {
            toastr.warning('{{ translate('Only available when order is out for delivery!') }}', {
                CloseButton: true,
                ProgressBar: true
            });
        }
    </script>

    <script>
        function print_invoice(order_id) {
            $.get({
                url: '{{url('/')}}/branch/pos/invoice/'+order_id,
                dataType: 'json',
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    console.log("success...")
                    $('#print-invoice').modal('show');
                    $('#printableArea').empty().html(data.view);
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        }

        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            location.reload();
        }
    </script>

    <script>
        function predefined_time_input(min) {
            document.getElementById("extra_minute").value = min;
        }
    </script>

    @if($order['order_type'] != 'pos' && $order['order_type'] != 'take_away' && ($order['order_status'] != DELIVERED && $order['order_status'] != RETURNED && $order['order_status'] != CANCELED && $order['order_status'] != FAILED))
        <script>
            const expire_time = "{{ $order['remaining_time'] }}";
            var countDownDate = new Date(expire_time).getTime();
            const time_zone = "{{ \App\CentralLogics\Helpers::get_business_settings('time_zone') ?? 'UTC' }}";

            var x = setInterval(function() {
                var now = new Date(new Date().toLocaleString("en-US", {timeZone: time_zone})).getTime();

                var distance = countDownDate - now;

                var days = Math.trunc(distance / (1000 * 60 * 60 * 24));
                var hours = Math.trunc((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.trunc((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.trunc((distance % (1000 * 60)) / 1000);


                document.getElementById("timer-icon").classList.remove("d-none");
                document.getElementById("edit-icon").classList.remove("d-none");
                $text = (distance < 0) ? "{{ translate('over') }}" : "{{ translate('left') }}";
                document.getElementById("counter").innerHTML = Math.abs(days) + "d " + Math.abs(hours) + "h " + Math.abs(minutes) + "m " + Math.abs(seconds) + "s " + $text;
                if (distance < 0) {
                    var element = document.getElementById('counter');
                    element.classList.add('text-danger');
                }
            }, 1000);
        </script>
    @endif
@endpush

