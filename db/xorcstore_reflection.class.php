<?php

class xorcstore_reflection {

	static public $r = array();
	static public $db = array();

	static function columns($o) {
		return self::$r[$o->klas]['fields'];
	}

	static function primary_key($o) {
		return self::$r[$o->klas]['keys'][0];
	}

	static function is_primary_key($o, $k) {
		return self::$r[$o->klas]['keys'][0] == $k;
	}

	static function assoc($o, $name) {
		return self::$r[$o->klas]['relation_names'][$name];
	}

	static function assocs($o) {
		return self::$r[$o->klas]['relation_names'];
	}

	static function auto_created($o) {
		if (isset(self::$r[$o->klas]['autodate']['created']))
			return self::$r[$o->klas]['autodate']['created'];
		return false;
	}

	static function auto_modified($o) {
		if (isset(self::$r[$o->klas]['autodate']['modified']))
			return self::$r[$o->klas]['autodate']['modified'];
		return false;
	}

	static function auto_increment($o) {
		if (isset(self::$r[$o->klas]['autoinc']))
			return self::$r[$o->klas]['autoinc'];
		return array();
	}

	static function column_exists($o, $c) {
		return isset(self::$r[$o->klas]['fields'][$c]);
	}

	static function type_of($o, $c) {
		return self::$r[$o->klas]['fields'][$c];
	}

	static function assoc_exists($o, $a) {
		return isset(self::$r[$o->klas]['relation_names'][$a]);
	}

	static function assoc_many_exists($o, $a) {
		return isset(self::$r[$o->klas]['relation_names'][$a]) &&
			(self::$r[$o->klas]['relation_names'][$a][0] == "has_many" ||
				self::$r[$o->klas]['relation_names'][$a][0] == "has_many_belongs_to_many");
	}

	static function assoc_single_exists($o, $a) {
		return isset(self::$r[$o->klas]['relation_names'][$a]) &&
			(self::$r[$o->klas]['relation_names'][$a][0] == "belongs_to" ||
				self::$r[$o->klas]['relation_names'][$a][0] == "has_one");
	}

	static function assoc_belongs_to_exists($o, $a) {
		return isset(self::$r[$o->klas]['relation_names'][$a]) &&
			self::$r[$o->klas]['relation_names'][$a][0] == "belongs_to";
	}

	static function extensions($o) {
		return self::$r[$o->klas]['extensions'];
	}

	static function is_serialize_field($o, $f) {
		return isset(self::$r[$o->klas]['serialize'][$f]);
	}

	static function serializations($o) {
		return self::$r[$o->klas]['serialize'];
	}

	static function assoc_opts($o, $a) {
		return self::$r[$o->klas]['relation_names'][$a];
	}

	static function sti_type($o) {
		return self::$r[$o->klas]['table']['sti_type'];
	}

	static function sti_type_by_name($oname) {
		return self::$r[$oname]['table']['sti_type'];
	}

	static function sti_type_condition($o) {
		if (($t = self::$r[$o->klas]['table']['sti_type']) &&
			$o->klas != self::$r[$o->klas]['class']
		) {
			#   print $o->klas;
			return array($t => $o->klas);
		} else
			return false;
	}

	static function table($o) {
		return self::$r[$o->klas]['table']['name'] ?? "";
	}

	static function prefix($o) {
		return self::$r[$o->klas]['table']['prefix'] ?? "";
	}

	static function database($o) {
		return self::$r[$o->klas]['table']['db'];
	}

	static function sequence($o) {
		return self::$r[$o->klas]['table']['sequence'] ?? null;
	}

	static function dbfunctions($o) {
		return self::$r[$o->klas]['dbfunctions'] ?? null;
	}

	static function idfunction($o) {
		return self::$r[$o->klas]['table']['idfunction'] ?? null;
	}

	static function db_adapter($o, $func) {
		$db = self::$r[$o->klas]['db'];
		return $db[$func];
	}

