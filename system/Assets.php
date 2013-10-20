<?php

class CSSMin
{
    static function mini($str){
        $re1 = <<<'EOS'
(?sx)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # comments
  /\* (?> .*? \*/ )
EOS;
        $re2 = <<<'EOS'
(?six)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # ; before } (and the spaces after it while we're here)
  \s*+ ; \s*+ ( } ) \s*+
|
  # all spaces around meta chars/operators
  \s*+ ( [*$~^|]?+= | [{};,>~+] | !important\b ) \s*+
|
  # spaces right of ( [ :
  ( [[(:] ) \s++
|
  # spaces left of ) ]
  \s++ ( [])] )
|
  # spaces left (and right) of :
  \s++ ( : ) \s*+
  # but not in selectors: not followed by a {
  (?!
    (?>
      [^{}"']++
    | "(?:[^"\\]++|\\.)*+"
    | '(?:[^'\\]++|\\.)*+'
    )*+
    {
  )
|
  # spaces at beginning/end of string
  ^ \s++ | \s++ \z
|
  # double spaces to single
  (\s)\s+
EOS;
        $str = preg_replace("%$re1%", '$1', $str);
        return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $str);
    }
}

class HTMLMin
{

  static protected function domNodeDiscardable(DOMNode $node)
  {
    return ($node->tagName == 'meta' && strtolower($node->getAttribute('http-equiv')) == 'content-type');
  }

  static protected function getNextSiblingOfTypeDOMElement(DOMNode $node)
  {
    do
    {
      $node = $node->nextSibling;
    }
    while (!($node === null || $node instanceof DOMElement));
    return $node;
  }

  static protected function domAttrDiscardable(DOMAttr $attr)
  {
    #(!in_array($attr->ownerElement->tagName, array('input', 'select', 'button', 'textarea')) && $attr->name == 'name' && $attr->ownerElement->getAttribute('id') == $attr->value) # elements with pairing name/id’s
    return ($attr->ownerElement->tagName == 'form' && $attr->name == 'method' && strtolower($attr->value) == 'get') # <form method="get"> is default
        || ($attr->ownerElement->tagName == 'style' && $attr->name == 'media' && strtolower($attr->value) == 'all') # <style media="all"> is implicit default
        || ($attr->ownerElement->tagName == 'input' && $attr->name == 'type' && strtolower($attr->value) == 'text') # <input type="text"> is default
         ;
  }

  #http://www.whatwg.org/specs/web-apps/current-work/multipage/syntax.html#void-elements
  private static $void_elements = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');
  private static $optional_end_tags = array('html', 'head', 'body');

  static protected function domNodeClosingTagOmittable(DOMNode $node)
  {
    # TODO: Exakt die Spezifikation implementieren, indem nachfolgende Elemente
    # mitbetrachtet werden
    # http://www.whatwg.org/specs/web-apps/current-work/multipage/syntax.html#optional-tags

    $tag_name = $node->tagName;
    #$nextSibling = self::getNextSiblingOfTypeDOMElement($node);
    $nextSibling = $node->nextSibling;
    return in_array($tag_name, self::$void_elements)
        || in_array($tag_name, self::$optional_end_tags)
        || ($tag_name == 'li' && ($nextSibling === null || ($nextSibling instanceof DOMElement && $nextSibling->tagName == $tag_name)))
        || ($tag_name == 'p' && (($nextSibling === null && ($node->parentNode !== null && $node->parentNode->tagName != 'a'))
                                 || ($nextSibling instanceof DOMElement && in_array($nextSibling->tagName, array('address', 'article', 'aside', 'blockquote', 'dir',
                                                                          'div', 'dl', 'fieldset', 'footer', 'form', 'h1', 'h2',
                                                                          'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'menu',
                                                                          'nav', 'ol', 'p', 'pre', 'section', 'table', 'ul')))
                                )
           );

  }

  static protected function domAttrIsBoolean(DOMAttr $attr)
  {
    # INKOMPLETT !!
    # gibt anscheinend keine Liste
    # http://www.google.de/#hl=de&source=hp&q=%22boolean+attribute%22+site%3Ahttp%3A%2F%2Fwww.whatwg.org%2Fspecs%2Fweb-apps%2Fcurrent-work%2Fmultipage%2F&aq=f&aqi=&aql=&oq=&gs_rfai=&fp=e48ec3a97faa7ccb
    #
    $tag_name = $attr->ownerElement->tagName;
    return $attr->name == 'hidden'
        || ($tag_name == 'fieldset' && in_array($attr->name, array('disabled', 'readonly')))
        || ($tag_name == 'option' && in_array($attr->name, array('disabled', 'readonly', 'selected')))
        || ($tag_name == 'input' && in_array($attr->name, array('disabled', 'readonly', 'checked', 'required')))
        || ($tag_name == 'select' && in_array($attr->name, array('disabled', 'readonly', 'multiple', 'required')))
        ;

  }

