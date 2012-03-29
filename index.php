<?php
/**
 * Minigitweb - a git web client contained in a single file.
 *
 * Put this file (or a symlink to it) inside a git repository. Then point the browser to it.
 *
 * Note: the webserver must have read permission in the .git directory.
 * For "git status", the webserver must also have write permission, to create a lock file.
 *
 * By Viktor 2011-05-04
 */

// unregister globals
if (ini_get('register_globals')) {
  foreach ($GLOBALS as $k => $v) {
    if ($k !== 'GLOBALS' && $k !== '_GET' && $k !== '_SERVER') unset($GLOBALS[$k]);
  }
}
error_reporting(E_ALL | E_NOTICE);

header("Content-Type: text/html; charset=UTF-8");

// Find directory containing the .git directory
if (isset($_SERVER['REQUEST_METHOD'])) $path = $_SERVER['SCRIPT_FILENAME']; // for web scripts
else $path = getcwd(); // for command line use
while(true) {
  if (file_exists("$path/.git")) break;
  $oldpath = $path;
  $path = dirname($path);
  if ($path == $oldpath) {
    // eternal loop
    trigger_error("Not in a git repository", E_USER_ERROR);
    break;
  }
}

chdir($path);

$repo = basename($path);
$do = $_GET['do'];
if (!$do) $do = 'status';

