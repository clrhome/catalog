<?
namespace ClrHome;

define('ClrHome\EMPTY_MESSAGE', 'Double-click to edit');

include(__DIR__ . '/lib/cleverly/Cleverly.class.php');
include(__DIR__ . '/src/classes/Catalog.class.php');

function u_parse($table, $prefix) {
	global $ns;
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
						$dl .= "<dt>$s->localName</dt><dd>" . ($s->nodeValue ? htmlentities($s->nodeValue, null, 'UTF-8') : EMPTY_MESSAGE) . '</dd>';

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

$language = @$_GET['lang'] ?: 'basic';
$catalog = new Catalog($language);
$first_byte = is_numeric(@$_GET['i']) ? (int)$_GET['i'] : null;
$second_byte = is_numeric(@$_GET['j']) ? (int)$_GET['j'] : null;

$rss = new \DOMDocument;
$rss->load('src/catalog.xml');
$nss = array('' => $rss->lookupNamespaceURI(null), 'axe' => $rss->lookupNamespaceURI('axe'), 'grammer' => $rss->lookupNamespaceURI('grammer'));
$ns = $nss[$_GET['lang']];

if ($_GET['alt']) {
	$pretty = filter_var($_GET['prettyprint'], FILTER_VALIDATE_BOOLEAN);

	switch ($_GET['alt']) {
		case 'json':
			header('Content-Type: application/json; charset=utf-8');
			die($catalog->toJson($first_byte, $second_byte, $pretty));
		case 'xml':
			header('Content-Type: text/xml; charset=utf-8');
			die($catalog->toXml($first_byte, $second_byte, $pretty));
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
				$r->item(0)->nodeValue = htmlspecialchars($value == EMPTY_MESSAGE ? '' : $value);
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

if ($first_byte !== null) {
	header(sprintf(
		"Location: /catalog/$language#t%s%s",
		str_pad(dechex($first_byte), 2, '0', STR_PAD_LEFT),
		$second_byte !== null
			? str_pad(dechex($second_byte), 2, '0', STR_PAD_LEFT)
			: ''
	));

	die();
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/src/templates');

$cleverly->display('index.tpl', [
	'editable' => true,
	'emptyMessage' => EMPTY_MESSAGE,
	'gallery' => u_parse($rss->firstChild, 't'),
	'lang' => $_GET['lang'] ?: 'basic'
]);
?>
