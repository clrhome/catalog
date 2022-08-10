<?php
namespace ClrHome;

final class Catalog {
  const CATALOG_FILE = __DIR__ . '/../catalog.xml';
  const LANGUAGE_BASIC = 'basic';
  const NAMESPACES =
      array('axe' => 'axe', 'basic' => null, 'grammer' => 'grammer');

  private \DOMDocument $catalog;
  private string $language;
  private array $namespaceUris;

  public function __construct(string $language) {
    if (!array_key_exists($language, self::NAMESPACES)) {
      throw new \OutOfRangeException("Invalid language $language");
    }

    $this->language = $language;
    $this->catalog = new \DOMDocument();
    $this->catalog->load(self::CATALOG_FILE);

    $this->namespaceUris = array(
      'axe' => $this->catalog->lookupNamespaceURI('axe'),
      'basic' => $this->catalog->lookupNamespaceURI(null),
      'grammer' => $this->catalog->lookupNamespaceURI('grammer')
    );
  }

  public function toJson(
    int|null $first_byte,
    int|null $second_byte,
    bool $pretty
  ) {
  	$space = $pretty ? ' ' : '';

    return str_replace(
      array('&lt;', '&gt;', '&amp;'),
      array('<', '>', '&'),
      preg_replace(
        '/,(\s*([\]\}]|$))/',
        '$1',
        preg_replace_callback(
          '/<token(.*?)>(.*?)<\/token>/s',
          function($token_matches) use ($pretty, $space) {
          	preg_match('/^\n\s*/', $token_matches[2], $space_matches);

          	preg_match_all(
              '/ (([-\w]+)(:[-\w]+)?)=(".*?")/',
              $token_matches[1],
              $attribute_matches,
              PREG_SET_ORDER
            );

            $token_content = $token_matches[2];
          	$indent = $pretty && preg_match('/^\s*$/', $token_content)
              ? @"$space_matches[0]  "
              : @$space_matches[0];

          	foreach ($attribute_matches as $attribute_match) {
          		if (
                $attribute_match[2] !== 'xmlns:' &&
                    $attribute_match[1] !== 'xmlns'
              ) {
          			$token_content =
                    "$indent\"$attribute_match[1]\":$space$attribute_match[4],$token_content";
              }
            }

          	return sprintf("{%s}", preg_replace_callback(
              '/<(.*?)(>(.*?)<\/\1| ?\/)>/',
              function($tag_matches) use ($space) {
              	return "\"$tag_matches[1]\":$space\"" .
                    str_replace('"', '\"', @$tag_matches[3]) . '",';
              }, $token_content
            ));
          },
          preg_replace(
            '/<table.*?>/',
            '[',
            str_replace(
              array('<table/>', '</table>', '<token/>'),
              array('[],', '],', '{},'),
              $this->toHeadlessXml($first_byte, $second_byte, $pretty)
            )
          )
        )
      )
    );
  }

  public function toXml(
    int|null $first_byte,
    int|null $second_byte,
    bool $pretty
  ) {
    $space = $pretty ? ' ' : '';

    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . str_replace(
      '/>',
      "$space/>",
      preg_replace(
        '/<(token[^>]*)>\s*<\/token>/',
        "<$1/>",
        $this->toHeadlessXml($first_byte, $second_byte, $pretty)
      )
    );
  }

  private function toHeadlessXml(
    int|null $first_byte,
    int|null $second_byte,
    bool $pretty
  ) {
  	$this->catalog->formatOutput = $pretty;
  	$node = $this->catalog->firstChild;

    if ($first_byte !== null) {
  		$node = $node->childNodes->item($first_byte);
    }

  	if ($second_byte !== null) {
  		$node = $node->childNodes->item($second_byte);

  		if ($node->nodeName !== 'token') {
  			throw new \UnexpectedValueException('Exported node is not a token');
  		}
  	}

  	foreach ($this->namespaceUris as $namespace => $uri) {
  		$node->setAttribute(
        $namespace === self::LANGUAGE_BASIC ? 'xmlns' : 'xmlns:' . $namespace,
        $uri
      );
    }

  	$headless_xml = $this->catalog->saveXML($node);

  	if ($this->language === self::LANGUAGE_BASIC) {
      $headless_xml = preg_replace(
        '/<([-\w]+:[-\w]+)(>.*?<\/\1| ?\/)>/',
        '',
        $headless_xml
      );
    } else {
  		$headless_xml = preg_replace_callback(
        '/<(([-\w]+:)?(syntax|description)(-\w)*)(>.*?<\/\1| ?\/)>/',
        function($matches) {
        	return $matches[2] === $this->language . ':' ? $matches[0] : '';
        },
        $headless_xml
      );
    }

  	return preg_replace('/^\s*\n/m', '', $headless_xml);
  }
}
?>