  static protected function domNodeAttributesToString(DOMNode $node)
  {

    #Remove quotes around attribute values, when allowed (<p class="foo"> → <p class=foo>)
    #Remove optional values from boolean attributes (<option selected="selected"> → <option selected>)
    $attrstr = '';
    if ($node->attributes != null)
    {
      foreach ($node->attributes as $attribute)
      {
        if (self::domAttrDiscardable($attribute))
          continue;
        $attrstr .= $attribute->name;
        if (!self::domAttrIsBoolean($attribute))
        {
          $attrstr .= '=';

          # http://www.whatwg.org/specs/web-apps/current-work/multipage/syntax.html#attributes-0
          $omitquotes = $attribute->value != '' && 0 == preg_match('/["\'=<>` \t\r\n\f]+/', $attribute->value) ;
          # DOM behält ENTITES nicht bei
          # http://www.w3.org/TR/2004/REC-xml-20040204/#sec-predefined-ent
          $attr_val = strtr($attribute->value, array('"' => '&quot;', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'));
          $attrstr .= ($omitquotes ? '' : '"') . $attr_val . ($omitquotes ? '' : '"');
        }
        $attrstr .= ' ';
      }
    }
    return trim($attrstr);
  }

  static protected function domNodeToString(DOMNode $node)
  {
    $htmlstr = '';
    foreach ($node->childNodes as $child)
    {
      if ($child instanceof DOMDocumentType)
      {
        // $htmlstr .= '<!doctype html>';
        /* remove */
      }
      else if ($child instanceof DOMElement)
      {
        if (!self::domNodeDiscardable($child))
        {
          $htmlstr .= trim('<' . $child->tagName . ' ' . self::domNodeAttributesToString($child));

          $htmlstr .= '>' . self::domNodeToString($child);
          if (!self::domNodeClosingTagOmittable($child))
          {
            $htmlstr .= '</' . $child->tagName . '>';
          }
        }
      }
      else if ($child instanceof DOMText)
      {
        if ($child->isWhitespaceInElementContent())
        {
          if ($child->previousSibling !== null && $child->nextSibling !== null)
          {
            $htmlstr .= ' ';
          }
        } else
        {
          # DOM behält ENTITES nicht bei
          # http://www.w3.org/TR/2004/REC-xml-20040204/#sec-predefined-ent
          $htmlstr .= strtr($child->wholeText, array('&' => '&amp;', '<' => '&lt;', '>' => '&gt;'));
        }
      }
      else if ($child instanceof DOMComment)
      {
        // KOMMENTARE SCHÖN IGNOREN
        // TODO KEEP IE CONDITIONAL COMMENTS
      }
      else
      {
        echo 'Unhandled:' . get_class($child) . "\n";
      }
    }
    $htmlstr = str_replace('<html><body>', '', $htmlstr);
    $htmlstr = preg_replace("/\n|\r\n|\r/", ' ', $htmlstr);
    return $htmlstr;
  }

  static function mini($html, $consider_inline = 'li')
  {
    $dom = new DOMDocument();
    $dom->substituteEntities = false;
    $dom->loadHTML(str_replace('<head>', '<head><Meta http-equiv="content-type" content="text/html; charset=utf-8">', $html));

    return self::domNodeToString($dom);
  }
}

/**
 * JSMin.php - modified PHP implementation of Douglas Crockford's JSMin.
 *
 * <code>
 * $minifiedJs = JSMin::minify($js);
 * </code>
 *
 * This is a modified port of jsmin.c. Improvements:
 *
 * Does not choke on some regexp literals containing quote characters. E.g. /'/
 *
 * Spaces are preserved after some add/sub operators, so they are not mistakenly
 * converted to post-inc/dec. E.g. a + ++b -> a+ ++b
 *
 * Preserves multi-line comments that begin with /*!
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com> (PHP port)
 * @author Steve Clay <steve@mrclay.org> (modifications + cleanup)
 * @author Andrea Giammarchi <http://www.3site.eu> (spaceBeforeRegExp)
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @link http://code.google.com/p/jsmin-php/
 */


class JSMin {
    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;

    protected $a           = "\n";
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = '';
    protected $lastByteOut  = '';
    protected $keptComment = '';

    /**
     * Minify Javascript.
     *
     * @param string $js Javascript to be minified
     *
     * @return string
     */
    public static function mini($js)
    {
        $jsmin = new JSMin($js);
        return $jsmin->min();
    }

    /**
     * @param string $input
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * Perform minification, return result
     *
     * @return string
     */
    public function min()
    {
        if ($this->output !== '') { // min already run
            return $this->output;
        }

        $mbIntEnc = null;
        if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('8bit');
        }
        $this->input = str_replace("\r\n", "\n", $this->input);
        $this->inputLength = strlen($this->input);

        $this->action(self::ACTION_DELETE_A_B);

        while ($this->a !== null) {
            // determine next command
            $command = self::ACTION_KEEP_A; // default
            if ($this->a === ' ') {
                if (($this->lastByteOut === '+' || $this->lastByteOut === '-')
                        && ($this->b === $this->lastByteOut)) {
                    // Don't delete this space. If we do, the addition/subtraction
                    // could be parsed as a post-increment
                } elseif (! $this->isAlphaNum($this->b)) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif ($this->a === "\n") {
                if ($this->b === ' ') {
                    $command = self::ACTION_DELETE_A_B;

                    // in case of mbstring.func_overload & 2, must check for null b,
                    // otherwise mb_strpos will give WARNING
                } elseif ($this->b === null
                          || (false === strpos('{[(+-!~', $this->b)
                              && ! $this->isAlphaNum($this->b))) {
                    $command = self::ACTION_DELETE_A;
                }
            } elseif (! $this->isAlphaNum($this->a)) {
                if ($this->b === ' '
                    || ($this->b === "\n"
                        && (false === strpos('}])+-"\'', $this->a)))) {
                    $command = self::ACTION_DELETE_A_B;
                }
            }
            $this->action($command);
        }
        $this->output = trim($this->output);

        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
        return $this->output;
    }

    /**
     * ACTION_KEEP_A = Output A. Copy B to A. Get the next B.
     * ACTION_DELETE_A = Copy B to A. Get the next B.
     * ACTION_DELETE_A_B = Get the next B.
     *
     * @param int $command
     * @throws JSMin_UnterminatedRegExpException|JSMin_UnterminatedStringException
     */
    protected function action($command)
    {
        // make sure we don't compress "a + ++b" to "a+++b", etc.
        if ($command === self::ACTION_DELETE_A_B
            && $this->b === ' '
            && ($this->a === '+' || $this->a === '-')) {
            // Note: we're at an addition/substraction operator; the inputIndex
            // will certainly be a valid index
            if ($this->input[$this->inputIndex] === $this->a) {
                // This is "+ +" or "- -". Don't delete the space.
                $command = self::ACTION_KEEP_A;
            }
        }

        switch ($command) {
            case self::ACTION_KEEP_A: // 1
                $this->output .= $this->a;

                if ($this->keptComment) {
                    $this->output = rtrim($this->output, "\n");
                    $this->output .= $this->keptComment;
                    $this->keptComment = '';
                }

                $this->lastByteOut = $this->a;

                // fallthrough intentional
            case self::ACTION_DELETE_A: // 2
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"') { // string literal
                    $str = $this->a; // in case needed for exception
                    for(;;) {
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;

                        $this->a = $this->get();
                        if ($this->a === $this->b) { // end quote
                            break;
                        }
                        if ($this->isEOF($this->a)) {
                            throw new JSMin_UnterminatedStringException(
                                "JSMin: Unterminated String at byte {$this->inputIndex}: {$str}");
                        }
                        $str .= $this->a;
                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->lastByteOut = $this->a;

                            $this->a       = $this->get();
                            $str .= $this->a;
                        }
                    }
                }

                // fallthrough intentional
            case self::ACTION_DELETE_A_B: // 3
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral()) {
                    $this->output .= $this->a . $this->b;
                    $pattern = '/'; // keep entire pattern in case we need to report it in the exception
                    for(;;) {
                        $this->a = $this->get();
                        $pattern .= $this->a;
                        if ($this->a === '[') {
                            for(;;) {
                                $this->output .= $this->a;
                                $this->a = $this->get();
                                $pattern .= $this->a;
                                if ($this->a === ']') {
                                    break;
                                }
                                if ($this->a === '\\') {
                                    $this->output .= $this->a;
                                    $this->a = $this->get();
                                    $pattern .= $this->a;
                                }
                                if ($this->isEOF($this->a)) {
                                    throw new JSMin_UnterminatedRegExpException(
                                        "JSMin: Unterminated set in RegExp at byte "
                                            . $this->inputIndex .": {$pattern}");
                                }
                            }
                        }

                        if ($this->a === '/') { // end pattern
                            break; // while (true)
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                            $pattern .= $this->a;
                        } elseif ($this->isEOF($this->a)) {
                            throw new JSMin_UnterminatedRegExpException(
                                "JSMin: Unterminated RegExp at byte {$this->inputIndex}: {$pattern}");
                        }
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;
                    }
                    $this->b = $this->next();
                }
            // end case ACTION_DELETE_A_B
        }
    }

    /**
     * @return bool
     */
    protected function isRegexpLiteral()
    {
        if (false !== strpos("(,=:[!&|?+-~*{;", $this->a)) {
            // we obviously aren't dividing
            return true;
        }
        if ($this->a === ' ' || $this->a === "\n") {
            $length = strlen($this->output);
            if ($length < 2) { // weird edge case
                return true;
            }
            // you can't divide a keyword
            if (preg_match('/(?:case|else|in|return|typeof)$/', $this->output, $m)) {
                if ($this->output === $m[0]) { // odd but could happen
                    return true;
                }
                // make sure it's a keyword, not end of an identifier
                $charBeforeKeyword = substr($this->output, $length - strlen($m[0]) - 1, 1);
                if (! $this->isAlphaNum($charBeforeKeyword)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return the next character from stdin. Watch out for lookahead. If the character is a control character,
     * translate it to a space or linefeed.
     *
     * @return string
     */
    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null) {
            // getc(stdin)
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            } else {
                $c = null;
            }
        }
        if (ord($c) >= self::ORD_SPACE || $c === "\n" || $c === null) {
            return $c;
        }
        if ($c === "\r") {
            return "\n";
        }
        return ' ';
    }

    /**
     * Does $a indicate end of input?
     *
     * @param string $a
     * @return bool
     */
    protected function isEOF($a)
    {
        return ord($a) <= self::ORD_LF;
    }

    /**
     * Get next char (without getting it). If is ctrl character, translate to a space or newline.
     *
     * @return string
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

    /**
     * Return true if the character is a letter, digit, underscore, dollar sign, or non-ASCII character.
     *
     * @param string $c
     *
     * @return bool
     */
    protected function isAlphaNum($c)
    {
        return (preg_match('/^[a-z0-9A-Z_\\$\\\\]$/', $c) || ord($c) > 126);
    }

    /**
     * Consume a single line comment from input (possibly retaining it)
     */
    protected function consumeSingleLineComment()
    {
        $comment = '';
        while (true) {
            $get = $this->get();
            $comment .= $get;
            if (ord($get) <= self::ORD_LF) { // end of line reached
                // if IE conditional comment
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                    $this->keptComment .= "/{$comment}";
                }
                return;
            }
        }
    }

    /**
     * Consume a multiple line comment from input (possibly retaining it)
     *
     * @throws JSMin_UnterminatedCommentException
     */
    protected function consumeMultipleLineComment()
    {
        $this->get();
        $comment = '';
        for(;;) {
            $get = $this->get();
            if ($get === '*') {
                if ($this->peek() === '/') { // end of comment reached
                    $this->get();
                    if (0 === strpos($comment, '!')) {
                        // preserved by YUI Compressor
                        if (!$this->keptComment) {
                            // don't prepend a newline if two comments right after one another
                            $this->keptComment = "\n";
                        }
                        $this->keptComment .= "/*!" . substr($comment, 1) . "*/\n";
                    } else if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        // IE conditional
                        $this->keptComment .= "/*{$comment}*/";
                    }
                    return;
                }
            } elseif ($get === null) {
                throw new JSMin_UnterminatedCommentException(
                    "JSMin: Unterminated comment at byte {$this->inputIndex}: /*{$comment}");
            }
            $comment .= $get;
        }
    }

    /**
     * Get the next character, skipping over comments. Some comments may be preserved.
     *
     * @return string
     */
    protected function next()
    {
        $get = $this->get();
        if ($get === '/') {
            switch ($this->peek()) {
                case '/':
                    $this->consumeSingleLineComment();
                    $get = "\n";
                    break;
                case '*':
                    $this->consumeMultipleLineComment();
                    $get = ' ';
                    break;
            }
        }
        return $get;
    }
}

