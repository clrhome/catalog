<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Token Catalog</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link href="catalog.css" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="/lib/js/jquery.js"></script>
    <script type="text/javascript" src="/lib/js/ga.js"></script>
    <script type="text/javascript">// <![CDATA[
{if $editable}      $(function() {
        $('.right dd').dblclick(function() {
          $(this).html('<textarea>' + $(this).text() + '</textarea>').children().focus().select().blur(function() {
            var e = $('.left a.active').last().attr('href');
            b = $(this);
            var k = {i: parseInt(e.slice(2, 4), 16), j: parseInt(e.slice(4, 6), 16)};
            k[$(this).parent().prev().html()] = $(this).val();

            $.post('./', k, function(e) {
              b.parent().html(e);
            });
          }).dblclick(false);
        });
      });
{/if}    // ]]></script>
    <script type="text/javascript" src="bin/js/catalog.js"></script>
  </head>
  <body>
    <header>
      <a>
        <img src="/catalog/bar.png" alt="The Catalog: Online Token Reference" />
      </a>
      <nav>
        <a href="/catalog/"{if $language eq 'basic'} class="active"{/if}>TI-BASIC</a>
        <a href="/catalog/axe/"{if $language eq 'axe'} class="active"{/if}>Axe</a>
        <a href="/catalog/grammer/"{if $language eq 'grammer'} class="active"{/if}>Grammer</a>
      </nav>
    </header>
    <main>
      <div class="gallery">
        <div>
          {include file='table.tpl'}
        </div>
      </div>
      <input type="text" />
    </main>
    <footer>
      <a href="/resources/">
        <span>another resource by</span>
        <img src="/images/emblem.png" alt="ClrHome" />
      </a>
      <p>{if $language eq 'basic'}Guidebook used with permission from TI. <a href="http://education.ti.com/">http://education.ti.com/</a>{/if}{if $language eq 'axe'}Axe Parser Commands List used with permission from Quigibo.{/if}{if $language eq 'grammer'}Grammer Commands List used with permission from Xeda Elnara.{/if}</p>
      <p>Other content may be user-contributed.</p>
      <p>ClrHome makes no guarantees of accuracy of the information present on this page.</p>
      <p>Please <a href="/?action=register">log in</a> or <a href="/?action=register">register</a> to contribute to this wiki.</p>
    </footer>
  </body>
</html>
