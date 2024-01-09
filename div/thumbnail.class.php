<?
class Thumbnail{
   static $conf=null;
   
   function div($a, $b){
		return ($a-($a % $b))/$b;
	}

   static function conf($conf=array()){
      $def = array("convert"=>"/usr/bin/convert", 'exec'=>"exec");
      # evtl. ini conf
      if(function_exists("xorc_ini")){
         $ini = xorc_ini("image_magick");
      }
      if(!is_array($ini)) $ini = array();
      self::$conf=array_merge($def, $ini, $conf);
   }
   
   
	function gen_thumb($src = "", $dest = "", $w = "", $h = "", 
		$shave = false, $allow_rotate=false, $enh=array(), $noexec=false){
		
		$opts = array('shave'=>false, 'allow_rotate'=>false, 'enh'=>array(), 'noexec'=>false, 
			'upscale'=>true, 'oncreatefail'=>'delete', 'quality'=>80,
			'gravity'=>'center'
			);
			
		if(is_array($shave)){
			$opts = array_merge($opts, $shave);
		}else{
			$opts = array_merge($opts, array('shave'=>$shave, 'allow_rotate'=>$allow_rotate,
				'enh'=>$enh, 'noexec'=>$noexec));
		}
		if(preg_match('/:/', $opts['shave'])){
			$opts['shave'] = explode(':', $opts['shave']);
		}
		# einmalige configuration
		if(is_null(Thumbnail::$conf)) Thumbnail::conf();
		
# echo "width = $w , h = $h \n";
		$mog = Thumbnail::$conf['convert'];       //   -crop {$w}x{$h}+{$pos_xy}
		
		$b = getimagesize($src);
		$sw = $b[0];
		$sh = $b[1];
		if(!$h){
			// crop with fixed ratio?
			if(is_array($opts['shave'])){
				$th = ($w/$opts['shave'][0])*$opts['shave'][1];
			}else{
				$th = ($w/$sw)*$sh;
			}
			$h = floor($th);
		}
		if( ( ($sw>$sh && $w<$h) || ($sw<$sh && $w>$h) ) && $opts['allow_rotate'] ){
			$tmp=$w; $w=$h; $h=$tmp;
		}

		// upscale verboten?
		if(!$opts['upscale'] && $sw<$w && $sh<$h){
			if(is_array($opts['shave'])){
				$w = $sw;
				$th = ($w/$opts['shave'][0])*$opts['shave'][1];
				$h = floor($th);
			}else{
				if($opts['oncreatefail']=='delete'){
					@unlink($dest);
				}
				$ret="ERROR. upscale denied";
				log_error("$ret ($sw x $sh vs. $w x $h)");
				return $ret;
			}
		}
		
		$quality="";
		// jpegs				
		if($b[2]==2){
			$quality="-quality {$opts['quality']}";
			$enh_def=array("equa"=>0, "norm"=>0, "sharp"=>1);
#			$enh_def=array("equa"=>0, "norm"=>1, "sharp"=>1);
		}else{
			$enh_def=array("equa"=>0, "norm"=>0, "sharp"=>0);
		}
		
		$enh=array_merge($enh_def, $opts['enh']);
		
		//cut of edges (shave the pic)
		$gravity = "-gravity {$opts['gravity']}";
		$pos = "+0+0";

		if($opts['shave']){
           if(($sw/$w > 1.1 && $sh/$h > 1.1) || $shave==2){
                   $max=($sw/$w > $sh/$h)? "x$h" : "{$w}x";
                   $resize="-resize $max";
           }
           $crop="{$gravity} -crop {$w}x{$h}{$pos}";
        }else{
           $resize="-resize {$w}x{$h}";
           $crop="";
        }
		
/*		
		$resize = " -resize {$w}";
		if(((($sw/$sh)-($w/$h))!=0) && $shave){
			$gravity = "-gravity center";
			$pos = "+0+0";
			if($sw>$sh && $w>$h){
				//gleiches format
				$resize = " -resize x{$h}";
			}else{
				//falsches format
				$resize = " -resize {$w}";
			}
		}
		// end shave
*/

		if($enh['norm']) $norm="-normalize -contrast";
      else $norm="";

      if($enh['equa']) $equa="-equalize";
      else $equa="";

      if($enh['sharp']){
//			$sfakt=floor(($w+$h)/20);
//			$sharp="-unsharp $sfakt";
			if(strval($enh['sharp'])!="1"){
				$sharp="-unsharp ".$enh['sharp'];
			}else{
				$sharp="-unsharp 2x1.4+0.55+0.01";	// x1.0+0.55+0.05
				$sharp="-sharpen 1x30";	// x1.0+0.55+0.05
			}
		}else{
			$sharp="";
		}

//		print "$src ~ $dest ~ $sw x $sh -> $w x $h\n";
		// scale image?
//		echo "4:3 Image<br>";
		$max = 0;

      $src=escapeshellarg($src);
      $dest=escapeshellarg($dest);

      # $colorspace = "-colorspace RGB";
      $colorspace = "";
      $parms="$colorspace {$resize} $norm $equa {$gravity} {$crop} $sharp $quality";
		
		if($opts['noexec']) return $parms;
		
		$cmd = "$mog $src $parms $dest";
		log_error("$cmd\n<br>");
		
		// überschreibbare exec-methode
		$execm = Thumbnail::$conf['exec'];
		$execm($cmd, $outp, $rc);
		
		if($rc){
			$ret="ERROR. mogrify failed (rc $rc).[".join("\n", $outp)."]";
		}else{
			return true;
		}
	}