class JSMin_UnterminatedStringException extends \Exception {}
class JSMin_UnterminatedCommentException extends \Exception {}
class JSMin_UnterminatedRegExpException extends \Exception {}


class Assets
{

    /* ==== START USER CONFIG ==== */

    /**
     * When $mergejs is set to true, all enqueued javascript files will be merged and named as per $mergedFileNamejs
     * set below. If set to false, all javascript files will retain their original file name.
     * @var boolean true
     */
    private $mergejs = true;

    /**
     * When $mergecss is set to true, all enqueued cascading style sheet files will be merged and named as per
     * $mergedFileNamecss set below. If set to false, all javascript files will retain their original file name.
     * @var boolean true
     */
    private $mergecss = true;

    /**
     * $mergedFileNamejs is used to set the merged javascript file name. This setting is ignored if the $mergejs value
     * above is set to false.
     * @var string 'style.min.css'
     */
    private $mergedFileNamejs = 'scripts.min.js';

    /**
     * $mergedFileNamecss is used to set the merged cascading style sheet file name. This setting is ignored if the
     * $mergecss value above is set to false.
     * @var string 'style.min.css'
     */
    private $mergedFileNamecss = 'style.min.css';

    /**
     * When $minifyjs is set to true, all enqueued javascript files will be minified.
     * @var boolean true
     */
    private $minifyjs = false;

