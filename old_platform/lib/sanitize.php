<?php
/*
 * Copyright (c) 2002,2003 Free Software Foundation
* developed under the custody of the
* Open Web Application Security Project
* (http://www.owasp.org)
*
* This file is part of the PHP Filters.
* PHP Filters is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* PHP Filters is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* If you are not able to view the LICENSE, which should
* always be possible within a valid and working PHP Filters release,
* please write to the Free Software Foundation, Inc.,
* 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
* to get a copy of the GNU General Public License or to report a
* possible license violation.
*/
///////////////////////////////////////
// sanitize.inc.php
// Sanitization functions for PHP
// by: Gavin Zuchlinski, Jamie Pratt, Hokkaido
// webpage: http://libox.net
// Last modified: December 21, 2003
//
// Many thanks to those on the webappsec list for helping me improve these functions
///////////////////////////////////////
// Function list:
// sanitize_paranoid_string($string) -- input string, returns string stripped of all non
//           alphanumeric
// sanitize_system_string($string) -- input string, returns string stripped of special
//           characters
// sanitize_sql_string($string) -- input string, returns string with slashed out quotes
// sanitize_html_string($string) -- input string, returns string with html replacements
//           for special characters
// sanitize_int($integer) -- input integer, returns ONLY the integer (no extraneous
		//           characters
		// sanitize_float($float) -- input float, returns ONLY the float (no extraneous
				//           characters)
		// sanitize($input, $flags) -- input any variable, performs sanitization
				//           functions specified in flags. flags can be bitwise
				//           combination of PARANOID, SQL, SYSTEM, HTML, INT, FLOAT, LDAP,
				//           UTF8
				//
		//
		///////////////////////////////////////
		//
		// 20031121 jp - added defines for magic_quotes and register_globals, added ; to replacements
		//               in sanitize_sql_string() function, created rudimentary testing pages
		// 20031221 gz - added nice_addslashes and changed sanitize_sql_string to use it
		//
		/////////////////////////////////////////

define("PARANOID", 1);
define("SQL", 2);
define("SYSTEM", 4);
define("HTML", 8);
define("INT", 16);
define("FLOAT", 32);
define("LDAP", 64);
define("UTF8", 128);

// get register_globals ini setting - jp
$register_globals = (bool) ini_get('register_gobals');
if ($register_globals == TRUE) {
	define("REGISTER_GLOBALS", 1);
} else { define("REGISTER_GLOBALS", 0);
}

// get magic_quotes_gpc ini setting - jp
$magic_quotes = (bool) ini_get('magic_quotes_gpc');
if ($magic_quotes == TRUE) {
	define("MAGIC_QUOTES", 1);
} else { define("MAGIC_QUOTES", 0);
}

