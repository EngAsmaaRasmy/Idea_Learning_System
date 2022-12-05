<?php

namespace App\Traits;

use App\Models\Slug;
use DB;
use Illuminate\Support\Str;

trait SlugTrait
{
    public function slug($name, $table)
    {
        $slug = Str::slug($name);
        $slug .= rand(1, 1000);
        $latestSlug = Slug::where('value', $slug)
            ->latest('id')
            ->value('value');
        if ($latestSlug) {
            $pieces = explode('-', $latestSlug);

            $number = intval(end($pieces));

            $slug .= '-' . ($number + 1);
            
            $slug .= rand(1, 1000);
        }
        return $slug;
    }

    public function createSlug($model, $row, $name, $table)
    {
        $slug = $this->slug($name, $table);
        Slug::create([
          'model' => $model,
          'row_id' => $row,
          'value' => $slug
        ]);
        return 1;
    }

    public function editSlug($model, $row, $name, $table)
    {
        $slugObject = Slug::where('model', $model)->where('row_id', $row)->first();
        if (!$slugObject) {
            return $this->createSlug($model, $row, $name, $table);
        }
        $slug = $this->slug($name, $table);
        $slugObject->update([
          'slug' => $slug
        ]);
        return 1;
    }

    public function deleteSlug($model, $row)
    {
        $slugObject = Slug::where('model', $model)->where('row_id', $row)->first();
        if (!$slugObject) {
            return 0;
        }
        $slugObject->delete();
        return 1;
    }

    public function getRowId($model, $slug)
    {
        $row = Slug::where('model', $model)->where('value', $slug)->first();
        if ($row) {
            return $row->row_id;
        }
        return null;
    }
}