?>
<!DOCTYPE html>
<html>
  <head>
    <title>git <?= $do . ' @ ' . htmlspecialchars($repo) ?> - minigitweb</title>
    <style>
      /* general stuff */
      input.check   { vertical-align: bottom; margin:0 3px }
      a             { text-decoration: none; }

      /* page elements */
      body          { margin: 0; padding: 0 }
      .header       { background-color: #000; color: #fff; }
      .content      { padding: 0.3em; font-family: monospace; }
      .content a    { background-color: #f5f5ff; -moz-border-radius: 6px; border-radius: 6px; }
      .content a:hover { background-color: #aaf; }
      h1, h2        { margin: 0; padding: 0 0.3em; }
      h2            { background-color: #999; color: #333; }
      ul.menu       { list-style-type: none; padding-left: 0.3em; margin: 0; }
      ul.menu li    { display: inline-block; margin-right: 0.5em; }
      ul.menu li a  { text-decoration: none; padding: 0.2em;
                      color: orange /*#8A5900*/; font-weight: bold; }
      p             { margin: 0 0 1em 0 }
      label         { padding: 0 1em; }
      input[type="radio"] { margin: 0; }

      table         { border-collapse: collapse; }
      tr.odd td     { background-color: #eee; }
      th            { text-align: center; font-weight: normal; }

      .actions      { margin-bottom: 0.3em; }
      .actions input { margin-right: 0.3em; }

      /* diff table */
      table.diff {
        border-collapse: collapse;
        width: 100%;
        border-right: solid 1px #ddd;
        border-bottom: solid 1px #ddd;
        margin: 0 0 1em 0;
      }
      .diff col.nums {
        width: 0%;
      }
      .diff col.left,
      .diff col.right {
        width: 50%
      }
      .diff td          { font-family: monospace; border-left: solid 1px #ddd; overflow: hidden;
                          vertical-align: top; }
      .diff tr.metainfo { background-color: #f7f7f7 }
      .diff tr.first td { border-top: solid 1px #ddd }
      .diff tr.del td   { background-color: #ffcccc }
      .diff tr.ins td   { background-color: #ccffcc }
      .diff tr.mod td   { background-color: #ffffcc }
      .diff td.nums     { font-weight: bold; padding-left: 3px; padding-right: 7px; }
    </style>
  </head>
  <body>

    <div class="header">
      <h1><?= htmlspecialchars($repo) ?></h1>
      <ul class="menu">
        <li><a href="?do=status">status</a></li>
        <li><a href="?do=log">log</a></li>
        <li><a href="?do=diff">diff</a></li>
        <li><a href="?do=branch">branch</a></li>
        <li><a href="?do=tag">tag</a></li>
        <li><a href="?do=stash">stash</a></li>
        <li><a href="?do=help">help</a></li>
      </ul>
    </div>
    <?
    switch ($do) {
      case 'log': {
        $cmd = "git log";
        $pagination = false;
        if (isset($_GET['from'], $_GET['to'])) {
          $cmd .= " {$_GET['from']}..{$_GET['to']}";
        }
        elseif (isset($_GET['commit'])) {
          $commit = $_GET['commit'];
          $cmd .= " $commit"; // --max-count=100
        }
        else {
          $pagination = true;
          if (isset($_GET['n'])) {
            if ($_GET['n'] > 0) $cmd .= " --max-count=" . intval($_GET['n']);
          }
          else $cmd .= " --max-count=10";
          if (isset($_GET['skip'])) $cmd .= " --skip=" . intval($_GET['skip']);
        }
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';

        echo '<form action="">';
        echo '<div class="actions">';
        echo '<input type="submit" name="do" value="diff" /> ';
        if ($pagination) {
          echo '<a href="?do=log&amp;n=10">1&hellip;10</a> ';
          echo '<a href="?do=log&amp;n=90&amp;skip=10">11̣&hellip;100</a> ';
          echo '<a href="?do=log&amp;n=0&amp;skip=100">101&hellip;&infin;</a> ';
        }
        echo '</div>';
        $log = `$cmd 2>&1`;
        $log = preg_replace(
          '/^commit (\S+)$/m',
          "commit <a href=\"?do=diff&amp;commit[]=$1\">$1</a>"
          . ' <label><input type="checkbox" class="check" name="commit[]" value="$1" /></label>',
          htmlspecialchars($log)
        );
        //echo '<div>',
        //  '<a href="?do=diff&amp;commit[]=HEAD">HEAD</a>',
        //  '<label><input type="checkbox" class="check" name="commit[]" value="HEAD" /></label>',
        //  '</div>';
        echo "<pre>$log</pre>\n";
        echo "</form>\n";
        echo "</div>";
        break;
      }
      case 'show': {
        $commit = $_GET['commit'];
        $cmd = "git show $commit";
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        // TODO
        echo '</div>';
        break;
      }
      case 'branch': {
        $cmd = "git branch -a";
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        $branch = htmlspecialchars(`$cmd 2>&1`);
        $rows = explode("\n", $branch);
        if (count($rows) > 0) {
          echo '<form action="">';
          echo '<div class="actions">';
          echo '<input type="submit" name="do" value="diff" />';
          echo '<input type="submit" name="do" value="log" />';
          echo '<input type="submit" name="do" value="cherry" />';
          echo '</div>';
          echo '<table>';
          echo '<tr><th></th><th>from</th><th>to</th></tr>';
          $i = 0;
          foreach ($rows as $row) {
            if (preg_match('/^(.) (.*?)(\S+)$/', $row, $matches)) {
              $name = $matches[3];
              $row = "{$matches[2]}{$matches[3]}";
              $current = ($matches[1] == '*');
              $row = "<a href=\"?do=log&amp;commit=$name\">$row</a>";
              if ($current) $row = "<strong>$row</strong>";
              echo '<tr class="' . (++$i % 2 == 0 ? 'even' : 'odd') . '">';
              echo "<td>", ($current ? '*' : '&nbsp;'), "&nbsp;$row</td>";
              //echo "<td>cherry</a></td>";
              echo '<td><label><input type="radio" name="from" value="', $name, '"';
              if ($current) echo ' checked="checked"';
              echo ' /></label></td>';
              echo '<td><label><input type="radio" name="to" value="', $name, '"';
              if ($current) echo ' checked="checked"';
              echo ' /></label></td>';
              echo "</tr>\n";
            }
          }
          echo '</table></form>';
        }
        echo '</div>';
        break;
      }
      case 'status': {
        $cmd = "git status";
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        echo "<pre>", htmlspecialchars(`$cmd 2>&1`), "</pre>";
        echo '</div>';
        break;
      }
      case 'tag': {
        $cmd = 'git tag';
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        echo "<pre>", htmlspecialchars(`$cmd 2>&1`), "</pre>";
        echo '</div>';
        break;
      }
      case 'stash': {
        $cmd = 'git stash list';
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        $stash = htmlspecialchars(`$cmd 2>&1`);
        $stash = preg_replace(
          '/^[^:]+/m',
          '<a href="?do=diff&amp;commit[]=$0">$0</a>',
          $stash
        );
        echo "<pre>$stash</pre>";
        echo '</div>';
        break;
      }
      case 'stash-show': {
        $cmd = 'git stash show -v ' . $_GET['stash'];
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        $stash = htmlspecialchars(`$cmd 2>&1`);
        echo "<pre>$stash</pre>";
        echo '</div>';
        break;
      }
      case 'cherry': {
        $from = isset($_GET['from']) ? $_GET['from'] : 'HEAD';
        $to   = isset($_GET['to']) ? $_GET['to'] : 'HEAD';
        $cmd = "git cherry -v $from $to";
        echo "<h2>$cmd</h2>";
        echo '<div class="content">';
        $cherry = htmlspecialchars(`$cmd 2>&1`);
        $cherry = preg_replace(
          '/^(\+|-) (\S+)/m',
          '$1 <a href="?do=diff&amp;commit[]=$2">$2</a>',
          $cherry
        );
        echo "<pre>$cherry</pre>";
        echo '</div>';
        break;
      }
      case 'diff': {
        if (isset($_GET['commit'])) {
          $commits = (array)$_GET['commit'];
        }
        elseif (isset($_GET['from'], $_GET['to'])) {
          $commits = array($_GET['to'], $_GET['from']);
        }
        elseif (isset($_GET['commits'])) {
          $commits = $_GET['commits'];
        }
        else $commits = null;
        switch (count($commits)) {
          case 0: {
            //echo "<p>No commits selected.</p>";
            $from = 'HEAD';
            $to = '';
            break;
          }
          case 1: {
            //array_unshift($commits, 'HEAD');
            // fallthru
            list ($to) = $commits;
            if ($to == 'HEAD') {
              $from = $to; $to = '';
            }
            else $from = "$to^";
            break;
          }
          case 2: {
            list ($to, $from) = $commits;
            break;
          }
          default: {
            echo "<p>Too many commits selected for diff.</p>";
            break 2;
          }
        }

        $renderer = new GitDiffRenderer();
        $renderer->setCommitRange($from, $to);
        echo '<h2>', $renderer->getDiffCommand(), '</h2>';
        echo '<div class="content">';
        if (isset($_GET['blame'])) {
          echo '<p>Hover on line numbers for blame tooltip.</p>';
          $renderer->useBlame(true);
        }
        else {
          $renderer->useBlame(false);
          $param = $_GET;
          echo
            '<p><a href="?',
            htmlspecialchars($_SERVER['QUERY_STRING'] . '&blame'), '">Show blame</a>',
            '</p>';
        }
        $renderer->renderDiff();
        echo '</div>';
        break;
      }
      case 'help': {
        if (!empty($_GET['help'])) {
          $command = "git help {$_GET['help']}";
          echo "<h2>$command</h2>";
          echo '<div class="content">';
          $help = htmlspecialchars(`$command 2>&1`);
          $help = preg_replace(
            '/git-([\w-]+)\(1\)/',
            '<a href="?do=help&amp;help=$1">git-$1(1)</a>',
            $help);
          echo "<pre>$help</pre>";
          echo '</div>';
        }
        else {
          $command = "git help";
          echo "<h2>$command</h2>";
          echo '<div class="content">';
          $help = htmlspecialchars(`$command 2>&1`);
          $help = preg_replace(
            '/^   ([a-z]+)   /m',
            '   <a href="?do=help&amp;help=$1">$1</a>   ',
            $help);
          echo "<pre>$help</pre>";
          echo '</div>';
        }
        break;
      }
    }
    ?>
  </body>
</html>
<?
exit;

/*********** Classes ***********/

/**
 * Side-by-side (X)HTML diff renderer
 *
 * Blame information is also displayed as the title on each line in the line number column.
 *
 * git is called in the current directory, so use chdir to navigate before using this class.
 */
class GitDiffRenderer {

  private $from, $to;

  private $useBlame = true;

  private $lasttype = null;
  private $lineleft = 0;
  private $lineright = 0;
  private $leftfile = null;
  private $rightfile = null;
  private $blameleft = array();
  private $blameright = array();

  private $deleted_buffer = array();

  public function __construct() {
  }

  public function setCommitRange($from, $to) {
    $this->from = $from;
    $this->to   = $to;
  }

  public function useBlame($value = null) {
    if ($value !== null) $this->useBlame = $value;
    else return $this->useBlame;
  }

  private function printLine($type, $old, $new) {
    $class = $type;
    if ($type != $this->lasttype) $class .= ' first';
    $this->lasttype = $type;
    echo '<tr class="', $class, '">';
    if (is_null($old)) echo '<td class="nums"></td>';
    else {
      $title = isset($this->blameleft[$this->lineleft]) ? $this->blameleft[$this->lineleft] : '';
      echo '<td class="nums" title="', htmlspecialchars($title) ,'">',
           $this->lineleft++, '</td>';
    }
    echo '<td>', $this->textToHtml($old), '</td>';
    if (is_null($new)) echo '<td class="nums"></td>';
    else {
      $title = isset($this->blameright[$this->lineright]) ? $this->blameright[$this->lineright] : '';
      echo '<td class="nums" title="', htmlspecialchars($title) ,'">',
           $this->lineright++, '</td>';
    }
    echo '<td>', $this->textToHtml($new), '</td>';
    echo '</tr>';
  }

  private function textToHtml($text) {
    $html = htmlspecialchars($text);
    $html = preg_replace('/^( +)/e', 'str_repeat("&nbsp;", strlen("$1"))', $html);
    $html = preg_replace('/(  +)/e', '" " . str_repeat("&nbsp;", strlen("$1") - 1)', $html);
    $html = nl2br($html);
    return $html;
  }

  private function printFullLineHtml($html) {
    $class = 'metainfo';
    if ($this->lasttype != 'metainfo') $class .= ' first';
    $this->lasttype = 'metainfo';
    echo '<tr class="', $class, '"><td class="nums"></td><td colspan="3">', $html, '</td></tr>';
  }

  private function printFullLine($text) {
    $this->printFullLineHtml(htmlspecialchars($text));
  }

  public function getDiffCommand() {
    $command = "git diff";
    if ($this->from && $this->to) $command .= " {$this->from}..{$this->to}";
    elseif ($this->from) $command .= " {$this->from}";
    return $command;
  }

  private function printIntroLine($line) {
    /*
    if (preg_match('!^git --diff (\S+) (\S+)!', $line, $matches)) {
      $this->leftfile = 
      $this->rightfile = $matches[2];
      $line = htmlspecialchars($line);
      if ($matches[1][0] == 'a') {
        substr($matches[1], 2);
        $line .= '<a name="' . $this->leftfile . '"></a>'
      }
      if ($matches[2][0] == 'b' && ) {
      $this->printFullLineHtml($line);
    }
    else*/if (preg_match('!--- a/(.*)!', $line, $matches)) {
      $this->leftfile = $matches[1];
    }
    elseif ('--- /dev/null' == $line) {
      $this->leftfile = '/dev/null';
    }
    elseif (preg_match('!\+\+\+ b/(.*)!', $line, $matches)) {
      $this->rightfile = $matches[1];
      return false;
    }
    elseif ('+++ /dev/null' == $line) {
      $this->rightfile = '/dev/null';
      return false;
    }
    else {
      $this->printFullLine($line);
    }
    return true;
  }

  public function renderDiff() {
    $command = $this->getDiffCommand();
    // use the diff --stat as a TOC
    $stat = htmlspecialchars(`$command --stat`);
    echo "<pre>$stat</pre>\n";
    // the diff itself
    $diff = `$command`;
    $lines = preg_split('/[\r\n]+/', $diff);
    if ($lines[count($lines) - 1] == '') array_pop($lines);
    $intro = true;
    $leftline = 0;
    $rightline = 0;
    echo '<table class="diff">';
    echo '<col class="nums" /><col class="left" /><col class="nums" /><col class="right" />';
    foreach ($lines as $line) {
      if ($intro) {
        $intro = $this->printIntroLine($line);
        continue;
      }
      $char = $line[0];
      $codeline = substr($line, 1);
      switch ($char) {
        case '\\': {
          // "\ No newline at end of file"
          // Count as a delete
          //$this->printLine('del', '\\' . $codeline, null);
          $this->deleted_buffer[count($this->deleted_buffer) - 1] .= "\n\\" . $codeline;
          break;
        }
        case '-': {
          $this->deleted_buffer[] = $codeline;
          break;
        }
        case '+': {
          if (!empty($this->deleted_buffer)) {
            $old = array_shift($this->deleted_buffer);
            $this->printLine('mod', $old, $codeline);
          }
          else {
            $this->printLine('ins', null, $codeline);
          }
          break;
        }
        case ' ': {
          $this->flushDeleteBuffer();
          // not changed - same in both columns
          $this->printLine('context', $codeline, $codeline);
          break;
        }
        case '@': {
          if (preg_match('/^@@\s*-(\d+),(\d+)\s*\+(\d+),(\d+)\s*@@/', $line, $m)) {
            if ($this->lineleft) {
              $this->printFullLine('...');
            }
            $this->lineleft   = $m[1];
            $this->lineright  = $m[3];
            if ($this->useBlame) {
              $blameleft = `git blame -L {$m[1]},+{$m[2]} {$this->from} {$this->leftfile} 2>&1`;
              $this->blameleft = $this->blame_by_line($blameleft);
              $blameright = `git blame -L {$m[3]},+{$m[4]} {$this->to} {$this->rightfile} 2>&1`;
              $this->blameright = $this->blame_by_line($blameright);
              if (substr($blameleft, 0, 6) == 'fatal:') {
                $this->printFullLine('git blame: ' . $blameleft . " (git blame -L {$m[1]},+{$m[2]} {$this->from} {$this->leftfile})");
              }
              if (substr($blameright, 0, 6) == 'fatal:') {
                $this->printFullLine('git blame: ' . $blameright . " (git blame -L {$m[3]},+{$m[4]} {$this->to} {$this->rightfile})");
              }
            }
            break;
          }
          // else fallthru
        }
        default: {
          $this->flushDeleteBuffer();
          echo '</table>';
          echo '<table class="diff">';
          echo '<col class="nums" /><col class="left" /><col class="nums" /><col class="right" />';
          $intro = true;
          $this->lineleft = $this->lineright = 0;
          $this->printIntroLine($line);
          break;
        }
      }
    }
    $this->flushDeleteBuffer();
    echo "</table>";
  }

  private function flushDeleteBuffer() {
    // flush del buffer
    if (!empty($this->deleted_buffer)) {
      foreach ($this->deleted_buffer as $del) {
        $this->printLine('del', $del, null);
      }
      $this->deleted_buffer = array();
    }
  }

  private function blame_by_line($blame_output) {
    $blame_by_line = array();
    if (preg_match_all(
      '/^(\^?[0-9a-f]+ )\((.*? \d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d \+\d+)\s+(\d+)\)/m',
      $blame_output,
      $ms,
      PREG_SET_ORDER
    )) {
      foreach ($ms as $m) {
        $blame_by_line[$m[3]] = $m[1] . $m[2];
      }
    }
    //$this->printFullLine(print_r($blame_by_line, true));
    return $blame_by_line;
  }
}

