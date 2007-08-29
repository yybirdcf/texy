<?php

/**
 * This file is part of the Texy! formatter (http://texy.info/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    GNU GENERAL PUBLIC LICENSE version 2 or 3
 * @version    $Revision$ $Date$
 * @category   Text
 * @package    Texy
 */



/**
 * HTML helper
 *
 * usage:
 *       $anchor = TexyHtml::el('a')->href($link)->setText('Texy');
 *       $el->class = 'myclass';
 *
 *       echo $el->startTag(), $el->endTag();
 *
 * @property mixed element's attributes
 */
class TexyHtml extends TexyBase
{
    /** @var string  element's name */
    private $name;

    /** @var bool  is element empty? */
    private $isEmpty;

    /** @var array  element's attributes */
    public $attrs = array();

    /**
     * @var mixed  element's content
     *   array of TexyHtml - child nodes
     *   string - content as string (text-node)
     */
    public $children;

    /** @var bool  use XHTML syntax? */
    public static $xhtml = TRUE;

    /** @var array  empty elements */
    public static $emptyEl = array('img'=>1,'hr'=>1,'br'=>1,'input'=>1,'meta'=>1,'area'=>1,
        'base'=>1,'col'=>1,'link'=>1,'param'=>1,'basefont'=>1,'frame'=>1,'isindex'=>1,'wbr'=>1,'embed'=>1);

    /** @var array  %inline; elements; replaced elements + br have value '1' */
    public static $inlineEl = array('ins'=>0,'del'=>0,'tt'=>0,'i'=>0,'b'=>0,'big'=>0,'small'=>0,'em'=>0,
        'strong'=>0,'dfn'=>0,'code'=>0,'samp'=>0,'kbd'=>0,'var'=>0,'cite'=>0,'abbr'=>0,'acronym'=>0,
        'sub'=>0,'sup'=>0,'q'=>0,'span'=>0,'bdo'=>0,'a'=>0,'object'=>1,'img'=>1,'br'=>1,'script'=>1,
        'map'=>0,'input'=>1,'select'=>1,'textarea'=>1,'label'=>0,'button'=>1,
        'u'=>0,'s'=>0,'strike'=>0,'font'=>0,'applet'=>1,'basefont'=>0, // transitional
        'embed'=>1,'wbr'=>0,'nobr'=>0,'canvas'=>1, // proprietary
    );



    /**
     * Static factory
     * @param string element name (or NULL)
     * @param array element's attributes
     * @return TexyHtml
     */
    public static function el($name = NULL, $attrs = NULL)
    {
        $el = new self;

        if ($name !== NULL) {
            $el->setName($name);
        }

        if ($attrs !== NULL) {
            if (!is_array($attrs)) {
                throw new TexyException('Attributes must be array');
            }

            $el->attrs = $attrs;
        }

        return $el;
    }



    /**
     * Static factory for textual element
     * @param string
     * @return TexyHtml
     */
    public static function text($text)
    {
        $el = new self;
        $el->setText($text);
        return $el;
    }



    /**
     * Changes element's name
     * @param string
     * @return TexyHtml  itself
     */
    final public function setName($name)
    {
        if ($name !== NULL && !is_string($name)) {
            throw new TexyException('Name must be string or NULL');
        }

        $this->name = $name;
        $this->isEmpty = isset(self::$emptyEl[$name]);
        return $this;
    }



    /**
     * Returns element's name
     * @return string
     */
    final public function getName()
    {
        return $this->name;
    }



    /**
     * Is element empty?
     * @param optional setter
     * @return bool
     */
    final public function isEmpty($value = NULL)
    {
        if (is_bool($value)) {
            $this->isEmpty = $value;
        }

        return $this->isEmpty;
    }



    /**
     * Sets element's textual content
     * @param string
     * @return TexyHtml  itself
     */
    final public function setText($text)
    {
        if ($text === NULL) {
            $text = '';
        } elseif (!is_scalar($text)) {
            throw new TexyException('Content must be scalar');
        }

        $this->children = $text;
        return $this;
    }



    /**
     * Gets element's textual content
     * @return string
     */
    final public function getText()
    {
        if (is_array($this->children)) {
            return FALSE;
        }

        return $this->children;
    }



    /**
     * Adds new element's child
     * @param TexyHtml object
     * @return TexyHtml  itself
     */
    final public function addChild(TexyHtml $child)
    {
        $this->children[] = $child;
        return $this;
    }



    /**
     * Returns child node
     * @param mixed index
     * @return TexyHtml
     */
    final public function getChild($index)
    {
        if (isset($this->children[$index])) {
            return $this->children[$index];
        }

        return NULL;
    }



    /**
     * Adds and creates new TexyHtml child
     * @param string  elements's name
     * @param string optional textual content
     * @return TexyHtml
     */
    final public function add($name, $text = NULL)
    {
        $child = new self;
        $child->setName($name);
        if ($text !== NULL) {
            $child->setText($text);
        }
        return $this->children[] = $child;
    }



    /**
     * Overloaded setter for element's attribute
     * @param string    property name
     * @param mixed     property value
     * @return void
     */
    final public function __set($name, $value)
    {
        $this->attrs[$name] = $value;
    }