    /**
     * When $minifycss is set to true, all enqueued cascading style sheet files will be minified.
     * @var boolean true
     */
    private $minifycss = true;

    /**
     * When $minifyTmpl is set to true, all enqueued template files (HTML) will be minified.
     * @var boolean true
     */
    private $minifytmpl = true;

    /**
     * $templateTypeAttr is used as the type attribute of the wrapping script tag for the template.
     * @var string 'text/x-handlebars'
     */
    private $templateTypeAttr = 'text/x-handlebars';

    /**
     * When $outputTemplateAsHTML is set to true, the template HTML string will be rendered inside the <script> tag. If
     * set to false, the file will be pointed using the src attribute in the <script> tag.
     * @var boolean true
     */
    private $outputTemplateAsHTML = true;

    /**
     * When $devModejs is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get a
     * fresh version of the file off the server.
     * @var boolean false
     */
    private $devModejs = false;

    /**
     * When $devModecss is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get
     * a fresh version of the file off the server.
     * @var boolean false
     */
    private $devModecss = false;

    /**
     * When $devModetmpl is set to true, a ?[random_string] will be appended to the file URL, forcing the browser to get
     * a fresh version of the file off the server.
     * @var boolean false
     */
    private $devModetmpl = false;

    /**
     * $mincache is relative to your $baseDirectory set above
     * @var string '.mincache'
     */
    private $mincache = '.mincache';

