@isset($data['otp'])
<p> {{__('main.verification_code')}} {{$data['otp']}} </p>
@endisset
