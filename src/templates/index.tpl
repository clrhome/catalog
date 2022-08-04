<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Token reference - ClrHome</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link href="catalog.css" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="/lib/js/jquery.js"></script>
    <script type="text/javascript" src="/lib/js/ga.js"></script>
    <script type="text/javascript">// <![CDATA[
      const EMPTY_MESSAGE = '{$emptyMessage}';
{if $editable}
      $(function() {
        $('.values dd').dblclick(function() {
          console.log('hello');

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
      });
{/if}    // ]]></script>
    <script type="text/javascript" src="bin/js/catalog.js"></script>
  </head>
  <body>
    <div id="top">
      <a>
        <img src="/catalog/bar.png" alt="The Catalog: Online Token Reference" />
      </a>
      <div>
        <a href="/catalog/"{if $lang eq 'basic'} class="active"{/if}>TI-BASIC</a>
        <a href="/catalog/axe/"{if $lang eq 'axe'} class="active"{/if}>Axe</a>
        <a href="/catalog/grammer/"{if $lang eq 'grammer'} class="active"{/if}>Grammer</a>
      </div>
      <cite></cite>
    </div>
    <div id="middle">
      <div class="gallery">
        <div>
          {$gallery}
        </div>
      </div>
      <input type="text" />
    </div>
    <div id="bottom">
      <a href="/">
        <img src="/images/emblem.png" alt="ClrHome" />
      </a>
      <div>{if $lang eq 'basic'}Guidebook used with permission from TI. <a href="http://education.ti.com/">http://education.ti.com/</a>{/if}{if $lang eq 'axe'}Axe Parser Commands List used with permission from Quigibo.{/if}{if $lang eq 'grammer'}Grammer Commands List used with permission from Xeda Elnara.{/if}<br />Other content may be user-contributed.<br />ClrHome makes no guarantees of accuracy of the information present on this page.<br />Please <a href="/?action=register">log in</a> or <a href="/?action=register">register</a> to contribute to this wiki.</div>
      <cite></cite>
    </div>
  </body>
</html>
