<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';
    protected $fillable = array('name', 'mime', 'path', 'url', 'size' );

    public function humanFileSize()
    {
        if ($this->size >= 1073741824) {
            $fileSize = round($this->size / 1024 / 1024 / 1024, 1) . 'GB';
        } elseif ($this->size >= 1048576) {
            $fileSize = round($this->size / 1024 / 1024, 1) . 'MB';
        } elseif ($this->size >= 1024) {
            $fileSize = round($this->size / 1024, 1) . 'KB';
        } else {
            $fileSize = $this->size . ' bytes';
        }
        return $fileSize;
    }
}