    /**
     * $skipStartup flag. When this is set to true, all directory checking and changed setting checks will be skipped.
     * Use only during production!.
     * @var boolean false
     */
    private $skipStartup = false;

    /* ===== END USER CONFIG ===== */

    // Here be dragons! Do not edit the following lines

    private $baseUrl;
    private $cacheDirectory;
    private $assetDirectory;
    private $files = array();
    private $cacheDirectoryjs;
    private $cacheDirectorycss;
    private $cacheDirectorytmpl;
    private $assetDirectoryjs;
    private $assetDirectorycss;
    private $assetDirectorytmpl;
    private $isDirtyjs;
    private $isDirtycss;
    private $isDirtytmpl;
    private $mergetmpl = false;

    private static $configSet = false;
    private static $Instance;

    /**
     * Assets::enqueue method.
     * @access public
     * @param string $files javascript / cascading stylesheets files to enqueue. The files are relative to your working
     * directory If in doubt, run getcwd() to get your current working directory. The parameters of this method are
     * overloadable. If one of the files cannot be read by PHP, it will throw an \Exception. Files enqueued will be
     * rendered according to the order it was enqueued. Allowed file extensions : .js, .css, .tmpl.
     * @example Assets::enqueue('js/jquery.js', 'js/underscore.js', 'js/backbone.js', 'dashboard.tmpl', 'style.css');
     * @return void
     */
    public static function enqueue()
    {
        $T = self::getInstance();

        // Overloadable Arguments
        $num = func_num_args();

        if($num > 0) {
            for($i = 0; $i < $num; $i++){
                $file = getcwd() . DIRECTORY_SEPARATOR . func_get_arg($i);
                if(file_exists($file)){
                    $fileType = $T->getFileType($file);
                    if(!isset($T->files[$fileType])) {
                        $T->files[$fileType] = array();
                    }
                    if(!in_array($file, $T->files[$fileType])) {
                        $T->checkForDirty($file, $fileType);
                        $T->files[$fileType][] = $file;
                    }
                } else {
                    throw new \Exception("{$file} does not exists");
                }
            }
        }
    }

    /**
     * Assets::dequeue method.
     * @access public
     * @param string $files javascript / cascading stylesheets files to dequeue. The files are relative to your working
     * directory If in doubt, run getcwd() to get your current working directory. The parameters of this method are
     * overloadable. All instances of the file will be removed from the Assets::enqueue list;
     * @example Assets::dequeue('style.css');
     * @return void
     */
    public static function dequeue()
    {
        $T = self::getInstance();

        // Overloadable Arguments
        $num = func_num_args();

        if($num > 0) {
            for($i = 0; $i < $num; $i++){
                $file = getcwd() . DIRECTORY_SEPARATOR . func_get_arg($i);
                $fileType = $T->getFileType($file);
                if(in_array($fileType, $this->supportedTypes)) {
                    $tempFiles = array();
                    foreach ($T->files[$fileType] as $enqueuedFile) {
                        if($enqueuedFile !== $file) {
                            $tempFiles[] = $file;
                        }
                    }
                    $T->files[$fileType] = $tempFiles;
                }
            }
        }
    }