function RemoveXSS($val) {
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// &#x0040 @ search for the hex values
		$val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
		// &#00064 @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}

function sanitize_tag($string, $min='', $max='')
{
	$string = preg_replace("/[^a-zA-Z0-9\-\_\ ]/", "", $string);
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return $string;
}

function sanitize_date($string, $min='', $max='')
{
	$string = preg_replace("/[^0-9\-\:\ ]/", "", $string);
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return $string;
}

function charset_decode_utf_8 ($string) {
	/* Only do the slow convert if there are 8-bit characters */
	/* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
	if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
		return $string;

	// decode three byte unicode characters
	$string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
			"'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",
			$string);

	// decode two byte unicode characters
	$string = preg_replace("/([\300-\337])([\200-\277])/e",
			"'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
			$string);

	return $string;
}

// addslashes wrapper to check for gpc_magic_quotes - gz
function nice_addslashes($string)
{
	// if magic quotes is on the string is already quoted, just return it
	if(MAGIC_QUOTES)
		return $string;
	else
		return addslashes($string);
}

// internal function for utf8 decoding
// thanks to Hokkaido for noticing that PHP's utf8_decode function is a little
// screwy, and to jamie for the code
function my_utf8_decode($string)
{
	return strtr($string,
			"???????��������������������������������������������������������������",
			"SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy");
}

// paranoid sanitization -- only let the alphanumeric set through
function sanitize_paranoid_string($string, $min='', $max='')
{
	//$string = preg_replace("/[^a-zA-Z0-9\/_\.\*\-\:\$]/", "", $string);
	$pattern = '/(;|\||`|>|<|&|^|"|'."\n|\r|'".'|{|}|[|]|\)|\()/i';
	$string = preg_replace($pattern, "", $string);
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return $string;
}

// sanitize a string in prep for passing a single argument to system() (or similar)
function sanitize_system_string($string, $min='', $max='')
{
	$pattern = '/(;|\||`|>|<|&|^|"|'."\n|\r|'".'|{|}|[|]|\)|\()/i'; // no piping, passing possible environment variables ($),
	// seperate commands, nested execution, file redirection,
	// background processing, special commands (backspace, etc.), quotes
	// newlines, or some other special characters
	$string = preg_replace($pattern, '', $string);
	$string = '"'.preg_replace('/\$/', '\\\$', $string).'"'; //make sure this is only interpretted as ONE argument
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return $string;
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
function sanitize_sql_string($string, $min='', $max='')
{
	$string = nice_addslashes($string); //gz
	$pattern = "/;/"; // jp
	$replacement = "";
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return preg_replace($pattern, $replacement, $string);
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
function sanitize_ldap_string($string, $min='', $max='')
{
	$pattern = '/(\)|\(|\||&)/';
	$len = strlen($string);
	if((($min != '') && ($len < $min)) || (($max != '') && ($len > $max)))
		return FALSE;
	return preg_replace($pattern, '', $string);
}


// sanitize a string for HTML (make sure nothing gets interpretted!)
function sanitize_html_string($string)
{
	$pattern[0] = '/\&/';
	$pattern[1] = '/</';
	$pattern[2] = "/>/";
	$pattern[3] = '/\n/';
	$pattern[4] = '/"/';
	$pattern[5] = "/'/";
	$pattern[6] = "/%/";
	$pattern[7] = '/\(/';
	$pattern[8] = '/\)/';
	$pattern[9] = '/\+/';
	$pattern[10] = '/-/';
	$replacement[0] = '&amp;';
	$replacement[1] = '&lt;';
	$replacement[2] = '&gt;';
	$replacement[3] = '<br>';
	$replacement[4] = '&quot;';
	$replacement[5] = '&#39;';
	$replacement[6] = '&#37;';
	$replacement[7] = '&#40;';
	$replacement[8] = '&#41;';
	$replacement[9] = '&#43;';
	$replacement[10] = '&#45;';
	return preg_replace($pattern, $replacement, $string);
}

// make int int!
function sanitize_int($integer, $min='', $max='')
{
	$int = intval($integer);
	if((($min != '') && ($int < $min)) || (($max != '') && ($int > $max)))
		return FALSE;
	return $int;
}

// make float float!
function sanitize_float($float, $min='', $max='')
{
	$float = floatval($float);
	if((($min != '') && ($float < $min)) || (($max != '') && ($float > $max)))
		return FALSE;
	return $float;
}

// glue together all the other functions
function sanitize($input, $flags, $min='', $max='')
{
	if($flags & UTF8) $input = my_utf8_decode($input);
	if($flags & PARANOID) $input = sanitize_paranoid_string($input, $min, $max);
	if($flags & INT) $input = sanitize_int($input, $min, $max);
	if($flags & FLOAT) $input = sanitize_float($input, $min, $max);
	if($flags & HTML) $input = sanitize_html_string($input, $min, $max);
	if($flags & SQL) $input = sanitize_sql_string($input, $min, $max);
	if($flags & LDAP) $input = sanitize_ldap_string($input, $min, $max);
	if($flags & SYSTEM) $input = sanitize_system_string($input, $min, $max);
	return $input;
}

function check_paranoid_string($input, $min='', $max='')
{
	if($input != sanitize_paranoid_string($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_int($input, $min='', $max='')
{
	if($input != sanitize_int($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_float($input, $min='', $max='')
{
	if($input != sanitize_float($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_html_string($input, $min='', $max='')
{
	if($input != sanitize_html_string($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_sql_string($input, $min='', $max='')
{
	if($input != sanitize_sql_string($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_ldap_string($input, $min='', $max='')
{
	if($input != sanitize_string($input, $min, $max))
		return FALSE;
	return TRUE;
}

function check_system_string($input, $min='', $max='')
{
	if($input != sanitize_system_string($input, $min, $max, TRUE))
		return FALSE;
	return TRUE;
}

// glue together all the other functions
function check($input, $flags, $min='', $max='')
{
	$oldput = $input;
	if($flags & UTF8) $input = my_utf8_decode($input);
	if($flags & PARANOID) $input = sanitize_paranoid_string($input, $min, $max);
	if($flags & INT) $input = sanitize_int($input, $min, $max);
	if($flags & FLOAT) $input = sanitize_float($input, $min, $max);
	if($flags & HTML) $input = sanitize_html_string($input, $min, $max);
	if($flags & SQL) $input = sanitize_sql_string($input, $min, $max);
	if($flags & LDAP) $input = sanitize_ldap_string($input, $min, $max);
	if($flags & SYSTEM) $input = sanitize_system_string($input, $min, $max, TRUE);
	if($input != $oldput)
		return FALSE;
	return TRUE;
}
?>
