<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Catalog - ClrHome</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link href="https://clrhome.org/logo.css" type="text/css" rel="stylesheet" />
    <link href="catalog.css?v={$date}" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="/bin/js/ga.js"></script>
    <script type="text/javascript" src="catalog.js?v={$date}"></script>
{if $editable}    <script type="text/javascript" src="catalog-edit.js?v={$date}"></script>
{/if}  </head>
  <body>
    <header>
      <h1>
        <img src="/catalog/bar.png" alt="Catalog: Online Token Reference" />
      </h1>
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
      <h1 class="logo">
        <a href="/resources/">
          <span>another resource by</span>
          <img src="/images/emblem.png" alt="ClrHome" />
        </a>
      </h1>
      <p>{if $language eq 'basic'}Guidebook used with permission from TI. <a href="http://education.ti.com/">http://education.ti.com/</a>{/if}{if $language eq 'axe'}Axe Parser Commands List used with permission from Quigibo.{/if}{if $language eq 'grammer'}Grammer Commands List used with permission from Xeda Elnara.{/if}</p>
      <p>Other content may be user-contributed.</p>
      <p>ClrHome makes no guarantees of accuracy of the information present on this page.</p>
      <p>Please <a href="/?action=register">log in</a> or <a href="/?action=register">register</a> to contribute to this wiki.</p>
    </footer>
  </body>
</html>
