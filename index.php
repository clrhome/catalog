<?
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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Token reference - ClrHome</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<style type="text/css">
			body {
				margin: 0;
				font: 16px Arial, Helvetica, sans-serif;
				background: #123;
			}
			body *::-webkit-scrollbar {
				width: 0.6em;
			}
			body *::-webkit-scrollbar:hover {
				border: 1px solid rgba(255, 255, 255, 0.2);
				background: rgba(255, 255, 255, 0.1);
			}
			body *::-webkit-scrollbar:active {
				border: 1px solid rgba(255, 255, 255, 0.3);
				background: rgba(255, 255, 255, 0.2);
			}
			body *::-webkit-scrollbar-thumb {
				background: rgba(255, 255, 255, 0);
			}
			body *:hover::-webkit-scrollbar-thumb {
				background: rgba(255, 255, 255, 0.4);
			}
			body *::-webkit-scrollbar-thumb:hover {
				background: rgba(255, 255, 255, 0.8);
			}
			body *::-webkit-scrollbar-thumb:active {
				background: rgba(255, 255, 255, 0.9);
			}
			body > div {
				padding: 2em 0;
			}
			body > div > div {
				margin: 0 5%;
			}
			#top, #bottom {
				-webkit-box-shadow: 0 0 0.5em #000;
				-moz-box-shadow: 0 0 0.5em #000;
				box-shadow: 0 0 0.5em #000;
				background: #012;
				color: #678;
			}
			#top {
				text-align: right;
			}
			input {
				display: block;
				width: 89%;
				margin: auto;
				margin-top: 1em;
				border: 0.07em solid #455;
				border-radius: 0.15em;
				padding: 0.1em 0.2em;
				background: #234;
				color: #343;
				outline: 0;
				font-size: 1.4em;
			}
			input:focus {
				background: #eee;
			}
			input.green:focus {
				background: #9c9;
			}
			input.red:focus {
				background: #c99;
			}
			.gallery {
				overflow: auto;
				border: 0.1em solid #455;
				border-radius: 0.5em;
				background: #566;
			}
			.gallery::-webkit-scrollbar, .gallery::-webkit-scrollbar-thumb {
				border-radius: 0 0 0.5em 0.5em;
			}
			.gallery > div {
				min-width: 48em;
			}
			.keys, .values, .values div {
				height: 20em;
			}
			.keys {
				width: 25%;
				float: left;
				overflow: auto;
				background: #899;
			}
			.values .keys {
				width: 33%;
				background: #9aa;
			}
			.keys a {
				display: block;
				border-bottom: 0.1em solid #677;
				padding: 0.5em 1em;
				font-size: 0.8em;
				font-weight: bold;
				text-decoration: none;
				color: #223;
				cursor: default;
			}
			.values .keys a {
				border-color: #788;
				color: #334;
			}
			.keys a:active, .keys a.active {
				background: #112;
				color: #bbd;
			}
			.values .keys a:active, .values .keys a.active {
				background: #223;
				color: #cce;
			}
			.keys .table:after {
				float: right;
				font-weight: normal;
			}
			.keys .table.active:after {
				content: '\25B6';
			}
			.keys .table:after, .keys .table:active:after {
				content: '\25B7';
			}
			.values {
				border-left: 0.1em solid #889;
				background: #def;
				color: #334;
			}
			.values, .values .values {
				overflow: hidden;
			}
			.values div {
				overflow: auto;
			}
			.values dl {
				margin: 1em;
			}
			.values dt {
				margin: 1em 0;
				font-weight: bold;
				text-transform: uppercase;
			}
			.values dd {
				margin: 1em 0;
				padding: 0;
			}
			textarea {
				width: 100%;
				height: 4em;
				margin: 0;
				border: 0;
				padding: 0;
				background-color: transparent;
				font: 1em Arial, Helvetica, sans-serif;
			}
			#top > a {
				float: left;
				margin-left: 5%;
			}
			#top > a img {
				max-width: 100%;
			}
			#top div {
				float: right;
				margin-left: 0;
			}
			#top div a {
				display: inline-block;
				margin: 1em 1em 0 0;
				border: 1px dotted #001;
				border-radius: 0.3em;
				padding: 0.5em 1em;
				color: #9ab;
				font-weight: bold;
				text-decoration: none;
			}
			#top div a:hover {
				border-style: solid;
				background: #123;
				color: #ace;
			}
			#top div a:active {
				background: #001;
				color: #888;
			}
			#top div a.active {
				background: #000;
				color: #eee;
				cursor: default;
			}
			#bottom div {
				font-size: 0.8em;
			}
			#bottom a {
				color: #678;
			}
			#bottom a:hover {
				color: #9ab;
			}
			#bottom > a {
				float: right;
				margin: 0 5%;
				opacity: 0.1;
				-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=10)';
				filter: alpha(opacity=10);
				-webkit-transition: all 200ms;
				-o-transition: all 200ms;
				-ms-transition: all 200ms;
				-moz-transition: all 200ms;
				transition: all 200ms;
			}
			#bottom > a:hover {
				opacity: 0.4;
				-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=40)';
				filter: alpha(opacity=40);
			}
			#bottom > a img {
				width: 10em;
				border: 0;
			}
			cite {
				display: block;
				clear: both;
			}
			a.hidden, div.hidden {
				display: none;
			}
		</style>
		<style type="text/css" media="screen and (max-width: 479px)">
			body {
				font-size: 12px;
			}
		</style>
		<script type="text/javascript" src="/lib/js/jquery.js"></script>
		<script type="text/javascript" src="/lib/js/ga.js"></script>
		<script type="text/javascript">// <![CDATA[
			function ohc() {
				if (!window.location.hash || window.location.hash == '#') {
					$('.keys a.active').removeClass('active');
					$('.values').first().scrollTop(0);
				} else {
					var e = $('[href=' + window.location.hash + ']');

					if (!e.hasClass('active'))
						e.mousedown();
				}
			}

			function t(e, f) {
				var i = 0;

				$(e).children('a').each(function() {
					var g = $($(this).attr('href'));
					var h = g.children('.keys');
					h = h.length ? t(h, f) : $(this).text().toUpperCase().indexOf(f) + 1;
					g.add(this).toggleClass('hidden', !Boolean(h));
					i += h;
				});

				return i;
			}

			$(function() {
				u = 0;

				$('.keys a').mousedown(function() {
					var e = $(this).attr('href');
					$('.values .values').scrollTop(0);
					$(this).add('[href=' + e.slice(0, 4) + ']').focus().blur().siblings().add('.values .keys a.active').removeClass('active').end().end().addClass('active');
					window.location.href = e;
					var f = $(e);
					$('.gallery').scrollLeft($('.gallery').scrollLeft() + f.position().left - parseInt($('.gallery').css('margin-left')));

					if (f.children('dl').length) {
						$.get(parseInt(e.slice(2, 4), 16) + (e.length > 4 ? '/' + parseInt(e.slice(4, 6), 16) : '') + '/?alt=json&' + new Date().getTime(), function(e, f, g) {
							g.target.children().children('dt').each(function() {
								var k = $(this).html().toLowerCase();

								if (k in e)
									$(this).next().text(e[k] ? e[k] : '<?
echo $empty;
?>');
							});
						}).target = f;
					}
				}).click(false);
<?
// if ($_SESSION['TRUST'])
	echo "
				$('.values dd').dblclick(function() {
					$(this).wrapInner('<textarea />').children().focus().select().blur(function() {
						var e = $('.keys a.active').last().attr('href');
						b = $(this);
						var k = {i: parseInt(e.slice(2, 4), 16), j: parseInt(e.slice(4, 6), 16)};
						k[$(this).parent().prev().html()] = $(this).val();

						$.post('./', k, function(e) {
							b.parent().text(e ? e : '$empty');
						});
					}).dblclick(false);
				});
";
?>
				$(document).keydown(function(e) {
					if ($('textarea').length) {
						if (e.keyCode == 9 || e.keyCode == 13 || e.keyCode == 27) {
							$('textarea').blur();
							return false;
						}

						return true;
					}

					var f = $('.keys a.active');

					switch (e.keyCode) {
						case 27:
						case 37:
							if (f.length == 2) {
								f.first().mousedown();
								return false;
							}

							window.location.hash = '';
							return false;
						case 40:
						case 74:
							if (f.length) {
								f = f.last();
								var g = f.nextUntil(':not(.hidden)');
								f = g.length ? g.last().next() : f.next();

								if (f.length)
									f.mousedown();

								return false;
							}
						case 13:
						case 39:
							if (!f.length) {
								$('.keys a:not(.hidden)').first().mousedown();
								return false;
							}

							$(f.attr('href') + ' .keys a:not(.hidden)').first().mousedown();
							return false;
						case 38:
						case 75:
							if (!f.length) {
								$('.keys').first().children('a:not(.hidden)').last().mousedown();
								return false;
							}

							f = f.last();
							var g = f.prevUntil(':not(.hidden)');
							f = g.length ? g.last().prev() : f.prev();

							if (f.length)
								f.mousedown();

							return false;
						case 191:
							$('input').focus().select();
							return false;
					}
				}).bind('touchstart', function(){});

				$('input').attr('placeholder', 'type here to search').keydown(function(e) {
					e.stopPropagation();

					if (e.keyCode == 27)
						$(this).blur();
				}).keyup(function() {
					clearTimeout(u);

					u = setTimeout(function() {
						var e = $('input').removeClass('red green').val().toUpperCase();
						window.location.hash = '';

						if (e.length)
							$('input').addClass(t('.gallery > div > .keys', e) ? 'green' : 'red');
						else
							$('.hidden').removeClass('hidden');
					}, 100);
				});

				window.onhashchange = ohc;
				ohc();
			});
		// ]]></script>
	</head>
	<body>
		<div id="top">
			<a>
				<img src="/catalog/bar.png" alt="The Catalog: Online Token Reference" />
			</a>
			<div>
				<a href="/catalog/"<?
if (!$_GET['lang'])
	echo ' class="active"';
?>>TI-BASIC</a>
				<a href="/catalog/axe/"<?
if ($_GET['lang'] == 'axe')
	echo ' class="active"';
?>>Axe</a>
				<a href="/catalog/grammer/"<?
if ($_GET['lang'] == 'grammer')
	echo ' class="active"';
?>>Grammer</a>
			</div>
			<cite></cite>
		</div>
		<div id="middle">
			<div class="gallery">
				<div>
					<?
echo u_parse($rss->firstChild, 't') . '
';
?>				</div>
			</div>
			<input type="text" />
		</div>
		<div id="bottom">
			<a href="/">
				<img src="/images/emblem.png" alt="ClrHome" />
			</a>
			<div><?
switch ($_GET['lang']) {
	case '':
		echo 'Guidebook used with permission from TI. <a href="http://education.ti.com/">http://education.ti.com/</a>';
		break;
	case 'axe':
		echo 'Axe Parser Commands List used with permission from Quigibo.';
		break;
	case 'grammer':
		echo 'Grammer Commands List used with permission from Xeda Elnara.';
}
?><br />Other content may be user-contributed.<br />ClrHome makes no guarantees of accuracy of the information present on this page.<br />Please <a href="/?action=register">log in</a> or <a href="/?action=register">register</a> to contribute to this wiki.</div>
			<cite></cite>
		</div>
	</body>
</html>