    /**
     * Overloaded getter for element's attribute
     * @param string    property name
     * @return mixed    property value
     */
    final public function &__get($name)
    {
        return $this->attrs[$name];
    }



    /**
     * Overloaded setter for element's attribute
     * @param string attribute name
     * @param array value
     * @return TexyHtml  itself
     */
/*
    final public function __call($m, $args)
    {
        $this->attrs[$m] = $args[0];
        return $this;
    }
*/


    /**
     * Special setter for element's attribute
     * @param string path
     * @param array query
     * @return TexyHtml  itself
     */
    final public function href($path, $params = NULL)
    {
        if ($params) {
            $query = http_build_query($params, NULL, '&');
            if ($query !== '') $path .= '?' . $query;
        }
        $this->attrs['href'] = $path;
        return $this;
    }



    /**
     * Renders element's start tag, content and end tag to internal string representation
     * @param Texy
     * @return string
     */
    final public function toString(Texy $texy)
    {
        $ct = $this->getContentType();
        $s = $texy->protect($this->startTag(), $ct);

        // empty elements are finished now
        if ($this->isEmpty) {
            return $s;
        }

        // add content
        if (is_array($this->children)) {
            foreach ($this->children as $value)
                $s .= $value->toString($texy);

        } else {
            $s .= $this->children;
        }

        // add end tag
        return $s . $texy->protect($this->endTag(), $ct);
    }



    /**
     * Renders to final HTML
     * @param Texy
     * @return string
     */
    final public function toHtml(Texy $texy)
    {
        return $texy->stringToHtml($this->toString($texy));
    }



    /**
     * Renders to final text
     * @param Texy
     * @return string
     */
    final public function toText(Texy $texy)
    {
        return $texy->stringToText($this->toString($texy));
    }



    /**
     * Returns element's start tag
     * @return string
     */
    public function startTag()
    {
        if (!$this->name) {
            return '';
        }

        $s = '<' . $this->name;

        if (is_array($this->attrs)) {
            foreach ($this->attrs as $key => $value)
            {
                // skip NULLs and false boolean attributes
                if ($value === NULL || $value === FALSE) continue;

                // true boolean attribute
                if ($value === TRUE) {
                    // in XHTML must use unminimized form
                    if (self::$xhtml) $s .= ' ' . $key . '="' . $key . '"';
                    // in HTML should use minimized form
                    else $s .= ' ' . $key;
                    continue;

                } elseif (is_array($value)) {

                    // prepare into temporary array
                    $tmp = NULL;
                    foreach ($value as $k => $v) {
                        // skip NULLs & empty string; composite 'style' vs. 'others'
                        if ($v == NULL) continue;

                        if (is_string($k)) $tmp[] = $k . ':' . $v;
                        else $tmp[] = $v;
                    }

                    if (!$tmp) continue;
                    $value = implode($key === 'style' ? ';' : ' ', $tmp);

                } elseif ($key === 'href' && substr($value, 0, 7) === 'mailto:') {
                    // email-obfuscate hack
                    $tmp = '';
                    for ($i = 0; $i<strlen($value); $i++) $tmp .= '&#' . ord($value[$i]) . ';'; // WARNING: no utf support
                    $s .= ' href="' . $tmp . '"';
                    continue;
                }

                // add new attribute
                $value = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $value);
                $s .= ' ' . $key . '="' . Texy::freezeSpaces($value) . '"';
            }
        }

        // finish start tag
        if (self::$xhtml && $this->isEmpty) return $s . ' />';
        return $s . '>';
    }



    /**
     * Returns element's end tag
     * @return string
     */
    public function endTag()
    {
        if ($this->name && !$this->isEmpty)
            return '</' . $this->name . '>';
        return '';
    }



    /**
     * Is element textual node?
     * @return bool
     */
    final public function isTextual()
    {
        return !$this->isEmpty && is_scalar($this->children);
    }



    /**
     * Clones all children too
     */
    final public function __clone()
    {
        if (is_array($this->children)) {
            foreach ($this->children as $key => $value)
                $this->children[$key] = clone $value;
        }
    }



    /**
     * @return int
     */
    final public function getContentType()
    {
        if (!isset(self::$inlineEl[$this->name])) return Texy::CONTENT_BLOCK;

        return self::$inlineEl[$this->name] ? Texy::CONTENT_REPLACED : Texy::CONTENT_MARKUP;
    }



    /**
     * Parses text as single line
     * @param Texy
     * @param string
     * @return void
     */
    final public function parseLine($texy, $s)
    {
        // TODO!
        // special escape sequences
        $s = str_replace(array('\)', '\*'), array('&#x29;', '&#x2A;'), $s);

        $parser = new TexyLineParser($texy);
        $this->children = $parser->parse($s);
    }



    /**
     * Parses text as block
     * @param Texy
     * @param string
     * @param int
     * @return void
     */
    final public function parseBlock($texy, $s, $level = 0)
    {
        $parser = new TexyBlockParser($texy, $level);
        $this->children = $parser->parse($s);
    }

}
