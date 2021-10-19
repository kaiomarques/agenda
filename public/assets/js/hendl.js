
// TODO: criar libs O.O [RadioBox, CheckBox, ComboBoxMultiple, ComboBox, TabNavigator, datatables] (com padrao bootstrap)

var hendl = {
	"setImagePath" : function ($image_path) {
		this.JComponent.IMAGE_PATH = $image_path;
		return this;
	},
	"queue" : {
		"START" : 1,
		"END"   : 0,
		"QUEUE" : [],
		"run" : function ($arrCallbacks, s) {

			if (s === 1) {

				if (this.START <= this.END) {

					this.CURRENT = this.QUEUE[this.START - 1];
					hendl.php.call_user_func_array(this.CURRENT[0], this.CURRENT[1]);

					if (this.START == this.END) {
						this.START = 1;
						this.END   = 0;
						this.QUEUE = [];
					}
					else {
						this.START++;
						setTimeout("hendl.queue.run(null, 1)", 20);
					}

				}

			}
			else {
				this.QUEUE = hendl.php.is_array($arrCallbacks, []);
				this.START = 1;
				this.END   = $arrCallbacks.length;
				this.run(null, 1);
			}

		}
	},
	"String" : {
		"format" : function () {

			var $search, $replace, $str = hendl.php.is_string(arguments[0], "");

			if ($str !== "") {

				for (var $x = 1, $len = arguments.length; $x < $len; $x++) {

					$replace = (hendl.php.isset(arguments[$x], "") + "");
					$str     = $str.replace("%s", $replace);
					$search  = ["{", ($x - 1), "}"].join("");

					for (var $y = 0, $length = hendl.php.substr_count($str, $search); $y < $length; $y++) {
						$str = $str.replace($search, $replace);
					}

				}

			}

			return $str;
		}
	},
	"php"  : {
		"getClass" : function () {
			return {"getName" : function () {return "php";}};
		},
		"call_user_func_array" : function ($callback, $param_arr) {

			function call_user_func($context, $method, $param_arr) {

				if ($context != null) {

					if (hendl.php.is_function($method)) {
						return $method.apply($context, $param_arr);
					}

					return $context[$method].apply($context, $param_arr);
				}

				return $method.apply(window, $param_arr);
			};

			var $type, $exec, $type1, $type2, $obj, $method, $aux_attr;

			$type      = this.gettype($callback);
			$param_arr = this.is_array($param_arr, []);
			$bind      = this.is_boolean(arguments[2], true);

			if ($type == "array") {

				$type1 = this.gettype($callback[0]);
				$type2 = this.gettype($callback[1]);

				if ($type1 == "object") {

					if ($type2 == "function") {
						return call_user_func($callback[0], $callback[1], $param_arr);
					}
					else if ($type2 == "string") {

						$obj    = $callback[0];
						$method = $callback[1];

						if ($method && this.is_function($obj[$method])) {
							return call_user_func($obj, $method, $param_arr);
						}
						else {
							throw (hendl.String.format("%s.%s is not a function", this.get_class($obj), $method));
						}

					}
					else {
						throw (hendl.String.format("%s.call_user_func_array::$callback[1] must be an function or string, %s given", this.getClass().getName(), $type2));
					}

				}
				else {
					throw (hendl.String.format("%s.call_user_func_array::$callback[0] must be an object, %s given", this.getClass().getName(), $type1));
				}

			}
			else if ($type == "function") {
				return call_user_func(null, $callback, $param_arr);
			}
			else {
				throw (hendl.String.format("%s.call_user_func_array::$callback[0] must be an array|function, %s given", this.getClass().getName(), $type));
			}

			return null;
		},
		"is" : function ($mixed, $typing) {

			if (arguments[2] !== undefined) {
				return (this.is($mixed, $typing) ? $mixed : arguments[2]);
			}

			return (this.gettype($mixed) == $typing);
		},
		"is_array" : function ($mixed) {
			return this.is($mixed, "array", arguments[1]);
		},
		"is_bool" : function ($mixed) {
			return this.is_boolean($mixed, arguments[1]);
		},
		"is_boolean" : function ($mixed) {
			return this.is($mixed, "boolean", arguments[1]);
		},
		"is_callable" : function ($mixed) {

			if (arguments[1] !== undefined) {
				return (this.is_callable($mixed) ? $mixed : arguments[1]);
			}
			else {

				if (this.is_array($mixed)) {

					if (this.is_object($mixed[0]) && this.is_function($mixed[1])) {
						return true;
					}

				}

			}

			return false;
		},
		"is_float" : function ($mixed) {
			return this.is($mixed, "double", arguments[1]);
		},
		"is_function" : function ($mixed) {
			return this.is($mixed, "function", arguments[1]);
		},
		"is_int" : function ($mixed) {
			return this.is($mixed, "integer", arguments[1]);
		},
		"is_integer" : function ($mixed) {
			return this.is_int($mixed, arguments[1]);
		},
		"is_nan" : function ($mixed) {
			return (arguments[1] === undefined ? isNaN($mixed) : (this.is_nan($mixed) ? $mixed : arguments[1]));
		},
		"is_null" : function ($mixed) {
			return this.is($mixed, "NULL", arguments[1]);
		},
		"is_object" : function ($mixed) {
			return this.is($mixed, "object", arguments[1]);
		},
		"is_string" : function ($mixed) {
			return this.is($mixed, "string", arguments[1]);
		},
		"isset" : function ($mixed) {

			if (arguments[1] !== undefined) {
				return (this.isset($mixed) ? $mixed : arguments[1]);
			}

			return ($mixed !== undefined && $mixed !== null);
		},
		"gettype" : function ($mixed) {

			var $type = $mixed !== undefined ? typeof($mixed) : "";

			if ($type == "number") {
				$type = $mixed.toString().indexOf(".") != -1 ? "double" : "integer";
			}
			else if ($type == "object") {
				$type = $mixed !== null ? ($mixed instanceof Array ? "array" : $type) : "NULL";
			}

			return $type;
		},
		"get_class" : function ($object) {
			return $object.constructor.toString().split(" ")[1].split("(")[0];
		},
		"substr_count" : function ($haystack, $needle) {

			var $count = 0;

			if (this.is_string($haystack) && $needle) {

				while ($haystack && $haystack.indexOf($needle) != -1) {
					$haystack = $haystack.replace($needle, "");
					$count++;
				}

			}

			return $count;
		}
	},
	"Ajax" : function ($csrfToken) {

		var $this = this;

		this.queue = {
			"START"   : 1,
			"END"     : 0,
			"CURRENT" : null,
			"QUEUE"   : [],
			"run"     : function ($queue, s) {

				if (s === 1) {

					if (this.START <= this.END) {

						this.CURRENT = this.QUEUE[this.START - 1];

						$this.call(this.CURRENT.method, this.CURRENT.url, this.CURRENT.data, [this, function ($response) {

							hendl.php.call_user_func_array(this.CURRENT.callback, [$response, this.CURRENT.data]);

							if (this.START == this.END) {
								this.START = 1;
								this.END   = 0;
								this.QUEUE = [];
							}
							else {
								this.START++;
								setTimeout(function () {
									$this.queue.run(null, 1);
								}, 10);
							}

						}], null);
					}

				}
				else {

					this.CURRENT = null;
					this.QUEUE   = hendl.php.is_array($queue, []);
					this.START   = 1;
					this.END     = $queue.length;

					$this.queue.run(null, 1);
				}

			}
		};

		this.extractCode = function ($str) {

			var $scope   = "";
			var $scripts = [];
			var $tag1    = "<script";
			var $tag2    = "</script>";
			var $lent1   = $tag1.length;
			var $lent2   = $tag2.length;
			var $tags1   = hendl.php.substr_count($str, $tag1);
			var $tags2   = hendl.php.substr_count($str, $tag2);

			if ($str && $tags1 > 0 && $tags1 == $tags2) {

				for (var $i = 0, $len = $str.length; $i < $len; $i++) {

					if ($scope == "JS") {

						if ($str.substr($i, $lent2) == $tag2) {
							$scope = "";
							$i += $lent2 - 1;
						}
						else {
							$scripts.push($str.substr($i, 1));
						}

					}
					else if ($scope == "TAG") {

						if ($str.substr($i, 1) == ">") {
							$scope = "JS";
						}

					}
					else if ($scope == "") {

						if ($str.substr($i, $lent1) == $tag1) {
							$scope = "TAG";
							$i += $lent1 - 1;
						}

					}

				}

			}

			$scripts = $scripts.join("");
			eval("$scripts = function () {" + $scripts + "}");

			return $scripts;
		},
		this.extractVars = function ($data) {

			var $type = hendl.php.gettype($data);
			var $aux  = [];

			if ($type == "object") {

				for (var $attr in $data) {

					if (hendl.php.is_array($data[$attr])) {
						$data[$attr].forEach(function ($value, $i) {

							if (hendl.php.isset($value)) {
								$aux.push(hendl.String.format("%s[%s]=%s", $attr, $i, $value));
							}

						});
					}
					else {

						if (hendl.php.isset($data[$attr])) {
							$aux.push(hendl.String.format("%s=%s", $attr, $data[$attr]));
						}

					}

				}

			}
			else if ($type == "string") {
				$aux.push($data);
			}

			return $aux.join("&");
		},
		this.innerHTML = function ($htmlObj, $message) {

			var $context;

			if (hendl.php.is_object($htmlObj)) {

				$message = hendl.php.isset($message, $htmlObj.message);
				$context = null;

				switch (hendl.php.gettype($htmlObj.context)) {

					case "object" :
						$context = $htmlObj.context;
						break;
					case "string" :
						$context = document.getElementById($htmlObj.context);
						break;

				}

				$htmlObj.context = $context;

				if (hendl.php.is_object($context)) {

					if (hendl.php.is_function($context.html)) { // jQuery
						$context.html($message);
					}
					else { // JS puro
						$context.innerHTML = $message;
					}

				}

			}

		},
		this.parseJSON = function ($value) {

			$value = hendl.php.is_string($value, "");

			if (($value.startsWith("[") && $value.endsWith("]")) || ($value.startsWith("{") && $value.endsWith("}"))) {
				return JSON.parse($value);
			}

			return null;
		},
		this.call = function ($method, $url, $data, $callback, $htmlObj) {

			var $http, $ajax;

			$htmlObj = hendl.php.isset($htmlObj, null);
			$ajax    = this;
			$http    = new XMLHttpRequest();
			$data    = $ajax.extractVars($data);

			$ajax.innerHTML($htmlObj);

			if ($method == "POST") {
				$http.open($method, $url, true);
				$http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			}
			else {
				$http.open($method, ($data ? ([$url, ($url.indexOf("?") != -1 ? "&" : "?"), $data].join("")) : $url), true);				
			}

			$http.setRequestHeader('X-CSRF-TOKEN', $csrfToken);
			$http.setRequestHeader("Access-Control-Allow-Origin", "*");
			$http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
			$http.setRequestHeader("HTTP_X_REQUESTED_WITH", "XMLHttpRequest");
			$http.onreadystatechange = function () {

				if ($http.readyState == 4) {

					var $queue     = [];
					var $fnCode    = $ajax.extractCode($http.responseText);
					var $param_arr = [];
					var $json      = $ajax.parseJSON($http.responseText);

					$queue.push([[$ajax, "innerHTML"], [$htmlObj, $http.responseText]]);

					if (hendl.php.is_function($fnCode)) {
						$queue.push([$fnCode, []]);
					}

					$param_arr.push(($json != null ? $json : $http.responseText));
					$param_arr.push($htmlObj);

					switch (hendl.php.gettype($callback)) {

						case "array" :
							$queue.push([[$callback[0], $callback[1]], $param_arr]);
							break;
						case "function" :
							$queue.push([$callback, $param_arr]);
							break;

					}

					hendl.queue.run($queue);
				}

			};

			return $http.send(($method == "POST" ? $data : null));
		}

	},
	"JComponent" : {
		"IMAGE_PATH" : null,
		"ELEMENTS"   : {},
		"getJSCall"  : function ($object, $funcName) {
			return hendl.String.format("hendl.%s('%s')%s", hendl.php.get_class($object), $object.getId(), (hendl.php.is_string($funcName) ? [".", $funcName, "(event)"].join("") : ""));
		},
		"get" : function ($id) {
			return hendl.php.is_object(this.ELEMENTS[$id], null);
		},
		"add" : function ($el) {

			if (hendl.php.is_object(this.ELEMENTS[$el.getId()])) {
				alert(hendl.String.format("Um elemento com o ID (%s) já foi criado. Escolha outro ID", $el.getId()));
				return false;
			}

			this.ELEMENTS[$el.getId()] = $el;
		},
		"call" : function ($context, $function, $event) {

			if (hendl.php.is_function($function)) {
				hendl.php.call_user_func_array([$context, $function], [$event]);
			}

		},
		"create" : function ($el, $div, $callback, $param_arr) {

			$div       = hendl.php.isset($div, null);
			$callback  = hendl.php.isset($callback, null);
			$param_arr = hendl.php.is_array($param_arr, []);

			switch (hendl.php.gettype($div)) {

				case "object" :

					if (hendl.php.is_function($div.html)) { // jQuery
						$div.html($el.html());
					}
					else {
						$div.innerHTML = $el.html();
					}

				break;
				case "string" :
					document.getElementById($div).innerHTML = $el.html();
				break;
				default :
					document.write($el.html());
				break;

			}

			var $type = hendl.php.gettype($callback);
			var $exec = false;

			if ($type == "array") {
				$exec = hendl.php.is_callable($callback);
			}
			else if ($type == "function") {
				$exec = true;
			}

			if ($exec) {
				hendl.php.call_user_func_array($callback, $param_arr);
			}

			return $el;
		}

	},
	"JCheckBox" : function JCheckBox($) {

		var $private = {};
		var $type    = hendl.php.gettype($);

		if ($type == "object") {

			if (arguments[1] === true) {

				this.__construct = function ($) {
					$private.group   = hendl.php.is_object($.group, null); // JCheckBoxGroup
					$private.index   = hendl.php.is_int($.index, -1); // if has JCheckBoxGroup
					$private.id      = hendl.php.is_string($.id, "");
					$private.value   = hendl.php.is_string($.value, "");
					$private.text    = hendl.php.is_string($.text, "");
					$private.checked = hendl.php.is_bool($.checked, false);
					$private.enabled = hendl.php.is_bool($.enabled, true);
					$private.click   = hendl.php.is_function($.click, null);
					$private.labelId = [$private.id, "label"].join("_");
				};

				this.getGroup = function () {
					return $private.group;
				};

				this.getDivId = function () {
					return ["div", $private.id].join("_");
				};

				this.getId = function () {
					return $private.id;
				};

				this.getLabelId = function () {
					return $private.labelId;
				};

				this.getJSCall = function ($funcName) {
					return hendl.JComponent.getJSCall(this, $funcName);
				};

				this.create = function ($div, $function) {
					return hendl.JComponent.create(this, $div, [this, $function]);
				};

				this.collection = function () {

					var $collection = {};

					$collection.main = document.getElementById(this.getId());
					$collection.text = document.getElementById(this.getLabelId());

					return $collection;
				};

				this.html = function () {

					var $html = [];

					$html.push(hendl.String.format('<label for="%s">', this.getId()));
					$html.push(hendl.String.format('<input type="checkbox" name="{0}" id="{0}" value="{1}" onclick="{2}" {3} {4} />', this.getId(), this.value(), this.getJSCall("click"), ($private.checked ? "checked" : ""), ($private.enabled ? "" : "disabled")));
					$html.push(hendl.String.format('&nbsp;&nbsp;'));
					$html.push(hendl.String.format('<span id="{0}">{1}</span>', this.getLabelId(), this.text()));
					$html.push(hendl.String.format('</label>'));

					return $html.join("");
				};

				this.index = function () {
					return $private.index;
				};

				this.value = function () {
					return $private.value;
				};

				this.text = function () {
					return $private.text;
				};

				this.checked = function ($bool) {

					if (hendl.php.isset($bool)) { // set
						$private.checked = hendl.php.is_bool($bool, false);
						this.collection().main.checked = $private.checked;
						return this;
					}

					return this.collection().main.checked;
				};

				this.enabled = function ($bool) {

					if (hendl.php.isset($bool)) { // set
						$private.enabled = hendl.php.is_bool($bool, true);
						this.collection().main.disabled = ($private.enabled == false);
						return this;
					}

					return (this.collection().main.disabled == false);
				};

				this.click = function ($event) {
					hendl.JComponent.call(this, $private.click, $event);
				};

				this.__construct($);
				hendl.JComponent.add(this);

				return 0;
			}

			return new hendl.JCheckBox($, true);
		}
		else if ($type == "string") {
			return hendl.JComponent.get($);
		}

		return null;
	},
	"JCheckBoxGroup" : function JCheckBoxGroup($) {

		var $private = {};
		var $type    = hendl.php.gettype($);

		if ($type == "object") {

			if (arguments[1] === true) {

				this.__construct = function ($) {
					$private.id              = hendl.php.is_string($.id, "");
					$private.options         = this.toOptions(hendl.php.is_array($.options, []));
					$private.optionSelectAll = hendl.php.is_boolean($.optionSelectAll, false);
					$private.chkall_id       = [$private.id, "chkall"].join("_");
					$private.chkall          = hendl.JCheckBox({"group" : this, "id" : $private.chkall_id, "value" : 1, "text" : "Todos", "click" : function () {
						this.getGroup().selectAll($private.chkall.checked());
					}});
				};

				this.toOptions = function ($options) {

					var $opt  = [];
					var $type = hendl.php.gettype($options);
					var $x    = 0;

					if ($type == "array") { // {value: "your value", text : "your text"}

						for (var $x = 0, $len = $options.length; $x < $len; $x++) {
							$opt.push(hendl.JCheckBox({"group" : this, "index" : $x, "id" : this.getChildId($x), "value" : ("" + $options[$x].value), "text" : ("" + $options[$x].text)}));
						}

					}

					return $opt;
				};

				this.getId = function () {
					return $private.id;
				};

				this.getChildId = function ($index) {
					return [this.getId(), "[", $index, "]"].join("");
				};

				this.getDivId = function ($index) {
					return ["div", this.getChildId($index)].join("");
				};

				this.getButtonId = function () {
					return $private.chkall_id;
				};

				this.getJSCall = function ($funcName) {
					return hendl.JComponent.getJSCall(this, $funcName);
				};

				this.options = function ($index) {

					if (hendl.php.isset($index)) {
						return hendl.php.is_object($private.options[$index], null);
					}

					return $private.options;
				};

				this.html = function () {

					var $html = [];

					$html.push('<table style="width:100%; border-collapse:collapse; border:1px solid #A0A0A0;">');

					if ($private.optionSelectAll) {
						$html.push('<tr>');
						$html.push(hendl.String.format('<td style="padding-left:5px; border-bottom:1px solid #A0A0A0;"><div id="{0}"></div></td>', $private.chkall.getDivId()));
						$html.push('</tr>');
					}

					for (var $index in $private.options) {
						$html.push('<tr>');
						$html.push(hendl.String.format('<td style="padding-left:5px;"><div id="{0}"></div></td>', this.getDivId($index)));
						$html.push('</tr>');
					}

					$html.push('</table>');

					return $html.join("");
				};

				this.create = function ($div, $function) {
					return hendl.JComponent.create(this, $div, [this, function () {

						$private.chkall.create($private.chkall.getDivId());

						for (var $index in $private.options) {
							$private.options[$index].create(this.getDivId($index));
						}

						if (hendl.php.is_function($function)) {
							hendl.php.call_user_array([this, $function], []);
						}

					}]);
				};

				this.anySelected = function () {

					var $ok = false;

					for (var $index in $private.options) {

						if ($private.options[$index].checked()) {
							$ok = true;
							break;
						}

					}

					return $ok;
				};

				this.enableAll = function ($bool) {

					if ($private.optionSelectAll) {
						$private.chkall.enabled($bool);
					}

					for (var $index in $private.options) {
						$private.options[$index].enabled($bool);
					}

				};

				this.selectAll = function ($bool) {

					for (var $index in $private.options) {
						$private.options[$index].checked($bool);
					}

				};

				this.selectedIndex = function ($search) {

					for (var $index in $private.options) {

						if ($search == $private.options[$index].index()) {
							$private.options[$index].checked(true);
						}

					}

				};

				this.selectedValue = function ($search) {

					for (var $index in $private.options) {

						if ($search == $private.options[$index].value()) {
							$private.options[$index].checked(true);
						}

					}

				};

				this.selectedText = function ($search) {

					for (var $index in $private.options) {

						if ($search == $private.options[$index].text()) {
							$private.options[$index].checked(true);
						}

					}

				};

				this.__construct($);
				hendl.JComponent.add(this);

				return 0;
			}

			return new hendl.JCheckBoxGroup($, true);
		}
		else if ($type == "string") {
			return hendl.JComponent.get($);
		}

		return null;
	},
	"JProgressBar" : function JProgressBar($) {

		var $private = {};
		var $type    = hendl.php.gettype($);

		if ($type == "object") {

			if (arguments[1] === true) {

				this.__construct = function ($) {

					$private.id         = $.id;
					$private.message_id = $.id + "_msg";
					$private.count      = hendl.php.is_int($.count, 1);
					$private.color      = hendl.php.is_string($.color, "");
					$private.message    = hendl.php.is_string($.message, "");

					if ($private.color == "success") {
						$private.color = "#33b77e";
					}
					else if ($private.color == "failed" || $private.color == "error") {
						$private.color = "#b73333";
					}
					else {
						$private.color = "";
					}

				};

				this.getId = function () {
					return $private.id;
				};

				this.collection = function () {

					var $collection = {};

					$collection.main    = jQuery(("#" + $private.id));
					$collection.message = jQuery(("#" + $private.message_id));
					$collection.child   = jQuery((".progress-bar." + $private.count));

					return $collection;
				};

				this.create = function ($div, $function) {
					return hendl.JComponent.create(this, $div, [this, function () {

						this.init();

						if (hendl.php.is_function($function)) {
							hendl.php.call_user_func_array([this, $function], []);
						}

					}], []);
				};

				this.html = function () {

					var $html = [];
					var $bg   = $private.color ? hendl.String.format("background-color: %s !important", $private.color) : "";

					$html.push(hendl.String.format('<div id="%s" class="progress bloco-progresso" style="width:100%; display:none;">', $private.id));
					$html.push('<div class="progress" style="background:#E0E0E0; position:relative;">');
					$html.push(hendl.String.format('<div id="%s" style="position:absolute; width:100%; left:0px; top:0px; text-align:center;"></div>', $private.message_id));
					$html.push(hendl.String.format('<div class="progress-bar %s progress-bar active" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:0%; position:absolute; left:0px; top:0px; %s">', $private.count, $bg));
					$html.push('</div></div></div>');

					return $html.join("");
				};

				this.init = function () {

					var $collection = this.collection();

					$collection.child.css('width', "0%");
					$collection.child.html("");
					$collection.message.html("");
					$collection.main.hide();
				};

				this.update = function () {

					var $num, $collection, $perc, $qt, $total, $message;

					$num        = arguments.length;
					$collection = this.collection();

					if ($num == 1 || $num == 2) {

						if ($num == 2) {

							$qt    = hendl.php.is_int(arguments[0], 1);
							$total = hendl.php.is_int(arguments[1], 0);
		    				$perc  = parseInt((100 * $qt / $total));
		    				$perc  = !isNaN($perc) ? ($perc <= 100 ? $perc : 100) : 0;
		    				$perc  = $perc + "%";

		    				if ($private.message) {
		    					$message = hendl.String.format(($private.message + ": (%s de %s) - %s"), $qt, $total, $perc);
		    				}
		    				else {
		    					$message = hendl.String.format("(%s de %s) - %s", $qt, $total, $perc);
		    				}

						}
						else {

							$perc = hendl.php.is_string(arguments[0], "");

							if ($private.message) {
		    					$message = hendl.String.format(($private.message + ": (%s)"), $perc);
		    				}
							else {
								$message = $perc;
							}

						}

						$collection.main.show();
						$collection.child.css('width', $perc);
						$collection.child.html($message);
						$collection.message.html($message);
					}

				};

				this.__construct($);
				hendl.JComponent.add(this);

				return 0;
			}

			return new hendl.JProgressBar($, true);
		}
		else if ($type == "string") {
			return hendl.JComponent.get($);
		}

		return null;
	},
	"JAlert" : {
		"create" : function ($id) {
			this.id = $id;
			document.write(this.html());
		},
		"getId" : function () {
			return this.id;
		},
		"collection" : function () {
			return {
				"div" : jQuery(("#" + this.id))
			};
		},
		"html" : function () {
			return hendl.String.format('<div id="%s"></div>', this.id);
		},
		"hide" : function () {
			var $div = this.collection().div;
			$div.html("");
			$div.hide();
		},
		"show" : function ($message, $info) {
			
			var $valid, $div = this.collection().div;

			$message = hendl.php.is_array($message) ? $message.join("<br />") : $message;
			$valid   = "success|warning|danger|info";
			$info    = $info && $valid.indexOf($info) != -1 ? $info : "info";

			$div.show();
			$div.html(hendl.String.format('<div class="alert alert-%s" ondblclick="this.style.display = \'none\';">%s</div>', $info, $message));
		},
		"success" : function ($message) {
			this.show($message, "success");
		},
		"warning" : function ($message) {
			this.show($message, "warning");
		},
		"danger" : function ($message) {
			this.show($message, "danger");
		},
		"info" : function ($message) {
			this.show($message, "info");
		}
	}
};
