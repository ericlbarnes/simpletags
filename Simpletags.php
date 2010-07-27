<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Simpletags
 *
 * A simple tag parsing library.
 *
 * @package		Simpletags
 * @version		1.0
 * @author		Dan Horrigan <http://dhorrigan.com>
 * @license		Apache License v2.0
 * @copyright	2010 Dan Horrigan
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Simpletags
{
	private $_trigger = 'tag:';
	private $_l_delim = '{';
	private $_r_delim = '}';
	private $_mark = 'k0dj3j4nJHDj22j';
	private $_tag_count = 0;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	array	The custom config
	 * @return	void
	 */
	public function __construct($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->_{$key}))
			{
				$this->_{$key} = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set Delimeters
	 *
	 * Set the delimeters for the tags
	 *
	 * @access	public
	 * @param	string	The left delimeter
	 * @param	string	The right delimeter
	 * @return	object	Returns $this to enable method chaining
	 */
	public function set_delimeters($left, $right)
	{
		$this->_l_delim = $left;
		$this->_r_delim = $right;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Trigger
	 *
	 * Sets the tag trigger to use.  This allows you to only consider tags
	 * that have a trigger:
	 *
	 * {tag:name}{/tag:name}
	 *
	 * @access	public
	 * @param	string	The tag trigger
	 * @return	object	Returns $this to enable method chaining
	 */
	public function set_trigger($trigger)
	{
		$this->_trigger = $trigger;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse
	 *
	 * Parses the content and returns an array with marked content and tags
	 * or the resulting content from calling the callback for each tag.
	 *
	 * @access	public
	 * @param	string	The content to parse
	 * @param	array	The callback for each tag
	 * @return	mixed	Either the tags as array or callback results
	 */
	public function parse($content, $data = array(), $callback = array())
	{
		$orig_content = $content;

		$open_tag_regex = $this->_l_delim.$this->_trigger.'.*?'.$this->_r_delim;

		while (($start = strpos($orig_content, $this->_l_delim.$this->_trigger)) !== FALSE)
		{
			$content = $orig_content;

			if ( ! preg_match('/'.$open_tag_regex.'/i', $content, $tag))
			{
				break;
			}

			// We use these later
			$tag_len = strlen($tag[0]);
			$full_tag = $tag[0];

			// Trim off the left and right delimeters
			$tag = trim($full_tag, $this->_l_delim.$this->_r_delim);

			// Get the segments of the tag
			$segments = preg_replace('/(.*?)\s+.*/', '$1', $tag);

			// Get the attribute string
			$attributes = (preg_match('/\s+.*/', $tag, $args)) ? trim($args[0]) : '';

			// Lets start to create the parsed tag
			$parsed['full_tag']	= $full_tag;
			$parsed['attributes'] = $this->_parse_attributes($attributes);
			$parsed['segments'] = $this->_parse_segments(str_replace($this->_trigger, '', $segments));

			// Set the end tag to search for
			$end_tag = $this->_l_delim.'/'.$segments.$this->_r_delim;

			// Lets trim off the first part of the content
			$content = substr($content, $start + $tag_len);

			// If there is an end tag, get and set the content.
			if (($end = strpos($content, $end_tag)) !== FALSE)
			{
				$parsed['content'] = substr($content, 0, $end);
				$parsed['full_tag'] .= $parsed['content'].$end_tag;
			}
			else
			{
				$parsed['content'] = '';
			}
			$parsed['marker'] = 'marker_'.$this->_tag_count.$this->_mark;

			$orig_content = str_replace($parsed['full_tag'], $parsed['marker'], $orig_content);
			$parsed_tags[] = $parsed;
			$this->_tag_count++;
		}

		// Lets replace all the data tags first
		if ( ! empty($data))
		{
			foreach ($parsed_tags as $key => $tag)
			{
				$found = TRUE;
				$t_data = $data;
				foreach ($tag['segments'] as $segment)
				{
					if ( ! isset($t_data[$segment]))
					{
						$found = FALSE;
						break;
					}
					$t_data = $t_data[$segment];
				}
				if ($found)
				{
					$orig_content = str_replace($tag['marker'], $t_data, $orig_content);
					unset($parsed_tags[$key]);
				}
			}

		}

		// If there is a callback, call it for each tag
		if ( ! empty($callback) AND is_callable($callback))
		{
			// Just return the content if there were no tags
			if( ! empty($parsed_tags))
			{
				foreach ($parsed_tags as $tag)
				{
					$orig_content = str_replace($tag['marker'], call_user_func($callback, $tag), $orig_content);
				}
			}

			return $orig_content;
		}
		else
		{
			return array('content' => $orig_content, 'tags' => $parsed_tags);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Attributes
	 *
	 * Parses the string of attributes into a keyed array
	 *
	 * @param	string	The string of attributes
	 * @return	array	The keyed array of attributes
	 */
	private function _parse_attributes($attributes)
	{
		preg_match_all('/(.*?)=([\'"])(.*?)\2/', $attributes, $parts);

		// The tag has no attrbutes
		if (empty($parts[0]))
		{
			return array();
		}

		// The tag has attributes, so lets parse them
		else
		{
			$attr = array();
			for ($i = 0; $i < count($parts[1]); $i++)
			{
				$attr[trim($parts[1][$i])] = $parts[3][$i];
			}
		}

		return $attr;
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Segments
	 *
	 * Parses the string of segments into an array
	 *
	 * @param	string	The string of segments
	 * @return	array	The array of segments
	 */
	private function _parse_segments($segments)
	{
		$segments = explode(':', $segments);
		return $segments;
	}
}

/* End of file Simpletags.php */