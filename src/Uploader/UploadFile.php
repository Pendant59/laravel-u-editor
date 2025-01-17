<?php namespace Pendant\UEditor\Uploader;

/**
 *
 *
 * Class UploadFile
 *
 * 文件/图像普通上传
 *
 * @package Pendant\UEditor\Uploader
 */
class UploadFile  extends Upload{
    use UploadQiniu;
    use UploadAliOss;
    public function doUpload()
    {


        $file = $this->request->file($this->fileField);
        if (empty($file)) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return false;
        }
        if (!$file->isValid()) {
            $this->stateInfo = $this->getStateInfo($file->getError());
            return false;

        }

        $this->file = $file;

        $this->oriName = $this->file->getClientOriginalName();

        $this->fileSize = $this->file->getSize();
        $this->fileType = $this->getFileExt();

        $this->fullName = $this->getFullName();


        $this->filePath = $this->getFilePath();

        $this->fileName = basename($this->filePath);


        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return false;
        }
        //检查是否不允许的文件格式
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return false;
        }

        if(config('UEditorUpload.core.mode')=='local'){
            try {
                $this->file->move(dirname($this->filePath), $this->fileName);

                $this->stateInfo = $this->stateMap[0];

            } catch (FileException $exception) {
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }

        }else if(config('UEditorUpload.core.mode')=='qiniu'){

            $content=file_get_contents($this->file->getPathname());
            return $this->uploadQiniu($this->filePath,$content);

        }else if(config('UEditorUpload.core.mode')=='oss'){

            $content=file_get_contents($this->file->getPathname());
            return $this->uploadAliOss($this->filePath,$content);

        }else if(config('UEditorUpload.core.mode')=='storage'){
            //上传文件到oss
            $folder = config('UEditorUpload.core.storage.folder');
            if(config('UEditorUpload.core.storage.classifyByFileType')){
                $folder .='/'. str_replace('.','',$this->fileType);
            }
            $path = \Storage::putFile($folder, $this->file);
            $this->fullName = \Storage::url($path);
            $this->stateInfo=$this->stateMap[0];

        }else{
            $this->stateInfo = $this->getStateInfo("ERROR_UNKNOWN_MODE");
            return false;
        }

        return true;

    }
}
