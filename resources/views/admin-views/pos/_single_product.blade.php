<div class="pos-product-item card" onclick="quickView('{{$product->id}}')">
    <div class="pos-product-item_thumb">
        <img src="{{asset('storage/app/public/product')}}/{{$product['image']}}"
                onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                class="img-fit">
    </div>

    <div class="pos-product-item_content clickable">
        <div class="pos-product-item_title">
            {{ Str::limit($product['name'], 15) }}
        </div>
        <div class="pos-product-item_price">
            {{ \App\CentralLogics\Helpers::set_symbol(($product['price']- \App\CentralLogics\Helpers::discount_calculate($product, $product['price']))) }}
            {{-- @if($product->discount > 0)
                <strike style="font-size: 8px!important;color: grey!important;">
                    {{ \App\CentralLogics\Helpers::set_symbol($product['price']) }}
                </strike><br>
            @endif --}}
        </div>
    </div>
</div>
