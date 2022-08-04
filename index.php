<?
include(__DIR__ . '/lib/cleverly/Cleverly.class.php');

function rep1($match) {
	return $match[2] == $_GET['lang'] . ':' ? $match[0] : '';
}

function rep2($match) {
	global $pretty, $space;
	preg_match('#^\n\s*#', $match[2], $spaces);
	preg_match_all('# (([-\w]+:)?[-\w]+)(="(.*?)")#', $match[1], $matches, PREG_SET_ORDER);

	if ($pretty and preg_match('#^\s*$#', $match[2]))
		$spaces[0] .= '	';

	foreach ($matches as $submatch)
		if ($submatch[2] != 'xmlns:' and $submatch[1] != 'xmlns')
			$match[2] = $spaces[0] . "\"$submatch[1]\":$space\"$submatch[4]\"," . $match[2];

	return '{' . preg_replace_callback('#<(.*?)(>(.*?)</\1| ?/)>#', 'rep3', $match[2]) . '},';
}

function rep3($match) {
	global $space;
	return "\"$match[1]\":$space\"" . str_replace('"', '\"', $match[3]) . '",';
}

function u_parse($table, $prefix) {
	global $empty, $ns, $nss;
	$rss = $table->childNodes;
	$table = array(array(), array(), array());
	$keys = '<div class="keys">';
	$values = '</div><div class="values"><div></div>';

	foreach ($rss as $rs => $r) {
		$tid = $prefix . str_pad(dechex($rs), 2, '0', STR_PAD_LEFT);

		if ($r->nodeName == 'table') {
			$has = u_parse($r, $tid);

			if ($has != "$keys$values</div>")
				$table[0][] = array("<a class=\"table\" href=\"#$tid\">" . ($r->getAttribute('name') ? $r->getAttribute('name') : strtoupper(dechex($rs)) . ' tokens') . '</a>', "<div id=\"$tid\">" . u_parse($r, $tid) . '</div>');
		} else {
			$id = $r->getAttributeNS($ns, 'id');

			if (!$id)
				$id = $r->getAttributeNS(null, 'id');

			if (strlen($id)) {
				$sid = preg_replace('#[^a-z]#i', '', $id);
				$dl = '<dl>';
				$ss = $r->childNodes;
				$has = false;

				foreach ($ss as $s) {
					if ($s->namespaceURI == $ns or $s->nodeName == 'keys') {
						$dl .= "<dt>$s->localName</dt><dd>" . ($s->nodeValue ? htmlentities($s->nodeValue, null, 'UTF-8') : $empty) . '</dd>';

						if ($s->nodeName != 'keys')
							$has = true;
					}
				}

				if ($has)
					$table[$sid ? 1 : 2][$sid ? strtoupper($sid) . $rs : $id] = array("<a href=\"#$tid\">" . htmlentities($id, null, 'UTF-8') . '</a>', "<div id=\"$tid\">$dl</dl></div>");
			}
		}
	}

	ksort($table[1]);
	ksort($table[2]);

	foreach ($table as $tr) {
		foreach ($tr as $td) {
			$keys .= $td[0];
			$values .= $td[1];
		}
	}

	return "$keys$values</div>";
}

$empty = 'Double-click to edit';
$rss = new DOMDocument;
$rss->load('catalog.xml');
$nss = array('' => $rss->lookupNamespaceURI(null), 'axe' => $rss->lookupNamespaceURI('axe'), 'grammer' => $rss->lookupNamespaceURI('grammer'));
$ns = $nss[$_GET['lang']];

if ($_GET['alt']) {
	$rss->formatOutput = $pretty = filter_var($_GET['prettyprint'], FILTER_VALIDATE_BOOLEAN);
	$rs = $rss->firstChild;

	if (is_numeric($_GET['i']))
		$rs = $rs->childNodes->item($_GET['i']);

	if (is_numeric($_GET['j'])) {
		$rs = $rs->childNodes->item($_GET['j']);

		if ($rs->nodeName != 'token') {
			header('Location: ../?alt=' . $_GET['alt'] . (isset($_GET['prettyprint']) ? '&prettyprint=' . $_GET['prettyprint'] : ''));
			die();
		}
	}

	foreach ($nss as $prefix => $uri)
		@$rs->setAttribute($prefix ? 'xmlns:' . $prefix : 'xmlns', $uri);

	$rss = $rss->saveXML($rs);

	if ($pretty)
		$rss = str_replace(array('  ', '/>'), array('	', ' />'), $rss);

	if ($_GET['lang'])
		$rss = preg_replace_callback('#<(([-\w]+:)?(syntax|description)(-\w)*)(>.*?</\1| ?/)>#', 'rep1', $rss);
	else
		$rss = preg_replace('#<([-\w]+:[-\w]+)(>.*?</\1| ?/)>#', '', $rss);

	$rss = preg_replace('#\n\s*\n#', '
', $rss);
	$space = $pretty ? ' ' : '';

	switch ($_GET['alt']) {
		case 'json':
			header('Content-Type: application/json; charset=utf-8');
			die(str_replace(array('&lt;', '&gt;', '&amp;'), array('<', '>', '&'), preg_replace('#,(\s*([\]\}]|$))#', '$1', preg_replace_callback('#<token(.*?)>(.*?)</token>#s', 'rep2', preg_replace('#<table.*?>#', '[', str_replace(array($pretty ? '<table />' : '<table/>', '</table>', $pretty ? '<token />' : '<token/>'), array('[],', '],', '{},'), $rss))))));
		case 'xml':
			header('Content-Type: text/xml; charset=utf-8');
			die(preg_replace('#<(token[^>]*)>\s*</token>#', "<$1$space/>", '<?xml version="1.0" encoding="UTF-8"?>
' . $rss));
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (is_numeric($_POST['i'])) {
		$rs = $rss->firstChild->childNodes->item($_POST['i']);

		if (is_numeric($_POST['j']))
			$rs = $rs->childNodes->item($_POST['j']);

		foreach ($_POST as $key => $value) {
			if ($r = $rs->getElementsByTagNameNS($ns, $key) and $r->length) {
				$value = str_replace(array("\r\n", "\n", "\r"), ' ', trim($value));
				$r->item(0)->nodeValue = htmlspecialchars($value == $empty ? '' : $value);
				file_put_contents('catalog.xml', $rss->saveXML(), LOCK_EX);
				file_put_contents('log.txt', "Value $key of $_POST[i]" . ($_POST['j'] ? ',' . $_POST['j'] : '') . ' (' . $rs->getAttribute('id') . ") for $_GET[l] changed to
	$value

", FILE_APPEND);
			} else {
				$value = '';
			}
		}
	}

	die($value);
}

if (is_numeric($_GET['i'])) {
	header('Location: /catalog/' . ($_GET['lang'] ? $_GET['lang'] . '/' : '') . '#t' . bin2hex(chr($_GET['i']) . (is_numeric($_GET['j']) ? chr($_GET['j']) : '')));
	die();
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/src/templates');

$cleverly->display('index.tpl', [
	'editable' => true,
	'emptyMessage' => $empty,
	'gallery' => u_parse($rss->firstChild, 't'),
	'lang' => $_GET['lang'] ?: 'basic'
]);
?>
