<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Translation;

trait TranslationTrait
{
    public function translate(Request $request, $model_name, $row_id)
    {
        $input = $request->all();
        $translatable = $this->getTranslatable($input);

        foreach ($input as $key => $value) {
            $lang_id = (substr($key, -3) == '_ar') ? 2 : 1;
            $key = (substr($key, -3) == '_ar') ?  substr($key, 0, -3) : $key;

            if (in_array($key, $translatable) && $lang_id == 2) {
                Translation::create([
                    'model' => $model_name,
                    'field' => $key,
                    'row_id' => $row_id,
                    'lang_id' => $lang_id,
                    'value' => $value
                ]);
            }
        }
    }
    public function translateArray($input, $model_name, $row_id)
    {
        $translatable = $this->getTranslatable($input);

        foreach ($input as $key => $value) {
            $lang_id = (substr($key, -3) == '_ar') ? 2 : 1;
            $key = (substr($key, -3) == '_ar') ?  substr($key, 0, -3) : $key;

            if (in_array($key, $translatable) && $lang_id == 2) {
                Translation::create([
                    'model' => $model_name,
                    'field' => $key,
                    'row_id' => $row_id,
                    'lang_id' => $lang_id,
                    'value' => $value
                ]);
            }
        }
    }
    public function editTranslation(Request $request, $model_name, $row_id)
    {
        $input = $request->all();
        $translatable = $this->getTranslatable($input);

        foreach ($input as $key => $value) {
            $lang_id = (substr($key, -3) == '_ar') ? 2 : 1 ;
            $key = (substr($key, -3) == '_ar') ? substr($key, 0, -3) : $key ;
            if (in_array($key, $translatable) && $lang_id == 2) {
                $translation = Translation::
                where('model', $model_name)
                ->where('row_id', $row_id)
                ->where('field', $key)
                ->first();

                if (isset($translation)) {
                    $translation->value = $value;
                    $translation->save();
                } else {
                    Translation::create([
                        'model' => $model_name,
                        'field' => $key,
                        'row_id' => $row_id,
                        'lang_id' => $lang_id,
                        'value' => $value
                    ]);
                }
            }
        }
    }
    public function editTranslationArray($input, $model_name, $row_id)
    {
        $translatable = $this->getTranslatable($input);

        foreach ($input as $key => $value) {
            $lang_id = (substr($key, -3) == '_ar') ? 2 : 1 ;
            $key = (substr($key, -3) == '_ar') ? substr($key, 0, -3) : $key ;


            if (in_array($key, $translatable) && $lang_id == 2) {
                $translation = Translation::
                where('model', $model_name)
                ->where('row_id', $row_id)
                ->where('field', $key)
                ->first();

                if (isset($translation)) {
                    $translation->value = $value;
                    $translation->save();
                } else {
                    Translation::create([
                        'model' => $model_name,
                        'field' => $key,
                        'row_id' => $row_id,
                        'lang_id' => $lang_id,
                        'value' => $value
                    ]);
                }
            }
        }
    }

    public function getRow($model_name, $row_id, $obj)
    {
        $translations = Translation::where('model', $model_name)
        ->where('row_id', $row_id)
        ->get();

        foreach ($translations as $key => $translation) {
            $obj[$translation->field . '_ar'] = $translation->value;
        }

        return $obj;
    }

    public function getTranslatedRow($model_name, $row_id, $obj)
    {
        $translations = Translation::where('model', $model_name)
        ->where('row_id', $row_id)
        ->get();

        foreach ($translations as $key => $translation) {
            $obj[$translation->field] = $translation->value;
        }

        return $obj;
    }

    public function getTranslatable($input)
    {
        $tanslatable = [];
        foreach ($input as $key => $value) {
            if (substr($key, -3) == '_ar') {
                array_push($tanslatable, $key);
                array_push($tanslatable, substr($key, 0, -3));
            }
        }
        return $tanslatable;
    }

    public function translateCollection($lang, $data, $model)
    {
        if (count($data) > 0 && $lang == 'ar') {
            $translated_data = [];
            foreach ($data as $key => $obj) {
                $translation = $this->getTranslatedRow($model, $obj->id, $obj);
                $translated_data[] = $translation;
            }
            $data = $translated_data;
        }
        return $data;
    }
}
