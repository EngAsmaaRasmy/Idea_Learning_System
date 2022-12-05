<?php

namespace App\Http\Controllers;

use App\Models\SiteProfile;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Traits\SlugTrait;
use App\Traits\TranslationTrait;
use DataTables;
use Validator;

class SiteProfileController extends Controller
{
    use ApiResponser;
    use SlugTrait;
    use TranslationTrait;

    public function show(Request $request)
    {
        $profile = SiteProfile::first();
        $local = $request->header('Accept-Language');
        if ($local && $local == 'ar') {
            $profile->about = $profile->about_ar;
            $profile->address = $profile->address_ar;
        }
        return $this->success($profile);
    }

    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'about' => 'required',
            'mobile' => 'required',
            'email' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            $message = implode("\n", $validator->errors()->all());
            return $this->error($message, 422, $validator->errors());
        }
        $profile = SiteProfile::first();
        if ($profile) {
            $profile->update($input);
            $this->editTranslation($request, 'SiteProfile', $profile->id);
        } else {
            $profile = SiteProfile::create($input);
            $this->translate($request, 'SiteProfile', $profile->id);
        }
        return $this->success($profile, __('main.site_profile_success'));
    }
}