    /**
     * Assets::render method.
     * @access public
     * @param string $fileType Supported filetypes are 'js' and 'css'. When running Assets::render('js'), each
     * javascript file enqueued using the Assets::enqueue() method will be rendered as a src attribute within a
     * <script> tag while Assets::render('css') will render cascading style sheet files enqueued as a href attribute
     * inside a <link rel="stylesheet"> tag.
     * @param string $namespace will be appended infront of both javascript and css files if they are merged into a
     * singular file.
     * @example Assets::render('css', 'dashboard'); outputs <link rel="stylesheet" src="dashboard.style.css">
     * @return string generated asset html tags
     */
    public static function render($fileType, $namespace = null)
    {
        $T = self::getInstance();
        $mergedFileName = 'mergedFileName'.$fileType;
        $isDirty = 'isDirty'.$fileType;
        $merge = 'merge'.$fileType;
        $cacheDirectory = 'cacheDirectory'.$fileType;
        $assetDirectory = 'assetDirectory'.$fileType;

        if(isset($T->files[$fileType])) {
            $str = '';
            $ofiles = array();
            $devMode = 'devMode' . $fileType;
            $namespace = ($namespace) ? $namespace . '.' : '';
            foreach ($T->files[$fileType] as $filePath) {
                $fileParts = preg_split('/(?<=[\/\\\])(?![\/\\\])/', $filePath);
                $fileName = array_pop($fileParts);
                if($T->$merge) {
                    if($T->$isDirty ||
                        !file_exists($T->$assetDirectory . $namespace . $T->$mergedFileName)){
                        $cache_name = md5($filePath);
                        $cachedFileName = $T->$cacheDirectory . $cache_name;
                        $str .= file_get_contents($cachedFileName);
                        $str .= ($fileType === 'js') ? ";\n\n" : "\n\n";
                    }
                } else {
                    $cache_name = md5($filePath);
                    $cachedFileName = $T->$cacheDirectory . $cache_name;
                    $devModeStr = ($T->$devMode) ? '?' . $T->randAlphaNum() : '';
                    if ($fileType === 'tmpl' && $T->outputTemplateAsHTML) {
                        $templateName = str_replace('.tmpl', '', $fileName);
                        $ofiles[] = array('name' => $templateName, 'contents' => file_get_contents($cachedFileName));
                    } else {
                        $ofiles[] = $T->baseUrl.$fileType.'/min/'.$fileName . $devModeStr;
                        if($T->$isDirty || !file_exists($T->$assetDirectory.$fileName)) {
                            copy($cachedFileName, $T->$assetDirectory.$fileName);
                        }
                    }
                }
            }
            if($T->$merge) {
                if($T->$isDirty ||
                    !file_exists($T->$assetDirectory.$namespace.$T->$mergedFileName)) {
                    file_put_contents($T->$assetDirectory.$namespace.$T->$mergedFileName, $str);
                }
                $devModeStr = ($T->$devMode) ? '?' . $T->randAlphaNum() : '';
                $ofiles[] = $T->baseUrl.$fileType.'/min/'.$namespace.$T->$mergedFileName . $devModeStr;
            }
        }
        return $T->genHTML($ofiles, $fileType);
    }

    /**
     * Assets::renderAll method.
     * @access public
     * @param string $namespace will be appended infront of all files if they are merged into a singular file.
     * @param boolean $reset if set to true, the reset method will be called after generation.
     * @example Assets::renderAll('dashboard', true);
     * @return array of generated asset strings
     */
    public static function renderAll($namespace = '', $reset = false)
    {
        $ret = array();
        foreach ($this->supportedTypes as $type) {
            $ret[$type] = self::render($type, $namespace);
        }
        if($reset) {
            self::reset();
        }
        return $ret;
    }

    /**
     * Assets::reset method. This method is to reset the internal Instance variable to null.
     * @access public
     * @example Assets::reset();
     * @return array of generated asset strings
     */
    public static function reset()
    {
        self::$Instance = null;
    }

    public static function configure($config = array())
    {
        $T = self::getInstance(true);
        if(!isset($config['baseUrl']) || !isset($config['assetDirectory']) || !isset($config['cacheDirectory'])) {
            throw new \Exception('Usage : $config = array("baseUrl" => "/", "assetDirectory" => "assets", "cacheDirectory" => "cache"); Assets::configure($config);');
        } else {
            self::$configSet = true;

            $T->baseUrl = $config['baseUrl'];
            $T->assetDirectory = $config['assetDirectory'];
            $T->cacheDirectory = $config['cacheDirectory'];
            $T->cacheDirectory = getcwd() . DIRECTORY_SEPARATOR . $T->cacheDirectory . DIRECTORY_SEPARATOR;
            $T->cacheDirectoryjs = $T->cacheDirectory . 'js' . DIRECTORY_SEPARATOR;
            $T->cacheDirectorycss = $T->cacheDirectory .'css' . DIRECTORY_SEPARATOR;
            $T->cacheDirectorytmpl = $T->cacheDirectory .'tmpl' . DIRECTORY_SEPARATOR;
            $T->assetDirectory = getcwd() . DIRECTORY_SEPARATOR . $T->assetDirectory . DIRECTORY_SEPARATOR;
            $T->assetDirectoryjs = $T->assetDirectory . 'js' . DIRECTORY_SEPARATOR . 'min' . DIRECTORY_SEPARATOR;
            $T->assetDirectorycss = $T->assetDirectory . 'css' . DIRECTORY_SEPARATOR . 'min' . DIRECTORY_SEPARATOR;
            $T->assetDirectorytmpl = $T->assetDirectory . 'tmpl' . DIRECTORY_SEPARATOR . 'min' . DIRECTORY_SEPARATOR;
            $T->mincache = $T->cacheDirectory . $T->mincache;
            $T->supportedTypes = array('js', 'css', 'tmpl');
            $T->startUp();
        }
    }

