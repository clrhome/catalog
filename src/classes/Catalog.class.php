<?php
namespace ClrHome;

final class Catalog {
  const CATALOG_FILE = __DIR__ . '/../catalog.xml';
  const LANGUAGE_BASIC = 'basic';
  const LOG_FILE = __DIR__ . '/../../log.yaml';
  const NAMESPACES =
      array('axe' => 'axe', 'basic' => null, 'grammer' => 'grammer');

  private \DOMDocument $catalog;
  private string $language;
  private array $mutations = [];
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

  public static function formatByte(int $byte) {
    return str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
  }

  public function save() {
    file_put_contents(self::CATALOG_FILE, $this->catalog->saveXML(), LOCK_EX);

    foreach ($this->mutations as $mutation) {
      file_put_contents(self::LOG_FILE, $mutation->toYaml(), FILE_APPEND);
    }

    $this->mutations = [];
  }

  public function setElement(
    int $first_byte,
    int|null $second_byte,
    string $key,
    string $value
  ) {
    $token = $this->getNode($first_byte, $second_byte);

    $fields = $token->getElementsByTagNameNS(
      $this->namespaceUris[$this->language],
      $key
    );

    if ($fields->length === 0) {
      throw new \OutOfRangeException("Invalid token field $key");
    }

    $field = $fields->item(0);
    $old_value = $field->nodeValue;
    $field->nodeValue = $value;

    array_push($this->mutations, new Catalog\Mutation(
      $first_byte,
      $second_byte,
      $key,
      $old_value,
      $value
    ));
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

  private function getNode(int|null $first_byte, int|null $second_byte) {
    $node = $this->catalog->firstChild;

    if ($first_byte !== null) {
  		$node = $node->childNodes->item($first_byte);

      if ($node === null) {
        throw new \UnexpectedValueException(sprintf(
          'Unrecognized token %s',
          self::formatByte($first_byte)
        ));
      }
    }

  	if ($second_byte !== null) {
      if ($first_byte === null) {
        throw new \UnexpectedValueException(sprintf(
          'Second byte %s should not be set if first byte is null',
          self::formatByte($second_byte)
        ));
      }

  		$node = $node->childNodes->item($second_byte);

  		if ($node === null || $node->nodeName !== 'token') {
        throw new \UnexpectedValueException(sprintf(
          'Unrecognized token %s%s',
          self::formatByte($first_byte),
          self::formatByte($second_byte)
        ));
  		}
  	}

    return $node;
  }

  private function toHeadlessXml(
    int|null $first_byte,
    int|null $second_byte,
    bool $pretty
  ) {
  	$this->catalog->formatOutput = $pretty;
    $node = $this->getNode($first_byte, $second_byte);

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

namespace ClrHome\Catalog;

final class Mutation {
  private int $firstByte;
  private string $key;
  private string $newValue;
  private int|null $secondByte;
  private string $oldValue;

  public function __construct(
    int $first_byte,
    int|null $second_byte,
    string $key,
    string $old_value,
    string $new_value
  ) {
    $this->firstByte = $first_byte;
    $this->secondByte = $second_byte;
    $this->key = $key;
    $this->oldValue = $old_value;
    $this->newValue = $new_value;
  }

  public function toYaml() {
    $second_byte = $this->secondByte ?? 'null';
    $old_value = $this->oldValue ?? 'null';
    $new_value = $this->newValue ?? 'null';

    return <<<EOF
- firstByte: $this->firstByte
  secondByte: $second_byte
  key: $this->key
  oldValue: $old_value
  newValue: $new_value

EOF;
  }
}
?>
