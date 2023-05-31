@extends('layouts.admin.app')
@section('title', translate('Add new product'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{asset('/assets/admin/css/tags-input.min.css')}}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('/assets/admin/img/icons/product.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Add_New_Product')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="row g-3">
            <div class="col-12">
                <form action="javascript:" method="post" id="product_form" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="card card-body h-100">
                                @php($data = Helpers::get_business_settings('language'))
                                @php($default_lang = Helpers::get_default_language())

                                @if($data && array_key_exists('code', $data[0]))
                                    <ul class="nav nav-tabs mb-4">

                                        @foreach($data as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link {{$lang['default'] == true ? 'active':''}}" href="#" id="{{$lang['code']}}-link">{{Helpers::get_language_name($lang['code']).'('.strtoupper($lang['code']).')'}}</a>
                                            </li>
                                        @endforeach

                                    </ul>
                                    @foreach($data as $lang)
                                        <div class="{{$lang['default'] == false ? 'd-none':''}} lang_form" id="{{$lang['code']}}-form">
                                            <div class="form-group">
                                                <label class="input-label" for="{{$lang['code']}}_name">{{translate('name')}} ({{strtoupper($lang['code'])}})</label>
                                                <input type="text" name="name[]" id="{{$lang['code']}}_name" class="form-control"
                                                    placeholder="{{translate('New Product')}}" {{$lang['status'] == true ? 'required':''}}
                                                    @if($lang['status'] == true) oninvalid="document.getElementById('{{$lang['code']}}-link').click()" @endif>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="{{$lang['code']}}_description">{{translate('short')}} {{translate('description')}}  ({{strtoupper($lang['code'])}})</label>
                                                {{--<div id="{{$lang}}_editor"></div>--}}
                                                <textarea name="description[]" class="form-control textarea-h-100" id="{{$lang['code']}}_hiddenArea"></textarea>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="" id="{{$default_lang}}-form">
                                        <div class="form-group">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('name')}} (EN)</label>
                                            <input type="text" name="name[]" class="form-control" placeholder="{{translate('New Product')}}" required>
                                        </div>
                                        <input type="hidden" name="lang[]" value="en">
                                        <div class="form-group">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{translate('short')}} {{translate('description')}} (EN)</label>
                                            {{--<div id="editor" style="min-height: 15rem;"></div>--}}
                                            {{--<textarea name="description[]" style="display:none" id="hiddenArea"></textarea>--}}
                                            <textarea name="description[]" class="form-control textarea-h-100" id="hiddenArea"></textarea>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-body h-100">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">{{translate('product_Image')}}</label>
                                    <small class="text-danger">* ( {{translate('ratio')}} 1:1 )</small>
                                    <!-- <div class="custom-file">
                                        <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required
                                            oninvalid="document.getElementById('en-link').click()">
                                        <label class="custom-file-label" for="customFileEg1">{{translate('choose')}} {{translate('file')}}</label>
                                    </div> -->


                                    <div class="d-flex justify-content-center mt-4">
                                        <div class="upload-file">
                                            <input type="file" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input">
                                            <div class="upload-file__img_drag upload-file__img">
                                                <img width="176" src="{{asset('/assets/admin/img/icons/upload_img.png')}}" alt="">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- <center style="display: none" id="image-viewer-section" class="pt-2">
                                        <img style="height: 200px;border: 1px solid; border-radius: 10px;" id="viewer"
                                            src="{{asset('/assets/admin/img/400x400/img2.jpg')}}" alt="banner image"/>
                                    </center> -->
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-category"></i>
                                                {{translate('Category')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlSelect1">
                                                            {{translate('category')}}
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <select name="category_id" class="form-control js-select2-custom"
                                                                onchange="getRequest('{{url('/')}}/admin/product/get-categories?parent_id='+this.value,'sub-categories')">
                                                            <option selected disabled>---{{translate('select')}}---</option>
                                                            @foreach($categories as $category)
                                                                <option value="{{$category['id']}}">{{$category['name']}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlSelect1">{{translate('sub_category')}}<span
                                                                class="input-label-secondary"></span></label>
                                                        <select name="sub_category_id" id="sub-categories"
                                                                class="form-control js-select2-custom"
                                                                onchange="getRequest('{{url('/')}}/admin/product/get-categories?parent_id='+this.value,'sub-sub-categories')">
                                                            <option selected disabled>---{{translate('select')}}---</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label" for="exampleFormControlInput1">{{translate('item_Type')}}</label>
                                                        <select name="item_type" class="form-control js-select2-custom">
                                                            <option selected disabled>---{{translate('select')}}---</option>
                                                            <option value="0">{{translate('product')}} {{translate('item')}}</option>
                                                          <!-- <option value="1">{{translate('set_menu')}}</option> -->
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">
                                                            {{translate('product_Type')}}
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <select name="product_type" class="form-control js-select2-custom" required>
                                                            <option value="veg">{{translate('veg')}}</option>
                                                            <option value="non_veg">{{translate('nonveg')}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-dollar"></i>
                                                {{translate('Price_Information')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('default_Price')}}</label>
                                                        <input type="number" min="0" step="any" value="1" name="price" class="form-control"
                                                            placeholder="{{translate('Ex : 100')}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('discount_Type')}}</label>
                                                        <select name="discount_type" class="form-control js-select2-custom" id="discount_type">
                                                            <option selected disabled>---{{translate('select')}}---</option>
                                                            <option value="percent">{{translate('percentage')}}</option>
                                                            <option value="amount">{{translate('amount')}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label id="discount_label" class="input-label">{{translate('discount_(%)')}}</label>
                                                        <input id="discount_input" type="number" min="0" name="discount" class="form-control"
                                                            placeholder="{{translate('Ex : 5%')}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('tax_Type')}}</label>
                                                        <select name="tax_type" class="form-control js-select2-custom" id="tax_type">
                                                            <option selected disabled>---{{translate('select')}}---</option>
                                                            <option value="percent">{{translate('percentage')}}</option>
                                                            <option value="amount">{{translate('amount')}}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label id="tax_label" class="input-label" for="exampleFormControlInput1">{{translate('tax_Rate($)')}}</label>
                                                        <input id="tax_input" type="number" min="0" step="any" name="tax" class="form-control"
                                                            placeholder="{{translate('Ex : $100')}}" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between gap-3">
                                                <div class="text-dark">{{ translate('turning visibility off will not show this product in the user app and website') }}</div>
                                                <div class="d-flex gap-3 align-items-center">
                                                    <h5>{{translate('Visibility')}}</h5>
                                                    <label class="switcher">
                                                        <input class="switcher_input" type="checkbox" checked="checked" name="status">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-watches"></i>
                                                {{translate('Availability')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('available_From')}}</label>
                                                        <input type="time" name="available_time_starts" class="form-control" value="10:30:00"
                                                            placeholder="{{translate('Ex : 10:30 am')}}" required>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <label class="input-label">{{translate('available_Till')}}</label>
                                                        <input type="time" name="available_time_ends" class="form-control" value="19:30:00" placeholder="{{translate('5:45 pm')}}"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                <i class="tio-puzzle"></i>
                                                {{translate('Addons_&_Attributes')}}
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('Select_Addons')}}</label>
                                                <select name="addon_ids[]" class="form-control" id="choose_addons" multiple="multiple">
                                                    @foreach(\App\Model\AddOn::orderBy('name')->get() as $addon)
                                                        <option value="{{$addon['id']}}">{{$addon['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="input-label">{{translate('Select_Attributes')}}<span
                                                        class="input-label-secondary"></span></label>
                                                <select name="attribute_id[]" id="choice_attributes"
                                                        class="form-control"
                                                        multiple="multiple">
                                                    @foreach(\App\Model\Attribute::orderBy('name')->get() as $attribute)
                                                        <option value="{{$attribute['id']}}">{{$attribute['name']}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" id="from_part_2">
                        <div class="card card-body">
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <div class="customer_choice_options" id="customer_choice_options"></div>
                                </div>
                                <div class="col-md-12">
                                    <div class="variant_combination" id="variant_combination"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                        <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script')

@endpush

@push('script_2')
    <script src="{{asset('/assets/admin/js/spartan-multi-image-picker.js')}}"></script>

    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
            $('#image-viewer-section').show(1000)
        });
    </script>

    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == '{{$default_lang}}')
            {
                $("#from_part_2").removeClass('d-none');
            }
            else
            {
                $("#from_part_2").addClass('d-none');
            }


        })
    </script>

    <script>
        //Select 2
        $("#choose_addons").select2({
            placeholder: "Select Addons",
            allowClear: true
        });
        $("#choice_attributes").select2({
            placeholder: "Select Attributes",
            allowClear: true
        });
    </script>

    <script>


        $('#product_form').on('submit', function () {
            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.product.store')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('{{translate("product uploaded successfully!")}}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.href = '{{route('admin.product.list')}}';
                        }, 2000);
                    }
                }
            });
        });
    </script>

    <script>
        function getRequest(route, id) {
            $.get({
                url: route,
                dataType: 'json',
                success: function (data) {
                    $('#' + id).empty().append(data.options);
                },
            });
        }
    </script>

    <script>
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script src="{{asset('/assets/admin')}}/js/tags-input.min.js"></script>

    <script>
        $('#choice_attributes').on('change', function () {
            $('#customer_choice_options').html(null);
            $.each($("#choice_attributes option:selected"), function () {
                add_more_customer_choice_option($(this).val(), $(this).text());
            });
        });

        function add_more_customer_choice_option(i, name) {
            let n = name.split(' ').join(' ');
            $('#customer_choice_options').append('<div class="row"><div class="col-md-3"><input type="hidden" name="choice_no[]" value="' + i + '"><input type="text" class="form-control" name="choice[]" value="' + n + '" placeholder="Choice Title" readonly></div><div class="col-lg-9"><input type="text" class="form-control" name="choice_options_' + i + '[]" placeholder="Enter choice values" data-role="tagsinput" onchange="combination_update()"></div></div>');
            $("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput();
        }

        function combination_update() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "POST",
                url: '{{route('admin.product.variant-combination')}}',
                data: $('#product_form').serialize(),
                success: function (data) {
                    $('#variant_combination').html(data.view);
                    if (data.length > 1) {
                        $('#quantity').hide();
                    } else {
                        $('#quantity').show();
                    }
                }
            });
        }
    </script>

    <script>
        function update_qty() {
            var total_qty = 0;
            var qty_elements = $('input[name^="stock_"]');
            for(var i=0; i<qty_elements.length; i++)
            {
                total_qty += parseInt(qty_elements.eq(i).val());
            }
            if(qty_elements.length > 0)
            {
                $('input[name="total_stock"]').attr("readonly", true);
                $('input[name="total_stock"]').val(total_qty);
                console.log(total_qty)
            }
            else{
                $('input[name="total_stock"]').attr("readonly", false);
            }
        }
    </script>

    <script>
        $("#discount_type").change(function(){
            if(this.value === 'amount') {
                $("#discount_label").text("{{translate('discount_amount')}}");
                $("#discount_input").attr("placeholder", "{{translate('Ex: 500')}}")
            }
            else if(this.value === 'percent') {
                $("#discount_label").text("{{translate('discount_percent')}}")
                $("#discount_input").attr("placeholder", "{{translate('Ex: 50%')}}")
            }
        });

        $("#tax_type").change(function(){
            if(this.value === 'amount') {
                $("#tax_label").text("{{translate('tax_amount')}}");
                $("#tax_input").attr("placeholder", "{{translate('Ex: 500')}}")
            }
            else if(this.value === 'percent') {
                $("#tax_label").text("{{translate('tax_percent')}}")
                $("#tax_input").attr("placeholder", "{{translate('Ex: 50%')}}")
            }
        });

    </script>
@endpush




