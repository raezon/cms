<div class="mt-5 mb-5">
    <div class="inline-page-menu my-4">
        <ul class="list-unstyled">
            <li class="{{Request::is('admin/business-settings/page-setup/about-us')? 'active': ''}}"><a href="{{route('admin.business-settings.page-setup.about-us')}}">{{translate('about_us')}}</a></li>
            <li class="{{Request::is('admin/business-settings/page-setup/terms-and-conditions')?'active':''}}"><a href="{{route('admin.business-settings.page-setup.terms-and-conditions')}}">{{translate('terms_and_condition')}}</a></li>
            <li class="{{Request::is('admin/business-settings/page-setup/privacy-policy')?'active':''}}"><a href="{{route('admin.business-settings.page-setup.privacy-policy')}}">{{translate('privacy_policy')}}</a></li>
            <li class="{{Request::is('admin/business-settings/page-setup/return-page*')?'active':''}}"><a href="{{route('admin.business-settings.page-setup.return_page_index')}}">{{translate('Return policy')}}</a></li>
            <li class="{{Request::is('admin/business-settings/page-setup/refund-page*')?'active':''}}"><a href="{{route('admin.business-settings.page-setup.refund_page_index')}}">{{translate('Refund policy')}}</a></li>
            <li class="{{Request::is('admin/business-settings/page-setup/cancellation-page*')?'active':''}}"><a href="{{route('admin.business-settings.page-setup.cancellation_page_index')}}">{{translate('Cancellation policy')}}</a></li>
        </ul>
    </div>
</div>