    public static function clearCache()
    {
        $T = self::getInstance();
        $T->internalClearCache();
    }

    private function randAlphaNum($random_string_length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        for ($i = 0; $i < $random_string_length; $i++) {
          $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    private function genHTML($files, $fileType)
    {
        $str = '';
        foreach ($files as $file) {
            switch ($fileType) {
                case 'js':
                    $str .= "<script type=\"text/javascript\" src=\"{$file}\"></script>\n";
                    break;
                case 'css':
                    $str .= "<link rel=\"stylesheet\" href=\"{$file}\">\n";
                    break;
                case 'tmpl':
                    if($this->outputTemplateAsHTML) {
                        $str .= "<script type=\"{$this->templateTypeAttr}\" id=\"{$file['name']}\">{$file['contents']}</script>\n";
                    } else {
                        $str .= "<script type=\"{$this->templateTypeAttr}\" src=\"{$file}\"></script>\n";
                    }
                    break;
                default:
                    break;
            }
        }
        return $str;
    }

    private function getFileType($file)
    {
        // check for type
        $fileType = substr($file,-3,3);
        if ($fileType === '.js') {
            $fileType = 'js';
        }
        if ($fileType === 'mpl') {
            $fileType = 'tmpl';
        }
        if(!in_array($fileType, $this->supportedTypes)) {
            throw new \Exception("Type Unknown {$file} supported file extensions are .js, .css and .tmpl");
        }

        return $fileType;
    }

    public function minify($file, $type)
    {
        $content = '';
        switch ($type) {
            case 'js':
                $js = file_get_contents($file);
                $content = JSMin::mini($js);
                break;
            case 'css':
                $css = file_get_contents($file);
                $content = CSSMin::mini($css);
                break;
            case 'tmpl':
                $tmpl = file_get_contents($file);
                $content = HTMLMin::mini($tmpl);
                break;
            default:
                break;
        }
        return $content;
    }

    private function checkForDirty($file, $fileType)
    {
        $cache_name = md5($file);
        $cacheDirectory = 'cacheDirectory' . $fileType;
        $cachedFileName = $this->$cacheDirectory . $cache_name;

        $isDirty = 'isDirty'.$fileType;
        $minify = 'minify'.$fileType;

        if(!file_exists($cachedFileName)) {
            if($this->$minify) {
                if($this->$minify) {
                    $fileContents = $this->minify($file, $fileType);
                } else {
                    $fileContents = file_get_contents($file);
                }
            } else {
                $fileContents = file_get_contents($file);
            }
            file_put_contents($cachedFileName, $fileContents);
            $this->$isDirty = true;
        } else if (filemtime($file) > filemtime($cachedFileName)) {
            if($this->$minify) {
                if($this->$minify) {
                    $fileContents = $this->minify($file, $fileType);
                } else {
                    $fileContents = file_get_contents($file);
                }
            } else {
                $fileContents = file_get_contents($file);
            }
            file_put_contents($cachedFileName, $fileContents);
            $this->$isDirty = true;
        }
    }

    private function internalClearCache()
    {
        $files = glob($this->cacheDirectoryjs . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->cacheDirectorycss . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->cacheDirectorytmpl . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectoryjs . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectorycss . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        $files = glob($this->assetDirectorytmpl . '*');
        foreach($files as $file){
          if(is_file($file))
            unlink($file);
        }
        unlink($this->mincache);
    }

    private function createMincache($overwrite = false)
    {
        if($overwrite) {
            if(file_exists($this->mincache)) {
                unlink($this->mincache);
            }
        }
        file_put_contents($this->mincache, json_encode(array(
          'mergejs' => $this->mergejs,
          'mergecss' => $this->mergecss,
          'mergedFileNamejs' => $this->mergedFileNamejs,
          'mergedFileNamecss' => $this->mergedFileNamecss,
          'minifyjs' => $this->minifyjs,
          'minifycss' => $this->minifycss,
          'minifytmpl' => $this->minifytmpl,
          'templateTypeAttr' => $this->templateTypeAttr,
          'outputTemplateAsHTML' => $this->outputTemplateAsHTML,
          'devModejs' => $this->devModejs,
          'devModecss' => $this->devModecss,
          'devModetmpl' => $this->devModetmpl,
          'baseUrl' => $this->baseUrl,
          'cacheDirectory' => $this->cacheDirectory,
          'mincache' => $this->mincache,
          'assetDirectory' => $this->assetDirectory,
          'skipStartup' => $this->skipStartup
        )));
    }

    private function startUp()
    {
        if(!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, 0777, true);
            mkdir($this->cacheDirectoryjs, 0777, true);
            mkdir($this->cacheDirectorycss, 0777, true);
            mkdir($this->cacheDirectorytmpl, 0777, true);

        }

        if(!file_exists($this->assetDirectory)) {
            mkdir($this->assetDirectory, 0777, true);
        }

        if (!file_exists($this->assetDirectory .'js')){
            mkdir($this->assetDirectory .'js', 0777, true);
            mkdir($this->assetDirectory .'js/min', 0777, true);
        }

        if (!file_exists($this->assetDirectory .'css')){
            mkdir($this->assetDirectory .'css', 0777, true);
            mkdir($this->assetDirectory .'css/min', 0777, true);
        }

        if (!file_exists($this->assetDirectory .'tmpl')){
            mkdir($this->assetDirectory .'tmpl', 0777, true);
            mkdir($this->assetDirectory .'tmpl/min', 0777, true);
        }

        if(!file_exists($this->mincache)) {
            $this->createMincache();
        }

        $lastSettings = json_decode(file_get_contents($this->mincache));
        if( !isset($lastSettings->mergejs) ||
            !isset($lastSettings->mergecss) ||
            !isset($lastSettings->mergedFileNamejs) ||
            !isset($lastSettings->mergedFileNamecss) ||
            !isset($lastSettings->minifyjs) ||
            !isset($lastSettings->minifycss) ||
            !isset($lastSettings->minifytmpl) ||
            !isset($lastSettings->templateTypeAttr) ||
            !isset($lastSettings->outputTemplateAsHTML) ||
            !isset($lastSettings->devModejs) ||
            !isset($lastSettings->devModecss) ||
            !isset($lastSettings->devModetmpl) ||
            !isset($lastSettings->baseUrl) ||
            !isset($lastSettings->cacheDirectory) ||
            !isset($lastSettings->mincache) ||
            !isset($lastSettings->assetDirectory) ||
            !isset($lastSettings->skipStartup)
        ) {
            $this->setAllDirty();
            $this->createMincache(true);
            $lastSettings = json_decode(file_get_contents($this->mincache));
        }
        if(is_object($lastSettings)) {
            if ($lastSettings->mergejs !== $this->mergejs ||
                $lastSettings->mergecss !== $this->mergecss ||
                $lastSettings->mergedFileNamejs !== $this->mergedFileNamejs ||
                $lastSettings->mergedFileNamecss !== $this->mergedFileNamecss ||
                $lastSettings->minifyjs !== $this->minifyjs ||
                $lastSettings->minifycss !== $this->minifycss ||
                $lastSettings->minifytmpl !== $this->minifytmpl ||
                $lastSettings->templateTypeAttr !== $this->templateTypeAttr ||
                $lastSettings->outputTemplateAsHTML !== $this->outputTemplateAsHTML ||
                $lastSettings->devModejs !== $this->devModejs ||
                $lastSettings->devModecss !== $this->devModecss ||
                $lastSettings->devModetmpl !== $this->devModetmpl ||
                $lastSettings->baseUrl !== $this->baseUrl ||
                $lastSettings->cacheDirectory !== $this->cacheDirectory ||
                $lastSettings->mincache !== $this->mincache ||
                $lastSettings->assetDirectory !== $this->assetDirectory ||
                $lastSettings->skipStartup !== $this->skipStartup
            ) {
                $this->setAllDirty();
                $this->createMincache(true);
                $this->createMincache(true);
            }
        } else {
            throw new \Exception("Invalid .mincache file at {$this->mincache}");
        }
    }

    private function setAllDirty()
    {
        $this->internalClearCache();
        $this->isDirtyjs = true;
        $this->isDirtycss = true;
        $this->isDirtytmpl = true;
    }

    private static function getInstance($skipConfigCheck = false)
    {
        if(!$skipConfigCheck) {
            if(!self::$configSet) {
                throw new \Exception("Configuration must be set first using Assets::configure()");
            }
        }

        if(self::$Instance){
            return self::$Instance;
        }

        if($skipConfigCheck) {
            $Instance = new Assets(true);
        } else {
            $Instance = new Assets;
        }
        self::$Instance = $Instance;
        return $Instance;
    }
}

/* End of Assets.php */