	static function read_schema($obj, $klas) {
		log_error($klas);
		if (isset(self::$r[$klas])) return;
		$clas = $obj->MASTER ? $obj->MASTER : get_class($obj);
		$clas = strtolower($clas);

		#log_error("loading REFL $klas");
		#log_error($obj);

		if ($obj->SCHEMAPATH) {
			$inifile = dirname($obj->SCHEMAPATH) . "/schema/{$clas}.ini";
		} elseif (defined("XORC_DB_SCHEMAPATH")) {
			$inifile = XORC_DB_SCHEMAPATH . "/{$clas}.ini";
		} else {
			$inifile = null;
		}
		log_error($inifile);
		if ($inifile) $conf = @parse_ini_file($inifile, true);

		if ($inifile && $conf) {
			$conf['keys'] = array_keys($conf['keys']);
			$db = isset($conf['table']['db']) ? $conf['table']['db'] : "";

			$con = XorcStore_Connector::get($db);
			$prefix = ($con->prefix && !$conf['table']['noprefix']) ?
				$con->prefix . "_" : "";
			$conf['table']['prefix'] = $prefix;
			$conf['table']['name'] = $prefix . $conf['table']['name'];
			if (isset($conf['table']['sequence']))
				$conf['table']['sequence'] = $prefix . $conf['table']['sequence'];
		} else {
			if (method_exists($obj, 'define_schema')) {
				$conf = $obj->define_schema();
			} else {
				$conf = $obj->define_schema_default($inifile, $klas);
			}

			if (!isset($conf['keys'])) $conf['keys'] = array("id");
			$conf['table'] = array("name" => $conf['table']);
			$con = XorcStore_Connector::get($conf['db'] ?? null);
			$prefix = ($con->prefix && !($conf['noprefix'] ?? false)) ?
				$con->prefix . "_" : "";
			$conf['table']['prefix'] = $prefix;
			$conf['table']['name'] = $prefix . ($conf['table']['name'] ?? "");
			$conf['table']['db'] = $conf['db'] ?? null;
			$conf['table']['sequence'] = $conf['sequence'] ?? null;
			$conf['table']['idfunction'] = $conf['idfunction'] ?? null;
			$conf['table']['sti_type'] = $conf['sti_type'] ?? null;
			$conf['fields'] = self::meta_info($con, $conf['table']['name']);
			if ($conf['fields']['created_at'] ?? null && $conf['fields']['created_at'] == '4') $conf['autodate']['created'] = "created_at";
			if ($conf['fields']['modified_at'] ?? null && $conf['fields']['modified_at'] == '4') $conf['autodate']['modified'] = "modified_at";
		}

		if ($con->use_sequences) {
			if (!$conf['table']['sequence'] && !$conf['table']['idfunction'])
				$conf['table']['sequence'] = $conf['table']['name'] . "_seq";
		} else {
			unset($conf['table']['sequence']);
		}
		#        print_r($conf);
		$conf['class'] = $clas;

		$conf['relation_names'] = self::read_relations($conf['relations'] ?? null, $obj);
		$conf['files'] = self::read_files($conf);

		@$conf['db']['substr'] = $con->substr;
		@$conf['db']['nameq'] = $con->nameQuote;
		@$conf['db']['sysTimeStamp'] = $con->sysTimeStamp;

		$ext = array();
		if ($conf['extensions'] ?? null) foreach ($conf['extensions'] as $ext_class => $ext_opts) {
			$exto = new $ext_class;
			$ext[$ext_class] = $ext_opts;
		}
		$conf['extensions'] = $ext;

		$ser = array();
		if ($conf['serialize'] ?? null) foreach ($conf['serialize'] as $name => $opts) {
			if (is_string($opts)) {
				$name = $opts;
				$opts = array();
			}
			$ser[$name] = $opts;
		}
		$conf['serialize'] = $ser;
		self::$r[$klas] = $conf;
	}

	static function meta_info($con, $tab) {
		$trans = array("R" => 1, "I" => 1, "C" => 2, "D" => 3, "T" => 4, "X" => 2, "N" => 7);
		$meta = array();
		$info = $con->MetaColumns($tab);
		#print_r($info);
		foreach ($info as $c) {
			#          print $con->MetaType($c->type)." # ";
			$meta[$c->name] = $trans[$con->MetaType($c->type)];
		}
		return $meta;
	}

	static function read_relations($conf, $o) {
		$rels = array();
		// $kv=get_class_vars($klas);
		foreach (xorcstore_assoc::$types as $type) {
			if (isset($conf[$type]))
				foreach (explode(",", $conf[$type]) as $rel)
					$rels[$rel] = array($type, self::_parse_rel_opts($rel, $conf));
			foreach ($o->$type() as $rel => $opts)
				$rels[$rel] = array($type, $opts);
		}
		return $rels;
	}
	function _parse_rel_opts($rel, &$conf) {
		#print_r($conf);
		$clas = isset($conf[$rel . ".class"]) ? $conf[$rel . ".class"] : $rel;
		$fkey = isset($conf[$rel . ".fkey"]) ? $conf[$rel . ".fkey"] : "";
		$mykey = isset($conf[$rel . ".my.fkey"]) ? $conf[$rel . ".my.fkey"] : "";
		$jt = isset($conf[$rel . ".jointable"]) ? $conf[$rel . ".jointable"] : "";
		$seq = isset($conf[$rel . ".seq"]) ? $conf[$rel . ".seq"] : "";
		return array(
			'class' => $clas,
			'fkey' => $fkey,
			"myfkey" => $mykey,
			"join_table" => $jt,
			"position" => $seq,
		);
	}

	static function read_files($conf) {
		$files = array();
		foreach ($conf['fields'] as $k => $type) {
			if ($type == 6) {
				$files[] = $k;
			}
		}
		return $files;
	}
}
