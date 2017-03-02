<?php

namespace Weblebby\GameConnect\Minecraft;

class Color
{
	const REGEX = '/(?:§|&amp;)([0-9a-fklmnor])/i';
	const START_TAG  = '<span style="%s">';
	const CLOSE_TAG  = '</span>';
	const CSS_COLOR  = 'color: #';
	const EMPTY_TAGS = '/<[^\/>]*>([\s]?)*<\/[^>]*>/';
	const LINE_BREAK = '<br />';

	protected static $colors = array(
		'0' => '000000', # Black
		'1' => '0000AA', # Dark Blue
		'2' => '00AA00', # Dark Green
		'3' => '00AAAA', # Dark Aqua
		'4' => 'AA0000', # Dark Red
		'5' => 'AA00AA', # Dark Purple
		'6' => 'FFAA00', # Gold
		'7' => 'AAAAAA', # Gray
		'8' => '555555', # Dark Gray
		'9' => '5555FF', # Blue
		'a' => '55FF55', # Green
		'b' => '55FFFF', # Aqua
		'c' => 'FF5555', # Red
		'd' => 'FF55FF', # Light Purple
		'e' => 'FFFF55', # Yellow
		'f' => 'FFFFFF'  # White
	);

	protected static $formatting = array(
		'k' => '',                               # Obfuscated
		'l' => 'font-weight: bold;',             # Bold
		'm' => 'text-decoration: line-through;', # Strikethrough
		'n' => 'text-decoration: underline;',    # Underline
		'o' => 'font-style: italic;',            # Italic
		'r' => ''                                # Reset
	);

	protected static function utf8($text)
	{
		if (mb_detect_encoding($text) != 'UTF-8') {
			$text = utf8_encode($text);
		}

		return $text;
	}

	public static function clean($text)
	{
		$text = self::utf8($text);
		$text = htmlspecialchars($text, ENT_QUOTES);

		return preg_replace(self::REGEX, '', $text);
	}

	public static function motd($text, $sign = '\u00A7')
	{
		$text = self::utf8($text);
		$text = str_replace("&", "&amp;", $text);
		$text = preg_replace(self::REGEX, $sign . '${1}', $text);
		$text = str_replace("\n", '\n', $text);
		$text = str_replace("&amp;", "&", $text);

		return $text;
	}

	public static function html($text, $line_break_element = false)
	{
		$text = self::utf8($text);
		$text = htmlspecialchars($text, ENT_QUOTES);

		preg_match_all(self::REGEX, $text, $offsets);

		$colors      = $offsets[0];
		$color_codes = $offsets[1];

		if ( empty($colors) === true ) {
			return $text;
		}

		$open_tags = 0;

		foreach ($colors as $index => $color) {
			$color_code = strtolower($color_codes[$index]);

			if ( isset(self::$colors[$color_code]) ) {
				$html = sprintf(self::START_TAG, self::CSS_COLOR . self::$colors[$color_code]);

				if ( $open_tags != 0 ) {
					$html = str_repeat(self::CLOSE_TAG, $open_tags) . $html;
					$open_tags = 0;
				}

				$open_tags++;
			} else {
				switch ($color_code) {
					case 'r':
						$html = '';

						if ($open_tags != 0) {
							$html = str_repeat(self::CLOSE_TAG, $open_tags);
							$open_tags = 0;
						}
						break;
					case 'k':
						$html = '';
						break;
					default:
						$html = sprintf(self::START_TAG, self::$formatting[$color_code]);
						$open_tags++;
						break;
				}
			}

			$text = preg_replace('/' . $color . '/', $html, $text, 1);
		}

		if ($open_tags != 0)
			$text = $text . str_repeat(self::CLOSE_TAG, $open_tags);

		if ($line_break_element) {
			$text = str_replace("\n", self::LINE_BREAK, $text);
			$text = str_replace('\n', self::LINE_BREAK, $text);
		}
		
		return preg_replace(self::EMPTY_TAGS, '', $text);
	}
}