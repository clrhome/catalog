<?
namespace ClrHome;

include(__DIR__ . '/lib/cleverly/Cleverly.class.php');
include(__DIR__ . '/src/classes/Catalog.class.php');

$language = @$_GET['lang'] ?: 'basic';
$catalog = new Catalog($language);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$first_byte = is_numeric(@$_POST['i']) ? (int)$_POST['i'] : null;
	$second_byte = is_numeric(@$_POST['j']) ? (int)$_POST['j'] : null;
	$key = '';
	$sanitized_value = '';

	if ($first_byte !== null) {
		foreach ($_POST as $key => $value) {
			try {
				$sanitized_value =
						str_replace(array("\r\n", "\n", "\r"), ' ', trim($value));

				$catalog->setElement($first_byte, $second_byte, $key, htmlspecialchars(
					$sanitized_value == CatalogTokenField::EMPTY_MESSAGE
						? ''
						: $sanitized_value
				));
			} catch (\OutOfRangeException $exception) {}
		}

		$catalog->save();
	}

	die(CatalogTokenField::formatValue($key, $sanitized_value));
}

$first_byte = is_numeric(@$_GET['i']) ? (int)$_GET['i'] : null;
$second_byte = is_numeric(@$_GET['j']) ? (int)$_GET['j'] : null;

if (array_key_exists('alt', $_GET)) {
	$html = filter_var($_GET['html'], FILTER_VALIDATE_BOOLEAN);
	$pretty = filter_var($_GET['prettyprint'], FILTER_VALIDATE_BOOLEAN);

	switch ($_GET['alt']) {
		case 'json':
			header('Content-Type: application/json; charset=utf-8');
			die($catalog->toJson($first_byte, $second_byte, $pretty, $html));
		case 'xml':
			header('Content-Type: text/xml; charset=utf-8');
			die($catalog->toXml($first_byte, $second_byte, $pretty, $html));
	}
}

if ($first_byte !== null) {
	header(sprintf(
		"Location: /catalog/$language#t%s%s",
		CatalogTable::formatByte($first_byte),
		$second_byte !== null ? CatalogTable::formatByte($second_byte) : ''
	));

	die();
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/src/templates');

$cleverly->display('index.tpl', [
	'editable' => true,
	'emptyMessage' => CatalogTokenField::EMPTY_MESSAGE,
	'language' => $language,
	'element' => $catalog->toTable()
]);
?>
