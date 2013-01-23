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
$git = empty($_GET['git']) ? 'status' : $_GET['git'];

?>
<!DOCTYPE html>
<html>
  <head>
    <title>git <?= $git . ' @ ' . htmlspecialchars($repo) ?> - minigitweb</title>
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
        <li><a href="?git=status">status</a></li>
        <li><a href="?git=log">log</a></li>
        <li><a href="?git=diff">diff</a></li>
        <li><a href="?git=branch">branch</a></li>
        <li><a href="?git=tag">tag</a></li>
        <li><a href="?git=stash">stash</a></li>
        <li><a href="?git=help">help</a></li>
      </ul>
    </div>
    <?
    switch ($git) {
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
        echo '<input type="submit" name="git" value="diff" /> ';
        if ($pagination) {
          echo '<a href="?git=log&amp;n=10">1&hellip;10</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=10">11&hellip;20</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=20">21&hellip;30</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=30">31&hellip;40</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=40">41&hellip;50</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=50">51&hellip;60</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=60">61&hellip;70</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=70">71&hellip;80</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=80">81&hellip;90</a> ';
          echo '<a href="?git=log&amp;n=10&amp;skip=90">91&hellip;100</a> ';
          echo '<a href="?git=log&amp;n=0&amp;skip=100">101&hellip;&infin;</a> ';
          echo '<a href="?git=log&amp;n=0">1&hellip;&infin;</a> ';
        }
        echo '</div>';
        $log = `$cmd 2>&1`;
        $log = preg_replace(
          '/^commit (\S+)$/m',
          "commit <a href=\"?git=show&amp;commit=$1\">$1</a>"
          . ' <label><input type="checkbox" class="check" name="commit[]" value="$1" /></label>',
          htmlspecialchars($log)
        );
        echo "<pre>$log</pre>\n";
        echo "</form>\n";
        echo "</div>";
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
          echo '<input type="submit" name="git" value="diff" />';
          echo '<input type="submit" name="git" value="log" />';
          echo '<input type="submit" name="git" value="cherry" />';
          echo '</div>';
          echo '<table>';
          echo '<tr><th></th><th>from</th><th>to</th></tr>';
          $i = 0;
          foreach ($rows as $row) {
            if (preg_match('/^(.) (.*?)(\S+)$/', $row, $matches)) {
              $name = $matches[3];
              $row = "{$matches[2]}{$matches[3]}";
              $current = ($matches[1] == '*');
              $row = "<a href=\"?git=log&amp;commit=$name\">$row</a>";
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
          '<a href="?git=diff&amp;commit[]=$0">$0</a>',
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
          '$1 <a href="?git=show&amp;commit=$2">$2</a>',
          $cherry
        );
        echo "<pre>$cherry</pre>";
        echo '</div>';
        break;
      }
      case 'show': // Fallthru: "diff" is "show" when called with exactly one commit.
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
            $from = $to = null;
            break;
          }
          case 1: {
            $from = $commits[0];
            $to = null;
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
            '<a href="?git=help&amp;help=$1">git-$1(1)</a>',
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
            '   <a href="?git=help&amp;help=$1">$1</a>   ',
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
    if ($this->from && $this->to) return "git diff {$this->from}..{$this->to}";
    if ($this->from) return "git show {$this->from}";
    return "git diff HEAD";
  }

  /**
   * Returns true if this is the last intro line, true if the next line will also be an intro line.
   */
  private function printIntroLine($line) {
    //$this->printFullLineHtml(json_encode($line));
    if (preg_match('!^diff --git (\S+) (\S+)$!', $line, $matches)) {
      // Add a <span> with an id, to use as anchor link target
      list (, $file_a, $file_b) = $matches;
      if (preg_match('!^a/(.*)$!', $file_a)) {
        $filename = htmlspecialchars(substr($file_a, 2));
        $file_a = 'a/<span id="' . $filename . '">' . $filename . '</span>';
      }
      elseif (preg_match('!^b/(.*)$!', $file_b)) {
        $filename = htmlspecialchars(substr($file_b, 2));
        $file_b = 'b/<span id="' . $filename . '">' . $filename . '</span>';
      }
      $this->printFullLineHtml("git --diff $file_a $file_b");
    }
    elseif (preg_match('!--- a/(.*)!', $line, $matches)) {
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
    $stat = htmlspecialchars(`$command --stat=300,100`);

    // the diff itself
    // (for 'git show' use a format to show the diff only)
    if (preg_match('/^git show/', $command)) $command .= ' --pretty=format:%b';
    $diff = `$command`;

    // If it is a merge, the diff is empty.
    if (preg_match('!^\s*$!', $diff)) {
      // Print stat header and return
      echo "<pre>$stat</pre>\n";
      return;
    }

    // Add anchor links down to the diff
    $stat = preg_replace('!^( )(\S+)(\s+\|\s+\d+ \+*\-*)$!m', '$1<a href="#$2">$2</a>$3', $stat);

    // Print stat header
    echo "<pre>$stat</pre>\n";

    // Start processing the diff
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

