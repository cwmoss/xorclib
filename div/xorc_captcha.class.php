<?php

class Xorc_Captcha{
	/**
	 * @var array
	 */
	private $options;
	public $img_url;
	public $img_file;
	
	private $img_built = false;
	
	function __construct($options=array()){
		$default = array(
			'font_size' => 25,
			'font_path' => '/usr/share/fonts/corefonts/',
			'font_file' => 'times.ttf',
			'lines_color'=> '#000000',
			'background_color'=> '#FFFFFF',
		
			'delete_every' => 500,
			'img_width' => 170,
			'img_heigth' => 60,
			'img_path' => Xorcapp::$inst->conf['general']['var'].'/captcha',
			'img_url' => XorcApp::$inst->env->httpbase.'/captcha',
			'img_alt' => 'captcha bild',
		);
		$conf_options = @XorcApp::$inst->conf['captcha'];
		if(!$conf_options) $conf_options = array();

		$this->options=array_merge(array_merge($default, $conf_options),$options);
		
		$this->img_url=$this->options['img_url']."/sess_".md5(session_id()).'.png';
		$this->img_file=$this->options['img_path']."/sess_".md5(session_id()).'.png';
	}

	/**
	 * image & input
	 */
	public function html(){
		return $this->image()."<br />\n".$this->input();
	}
	
	/**
	 * type text here...
	 */
	public function input(){
		return text_field_tag('captcha','');
	}

	/**
	 * captcha image html
	 */
	public function image(){
		if(!$this->img_built)$this->build_image();
		return("<img src=\"$this->img_url\" border=\"0\" alt=\"".$this->options['img_alt']."\">");
	}
	
	private function build_image(){
		if(rand(1,$this->options['delete_every'])==1)$this->delete_old();
		
		require_once 'Text/CAPTCHA.php';
		$c = Text_CAPTCHA::factory('Image');

		//		print_r($this->opts);
		$retval = $c->init($this->options['img_width'], $this->options['img_heigth'], null, $this->options);
		if (PEAR::isError($retval)) {
			echo 'Error generating CAPTCHA! ret';
			return;
		}
		// Get CAPTCHA secret passphrase
		$_SESSION["xorc_captcha"] = $c->getPhrase();

		// Get CAPTCHA image (as PNG)
		$png = $c->getCAPTCHAAsPNG();
		if (PEAR::isError($png)) {
			echo 'Error generating CAPTCHA! png';
			return;
		}

		//		file_put_contents($this->imgf, $png);
		$fo=fopen($this->img_file, "wb");
		fwrite($fo, $png);
		fclose($fo);
		//		header("Content-Type: image/png");
		//		print $png;
		$this->img_built = true;
	}
	
	private function delete_old(){
		$path = $this->options['img_path'].'/';
		//find all *.png older than xxx in captcha dir and remove them
		exec("find $path -name *.png -mmin +60 -print0 | xargs -0 rm",&$out);
//		var_dump($out); 
	}
	
}
#convert -font ArialBold.ttf -background white -pointsize 26 -style Italic -fill '#666' -gravity NorthWest label:'d' -wave 20 -fuzz 30% +trim \( -font ArialBold.ttf label:'H' -swirl -15 -fuzz 30% -trim  \)  +append captcha3.png

?>