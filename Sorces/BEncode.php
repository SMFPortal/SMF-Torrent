<?php
/**********************************************************************************
* BEncode.php                                                                     *
***********************************************************************************
* SMF Torrent                                                                     *
* =============================================================================== *
* Software Version:           SMF Torrent 0.1                                     *
* Software by:                WasdMan  (http://smf-portal.hu)                     *
* Copyright 2013 by:          WasdMan  (http://smf-portal.hu)                     *
* Support, News, Updates at:  http://smf-portal.hu                                *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

class BEncode
{
  public $encoded = '';

	// Encodes strings, integers and empty dictionaries.
	// $unstrip is set to true when decoding dictionary keys

	function encodeEntry($entry, $unstrip = false)
	{
		if (is_bool($entry))
		{
			$this->encoded .= 'de';

			return;
		}

		if (is_int($entry) || is_float($entry))
		{
			$this->encoded .= "i{$entry}e";
			return;
		}

		if ($unstrip)
			$entry = stripslashes($entry);

		$this->encoded .= strlen($entry) . ":$entry";
	}

	// Encodes lists
	function encodeList($array)
	{
		$this->encoded .= 'l';

		// The empty list is defined as array();
		if (empty($array))
		{
			$this->encoded .= 'e';
			return;
		}

		foreach ($array as $entry)
			$this->decideEncode($entry);

		$this->encoded .= 'e';
	}

	// Passes lists and dictionaries accordingly, and has encodeEntry handle
	// the strings and integers.
	function decideEncode($entry)
	{
		// List or Dictionary?
		if (is_array($entry))
		{
			if (isset($entry[0]) || empty($entry))
				$this->encodeList($entry);
			else
				$this->encodeDict($entry);
		}
		// Number or string?
		else
			$this->encodeEntry($entry);
	}

	// Encodes dictionaries
	function encodeDict($array)
	{
		$this->encoded  .= 'd';

		if (is_bool($array))
		{
			$this->encoded  .= 'e';
			return;
		}

		// NEED TO SORT!
		ksort($array);

		foreach($array as $left => $right)
		{
			$this->encodeEntry($left, true);
			$this->decideEncode($right);
		}

		$this->encoded .= 'e';
		return;
	}
}

// Use this function in your own code.
function BEncode($array, $gzip = false)
{
	$encoder = new BEncode;
	$encoder->decideEncode($array);

	return $gzip ? gzencode($encoder->encoded, 9, FORCE_GZIP) : $encoder->encoded;
}

class BDecode
{
	function numberdecode($wholefile, $start)
	{
		$ret[0] = 0;
		$offset = $start;

		// Funky handling of negative numbers and zero
		$negative = false;
		if ($wholefile[$offset] == '-')
		{
			$negative = true;
			$offset++;
		}
		elseif ($wholefile[$offset] == '0')
		{
			$offset++;
			if ($negative)
				return array(false);
			if ($wholefile[$offset] == ':' || $wholefile[$offset] == 'e')
			{
				$offset++;
				$ret[0] = 0;
				$ret[1] = $offset;
				return $ret;
			}

			return array(false);
		}

		while (true)
		{
			if ($wholefile[$offset] >= '0' && $wholefile[$offset] <= '9')
			{

				$ret[0] *= 10;
				$ret[0] += ord($wholefile[$offset]) - ord("0");
				$offset++;
			}
			// Tolerate : or e because this is a multiuse function
			elseif ($wholefile[$offset] == 'e' || $wholefile[$offset] == ':')
			{
				$ret[1] = $offset + 1;
				if ($negative)
				{
					if ($ret[0] == 0)
						return array(false);
					$ret[0] = - $ret[0];
				}

				return $ret;
			}
			else
				return array(false);
		}
	}

	function decodeEntry($wholefile, $offset=0)
	{
		if ($wholefile[$offset] == 'd')
			return $this->decodeDict($wholefile, $offset);
		elseif ($wholefile[$offset] == 'l')
			return $this->decodelist($wholefile, $offset);
		elseif ($wholefile[$offset] == "i")
		{
			$offset++;
			return $this->numberdecode($wholefile, $offset);
		}

		// String value: decode number, then grab substring
		$info = $this->numberdecode($wholefile, $offset);
		if ($info[0] === false)
			return array(false);
		$ret[0] = substr($wholefile, $info[1], $info[0]);
		$ret[1] = $info[1]+strlen($ret[0]);

		return $ret;
	}

	function decodeList($wholefile, $start)
	{
		$offset = $start+1;
		$i = 0;
		if ($wholefile[$start] != 'l')
			return array(false);

		$ret = array();
		while (true)
		{
			if ($wholefile[$offset] == 'e')
				break;
			$value = $this->decodeEntry($wholefile, $offset);

			if ($value[0] === false)
				return array(false);

			$ret[$i] = $value[0];
			$offset = $value[1];
			$i ++;
		}

		// The empy list is an empty array. Seems fine.
		$final[0] = $ret;
		$final[1] = $offset+1;
		return $final;
	}

	// Tries to construct an array
	function decodeDict($wholefile, $start = 0)
	{
		$offset = $start;

		if ($wholefile[$offset] == 'l')
			return $this->decodeList($wholefile, $start);
		elseif ($wholefile[$offset] != 'd')
			return false;

		$ret = array();
		$offset++;

		while (true)
		{
			if ($wholefile[$offset] == 'e')
			{
				$offset++;
				break;
			}
			$left = $this->decodeEntry($wholefile, $offset);
			if (!$left[0])
				return false;

			$offset = $left[1];

			if ($wholefile[$offset] == 'd')
			{
				// Recurse
				$value = $this->decodedict($wholefile, $offset);
				if (!$value[0])
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset= $value[1];
				continue;
			}
			elseif ($wholefile[$offset] == 'l')
			{
				$value = $this->decodeList($wholefile, $offset);
				if (!$value[0] && is_bool($value[0]))
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			}
			else
			{
				$value = $this->decodeEntry($wholefile, $offset);
				if ($value[0] === false)
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			}
		}

		if (empty($ret))
			$final[0] = true;
		else
			$final[0] = $ret;

		$final[1] = $offset;
		return $final;
	}
}

// Use this function. eg:  BDecode("d8:announce44:http://www. ... e");
function BDecode($wholefile)
{
	$decoder = new BDecode;
	$return = $decoder->decodeEntry($wholefile);
	return $return[0];
}

?>