	function abs($a){
		if($a < 0) return ($a+(-1));
		return $a;
	}

	function gen_thumb_gd($src = "", $dest = "", $w = "", $h = "", $shave = false, $allow_rotate=false){
		list($ow, $oh, $from_type) = getimagesize($src);

		switch($from_type){
			case 1: // GIF
					$srcImage = imageCreateFromGif($src);
					break;
			case 2: // JPG
					$srcImage = imageCreateFromJpeg($src);
					break;
			case 3: // PNG
					$srcImage = imageCreateFromPng($src);
					break;
		}
		if( ( ($ow>$oh && $w<$h) || ($ow<$oh && $w>$h) ) && $allow_rotate ){
			$tw = $w;
			$w = $h;
			$h = $tw;
		}
		$tempw = $w;
		//echo " $oh * $w ) / $ow \n";
		$temph = number_format((($oh*$w)/$ow), 0);
	
		if($temph < $h){
			$tempw = number_format((($ow*$h)/$oh), 0);
			$temph = $h;
		}
		//echo " $temph / $tempw $oh / $ow";
		$tempImage = imageCreateTrueColor($tempw, $temph);
		imageAntiAlias($tempImage, true);
	
		imagecopyresampled($tempImage, $srcImage, 0, 0, 0, 0, $tempw, $temph, $ow, $oh);
		imagedestroy($srcImage);
		if($shave){
			// Calculate offsets
			if($temph > $h)
			{
				$offsety = number_format(($temph/2)-($h/2), 0);
				$offsetx = 0;
			}
			else
			{
				$offsety = 0;
				$offsetx = number_format(($tempw/2)-($w/2), 0);
			}
			//echo " $offsetx / $offsety\n";
			$destImage = imageCreateTrueColor($w, $h);
	
			imagecopyresampled($destImage, $tempImage, 0, 0, $offsetx, $offsety, $w, $h, $w, $h);
//			Thumbnail::gamma_gd($destImage);
			Thumbnail::sharpen_gd($destImage, $w, $h);
			imageJpeg($destImage, $dest, 80);
			imagedestroy($destImage);
		}else{
//			Thumbnail::gamma_gd($tempImage);
			Thumbnail::sharpen_gd($tempImage, $tempw, $temph);
			imageJpeg($tempImage, $dest, 80);
			imagedestroy($tempImage);
		}
	}

	function gamma_gd(&$img){
		for($a=0;$a<imagecolorstotal ($img);$a++){
           $color = ImageColorsForIndex($img, $i);
           $R=.299 * ($color['red'])+ .587 * ($color['green'])+ .114 * ($color['blue']);
           $G=.299 * ($color['red'])+ .587 * ($color['green'])+ .114 * ($color['blue']);
           $B=.299 * ($color['red'])+ .587 * ($color['green'])+ .114 * ($color['blue']);
           ImageColorSet($img, $a, $R, $G, $B);
       } 
	}
		
	function sharpen_gd(&$img, $width, $height){
		$pix=array();
		
		//get all color values off the image
		for($hc=0; $hc<$height; ++$hc){
		   for($wc=0; $wc<$width; ++$wc){
		       $rgb = ImageColorAt($img, $wc, $hc);
		       $pix[$hc][$wc][0]= $rgb >> 16;
		       $pix[$hc][$wc][1]= $rgb >> 8 & 255;
		       $pix[$hc][$wc][2]= $rgb & 255;
		   }
		}
		
		//sharpen with upper and left pixels
		$height--; $width--;
		for($hc=1; $hc<$height; ++$hc){       
		   $r5=$pix[$hc][0][0];
		   $g5=$pix[$hc][0][1];
		   $b5=$pix[$hc][0][2];           
		   $hcc=$hc-1;
		   for($wc=1; $wc<$width; ++$wc){
		       $r=-($pix[$hcc][$wc][0]);
		       $g=-($pix[$hcc][$wc][1]);
		       $b=-($pix[$hcc][$wc][2]);       
		
		       $r-=$r5+$r5; $g-=$g5+$g5; $b-=$b5+$b5;   
		      
		       $r5=$pix[$hc][$wc][0];
		       $g5=$pix[$hc][$wc][1];
		       $b5=$pix[$hc][$wc][2];
		  
		       $r+=$r5*5; $g+=$g5*5; $b+=$b5*5;       
		
		       $r*=.5; $g*=.5; $b*=.5;
		      
		//here the value of 0.75 is like 75% of sharpening effect
		//Change if you need it to 0.01 to 1.00 or so
		//Zero would be NO effect
		//1.00 would be somewhat grainy
		
		       $r=(($r-$r5)*.75)+$r5;
		       $g=(($g-$g5)*.75)+$g5;
		       $b=(($b-$b5)*.75)+$b5;       
		
		       if ($r<0) $r=0; elseif ($r>255) $r=255;
		       if ($g<0) $g=0; elseif ($g>255) $g=255;
		       if ($b<0) $b=0; elseif ($b>255) $b=255;
		       imagesetpixel($img,$wc,$hc,($r << 16)|($g << 8)|$b);
		   }           
		}
	}
	
	function sharpen_gd2(&$img, $w="", $h=""){
		$sharpenMatrix = array(-1,-1,-1,-1,16,-1,-1,-1,-1);
		$divisor = 8;
		$offset = 0;
		imageconvolution($img, $sharpenMatrix, $divisor, $offset);
	}
}
?>
