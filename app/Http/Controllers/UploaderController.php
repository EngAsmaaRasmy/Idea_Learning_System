<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Throwable;

class UploaderController extends Controller
{
    use ApiResponser;

  /**
   * Handles the file upload
   *
   * @param Request $request
   *
   * @return JsonResponse
   *
   * @throws UploadMissingFileException
   * @throws UploadFailedException
   */
    public function upload(Request $request)
    {  //from web route
       // create the file receiver
        try {
            $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

            // check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                return $this->error(__('main.video_not_uploaded'), 404);
            }

            // receive the file
            $save = $receiver->receive();

            // check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
            // save the file and return any response you need, current example uses `move` function. If you are
            // not using move, you need to manually delete the file by unlink($save->getFile()->getPathname())
                return $this->saveFile($save->getFile(), $request);
            }
            // we are in chunk mode, lets send the current progress
            /** @var AbstractHandler $handler */
            $handler = $save->handler();

            return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
            ]);

        } catch(Throwable $e) {
            return response()->json($e);
        }
    }

  /**
   * Saves the file
   *
   * @param UploadedFile $file
   *
   * @return JsonResponse
   */
    protected function saveFile(UploadedFile $file, Request $request)
    {
        $fileName = $this->createFilename($file);

        // Get file mime type
        $mime_original = $file->getMimeType();
        $mime = str_replace('/', '-', $mime_original);
        $folderDATE = $request->dataDATE;
        $folder  = $folderDATE;
        $uploads_folder =  getcwd() . '/uploads/lessons/';
        if (!file_exists($uploads_folder)) {
                mkdir($uploads_folder, 0777, true);
        }
        // $filePath = "public/upload/lessons/{$folder}/";
        // $finalPath = storage_path("app/" . $filePath);
        $fileSize = $file->getSize();
       // move the file name
        $file->move($uploads_folder, $fileName);
        $url_base = 'storage/upload/lessons/' . "{$folderDATE}/" . $fileName;
        $control_var = Media::create([
            'name' => $fileName,
            'mime' => $mime_original,
            'url' => $url_base,
            'size' => $fileSize
        ]);
        return $this->success([
        'name' => $fileName,
        'id' => $control_var->id,
        'lesson_index' => $request->input('lesson_index'),
        'mime_type' => $mime
        ]);
    }
    /**
     * Create unique filename for uploaded file
     * @param UploadedFile $file
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(20);

        //here you can manipulate with file name e.g. HASHED
        return $filename . "." . $extension;
    }
    /**
     * Delete uploaded file WEB ROUTE
     * @param Request request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        $file = $request->filename;
        $dir = $request->date;
        $path = public_path() . "/uploads/lessons/" . $file;
        if (File::delete(public_path() . "/uploads/lessons/" . $file)) {
            $media = Media::where('name', $file)->delete();
            return $this->success('', 'تم حذف الفيديو');
        } else {
            return $this->error(' حدث خطأ', 422);
        }
    }
}
