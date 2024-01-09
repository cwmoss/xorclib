<?php

class XorcStore_File{

    var $id;
    var $oname;     // object name
    var $basedir;  // name of directory
    var $baseurl;   

    var $conf=array();
    var $props=array('type', 'size', 'remote', 'basename', 'fullname');
    var $exists=false;
    
    function setup($name, $conf=array()){
        $this->conf=$conf;
        $this->oname=$name;
        $this->basedir=$conf['basedir'];
        // if(!$this->basedir) $this->basedir=$this->basedir();
        $this->baseurl=$conf['baseurl'];
        // if(!$this->baseurl) $this->baseurl=$this->baseurl();
    }


    function remove(){
        $filename=$this->basedir."/".XorcStore_File::name($this->name, $this->id);
        if(file_exists($filename)) return unlink($filename);
        return false;
    }

    function load($id, $name, $vals=array()){
        $this->id=$id;
        $this->name=$name;
        if($this->test_exist()){
            foreach($vals as $k=>$v){
                if($vname=$this->conf[$k])
                    $this->$vname=$v;
            }
        }
    }
    
    function size(){
        if($this->size) return $this->size;
        $this->size=filesize($this->fullname);
        return $this->size;
    }
    
    function type(){
        if($this->type) return $this->type;
        $this->type=XorcStore_File::detect_type($this->fullname);
        return $this->type;
    }
    
    function detect_type($file, $cmd="/usr/bin/file"){
        if(!file_exists($file) || !(is_file($file) && is_readable($file)))
            return false;
        $t=`$cmd -bi $file`;
        if(!$t) $t="application/ocet-stream";
        return $t;
    }
    
    function human_size($bytes, $decimal = '2'){
       if( is_numeric( $bytes ) ) {
         $position = 0;
         $units = array( " Bytes", " KB", " MB", " GB", " TB" );
         while( $bytes >= 1024 && ( $bytes / 1024 ) >= 1 ) {
             $bytes /= 1024;
             $position++;
         }
         return round( $bytes, $decimal ) . $units[$position];
       }
       else {
         return "0 Bytes";
       }
    }
    
    function url(){
        return $this->baseurl."/".$this->name;
    }
    
    function link(){
        return sprintf('<a href="%s">%s</a>', $this->url(), $this->name);
    }
    
    function test_exist(){
        if(!$this->name) return;
        $fname=XorcStore_File::name($this->name, $this->id);
        $fullname=$this->basedir."/".$fname;
        if(file_exists($fullname)){
            $this->exists=true;
            $this->filename=$fname;
            $this->fullname=$fullname;
            return true;
        }
        return false;
    }
    
    function sql(){
        $sql=array();
        foreach($this->conf as $c=>$val){
            if(!$this->exists && in_array($c, $this->props)){
                $sql[]="$val=null";
                continue;
            }
            switch($c){
                case "size": $sql[]="$val=$this->size"; break;
                case "type": $sql[]="$val='$this->type'"; break;
                case "remote": $sql[]="$val='$this->remote'"; break;
            }
        }
        if(!$this->exists) $sql[]="$this->oname=null";
        else $sql[]="$this->oname = '".$this->name."'";
        return $sql;
    }
    
    function id($id=null){
        if(is_null($id)) return $this->id;
        $this->id=$id;
        return $id;
    }
    
    function set($upload, $name=""){
        if(is_array($upload)){
            $this->set_uploadhash($upload);
        }elseif(is_object($upload)){
            $this->set_upload($upload);
        }elseif(is_string($upload)){
            $this->set_uploadfile($upload, $name);
        }
    }
    
    function set_uploadhash($upload){
        $this->remote = $upload['remote'];
        $this->name = XorcStore_File::safe_name($this->remote);
        $this->size = $upload['size'];
        $this->type = $upload['type'];
        $this->ext = $upload['ext'];
        $this->tmp = $upload['tmp'];
        $this->exists=true;
    }
    
    function set_upload($upload){
        $this->remote = $upload->prop['remote'];
        $this->name = XorcStore_File::safe_name($this->remote);
        $this->size = $upload->prop['size'];
        $this->type = $upload->prop['type'];
        $this->ext = $upload->prop['ext'];
        $this->tmp = $upload->tmp;
        $this->exists=true;
    }
    
    function set_uploadfile($upload, $name=""){
        $this->remote = $name?$name:basename($upload);
        $this->name = XorcStore_File::safe_name($upload);
        $this->fullname = $upload;
        $this->tmp = $upload;
        $this->size = $this->size();
        $this->type = $this->type();
        
        $this->exists=true;
    }
    
    function save(){
        if($this->exists && $this->tmp){
            
            print "SAVING $this->name #$this->id#";
            $this->filename=XorcStore_File::name($this->name, $this->id);

            print $this->filename;
            $ok=XorcStore_File::copy($this->tmp, $this->basedir."/".$this->filename, 0777);
        
            if($ok) $ok=$this->after_checkin();
            return $ok;
        }
    }

    function after_checkin(){return true;}
    
    function name($name, $id=null){
        if(false && is_null($id)){
            return $this->path_by_name($name);
        }else{
            return $this->path_by_id($id, $name);
        }
    }
    
    function filename(){
        return $this->fullname;
    }
    
    function path_by_name($name){
        $name=str_pad($name, 4);
        return substr($name, 0, 2)."/".substr($name, 2, 2)."/$name";
	}

    function path_by_id($id, $name){
        $p = sprintf("%09s", $id);
        return substr($p, 0, 3)."/".substr($p ,3 ,3)."/{$id}_{$name}";  
    }
    
    function safe_name($name){
        $name=basename(str_replace('\\', '/', $name));
        $name=str_replace(" ", "_", $name);
        $name=preg_replace("/[^-_.A-Za-z0-9]/", "", $name);
        if(preg_match("/^\.+$/", $name)) $name="_".$name;
        if(!$name) $name="no_name";
        return $name;
    }
    
    function copy($src, $dest, $createmode=false){
        if(is_dir(dirname($dest)) ||
            ($createmode && XorcStore_File::mkdirs(dirname($dest), $createmode))){
            print "cp $src -- $dest#\n";
            return copy($src, $dest);
        }
        print "cp FAILED";
        return false;
    }
    
    function mkdirs($dir, $mode = 0777, $recursive = true) {
        if( is_null($dir) || $dir === "" ){
            return false;
        }
        if( is_dir($dir) || $dir === "/" ){
            return true;
        }
        if( XorcStore_File::mkdirs(dirname($dir), $mode, $recursive) ){
            return mkdir($dir, $mode);
        }
        return false;
    }
    
    function send_to_browser(){
        $loc=$this->filename;

    	Header("Content-Type: {$this->type}");
    	//Header("Content-Length: ".filesize($loc));
    	//Header("Content-Disposition: attachment; filename=$name");
    	readfile($loc);
    }
    
    function baseurl(){return "/no/baseurl/configured";}
    function basedir(){return "/no/basedir/configured";}
    
    function is_image(){
        return preg_match("/(jpe?g)|(gif)|(png)/i", $this->type);
    }

    function is_photo(){
        return preg_match("/jpe?g/i", $this->type);
    }

    function is_graphic(){
        return preg_match("/(gif)|(png)/i", $this->type);
    }
}
?>