<?php


class LogMessage {
	
	private $datetime;
	private $tags;
	private $level;
	private $text;
	private $fields;
	
	public function __construct($tags, $level, $text, $fields = array()) {
		if (!is_array($tags))
			$tags = array($tags);
		$this->datetime = date("Y-m-d H:i:s");
		$this->tags = $tags;
		$this->level = $level;
		$this->text = $text;
		$this->fields = $fields;
	}
	
	public function datetime() {
		return $this->datetime;
	}
	
	public function tags() {
		return $this->tags;
	}
	
	public function level() {
		return $this->level;
	}
	
	public function text() {
		return $this->text;
	}
	
	public function fields() {
		return $this->fields;
	}
	
	public function asRecord() {
		return array(
			"Text" => $this->text(),
			"Date" => $this->datetime(),
			"Level" => $this->level(),
			"Tags" => $this->tags(),
			"Fields" => $this->fields()
		);
	}
	
	public function toJSON() {
		return json_encode($this->asRecord());
	}
	
	public function formatOneLine() {
		$fieldval = array_values($this->fields());
		$f = count($fieldval) > 0 ? join(" | ", $fieldval) . " | " : ""; 
		return sprintf("%1\$s | %2\$s | %3\$s | %5\$s%4\$s" . PHP_EOL,
		               $this->datetime(),
		               $this->level(),
		               join(",", $this->tags()),
		               addcslashes($this->text(), "\t\n\r"),
					   $f);
	}
		
	public function formatMultiLine() {
		$f = "";
		foreach ($this->fields() as $key=>$value)
			$f .= $key . ": " . $value . PHP_EOL;
		return sprintf("Message: %s" . PHP_EOL .
					   "Date: %s" . PHP_EOL .
		               "Level: %s" . PHP_EOL .
		               "Tags: %s" . PHP_EOL .
		               $f,
				       $this->text(),
		               $this->datetime(),
		               $this->level(),
		               join(",", $this->tags()));
	}

}
