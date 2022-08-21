<?php
namespace ClrHome;

final class CatalogTokenField {
  const EMPTY_MESSAGE = 'Double-click to edit';
  const FIELD_KEYS = 'keys';

  public string $key;
  public string $value;

  public function __construct(string $key, string $value) {
    $this->key = $key;
    $this->value = self::formatValue($key, $value);
  }

  public static function formatValue(string $key, string $value) {
    $encoded_value =
        htmlentities($value, null, 'UTF-8') ?: self::EMPTY_MESSAGE;
    return $key === self::FIELD_KEYS
      ? preg_replace(
        array(
          '/^\(2nd\) (\[[^\]]+\])/',
          '/^\(ALPHA\) (\[[^\]]+\])/',
          '/^\([^\)]+\)/',
          '/> ([^<]+)/',
        ),
        array(
          '<kbd class="second">(2nd)</kbd> <mark>$1</mark>',
          '<kbd class="alpha">(ALPHA)</kbd> <mark>$1</mark>',
          '<kbd>$0</kbd>',
          '> <samp>$1</samp>'
        ),
        $encoded_value
      )
      : preg_replace('/&lt;\w+&gt;/', '<var>$0</var>', $encoded_value);
  }
}

final class CatalogToken {
  public string $bytes;
  public array $fields;
  private string $id;
  public string $idSafe;

  public function __construct(
    string $bytes,
    string $namespace,
    \DOMElement $node
  ) {
    $this->bytes = $bytes;
    $this->id = $node->getAttributeNS($namespace, 'id') ?:
        $node->getAttributeNS(null, 'id');
    $this->idSafe = htmlentities($this->id, null, 'UTF-8');
    $this->fields = [];

    foreach ($node->childNodes as $field_node) {
      if (
        $field_node->namespaceURI === $namespace ||
            $field_node === CatalogTokenField::FIELD_KEYS
      ) {
        array_push($this->fields, new CatalogTokenField(
          $field_node->localName,
          $field_node->nodeValue
        ));
      }
    }
  }

  public function compare(CatalogToken $other) {
    return strcasecmp(
      preg_replace('/[^A-Za-z]/', '', $this->id),
      preg_replace('/[^A-Za-z]/', '', $other->id)
    );
  }

  public function hasAlpha() {
    return preg_match('/[A-Za-z]/', $this->id);
  }
}

final class CatalogTable {
  public array $elements = [];
  public string $name;
  public string $prefix;
  private string $namespace;

  public function __construct(
    string $prefix,
    string $namespace,
    \DOMElement $node
  ) {
    $this->name = $node->getAttribute('name');
    $this->prefix = $prefix;
    $element_nodes = $node->childNodes;

    foreach ($node->childNodes as $index => $element_node) {
      $bytes = $prefix . self::formatByte($index);

      switch ($element_node->nodeName) {
        case 'table':
          array_push(
            $this->elements,
            new CatalogTable($bytes, $namespace, $element_node)
          );

          break;
        case 'token':
          $token = new CatalogToken($bytes, $namespace, $element_node);

          array_push($this->elements, $token);
          break;
        default:
          throw new \UnexpectedValueException(sprintf(
            'Unexpected %s at %s',
            $element_node->nodeName,
            $bytes
          ));
      }
    }

    usort($this->elements, function($a, $b) {
      if (
        get_class($a) === CatalogTable::class &&
            get_class($b) !== CatalogTable::class ||
            get_class($a) === CatalogToken::class &&
            get_class($b) === CatalogToken::class &&
            $a->hasAlpha() && !$b->hasAlpha()
      ) {
        return -1;
      }

      if (
        get_class($a) !== CatalogTable::class &&
            get_class($b) === CatalogTable::class ||
            get_class($a) === CatalogToken::class &&
            get_class($b) === CatalogToken::class &&
            !$a->hasAlpha() && $b->hasAlpha()
      ) {
        return 1;
      }

      return get_class($a) === CatalogToken::class ? $a->compare($b) : 0;
    });
  }

  public static function formatByte(int $byte) {
    return str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
  }
}
?>
